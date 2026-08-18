<?php

namespace App\Livewire\Concerns;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Multi-select + batch operations (move, change status, change category,
 * add/remove tags) shared by the items list and a place's item list. The
 * blade side is the batch-bar / batch-*-sheet partials; the component must
 * also expose a placeIndex computed (PlaceTree) for the move sheet.
 */
trait SelectsItems
{
    public bool $selecting = false;

    /** @var list<int> */
    public array $selectedIds = [];

    /**
     * '' closed · 'move' place picker · 'status' status picker ·
     * 'category' category picker · 'tags' tag toggles.
     */
    public string $batchSheet = '';

    public ?int $batchPlaceId = null;

    /**
     * null nothing picked yet · 0 "No category" · otherwise a category id.
     */
    public ?int $batchCategoryId = null;

    /**
     * Displayed tri-state per tag: 'all' on every selected item, 'some' on a
     * few, 'none' on none. Toggling flips between 'all' and 'none'.
     *
     * @var array<int, string>
     */
    public array $batchTagStates = [];

    /**
     * Only the tags the user actually toggled ('all' attach · 'none' detach) —
     * untouched tags keep whatever each item already has.
     *
     * @var array<int, string>
     */
    public array $batchTagChanges = [];

    public function toggleSelecting(): void
    {
        $this->selecting = ! $this->selecting;
        $this->selectedIds = [];
        $this->resetBatchState();
    }

    public function toggleSelected(int $itemId): void
    {
        $this->selectedIds = in_array($itemId, $this->selectedIds, true)
            ? array_values(array_diff($this->selectedIds, [$itemId]))
            : [...$this->selectedIds, $itemId];
    }

    /**
     * @param  list<int>  $ids
     */
    public function selectMany(array $ids): void
    {
        $this->selectedIds = array_values(array_unique([...$this->selectedIds, ...array_map(intval(...), $ids)]));
    }

    public function openBatch(string $sheet): void
    {
        if ($this->selectedIds === [] || ! in_array($sheet, ['move', 'status', 'category', 'tags'], true)) {
            return;
        }

        $this->resetBatchState();
        $this->batchSheet = $sheet;

        if ($sheet === 'tags') {
            $this->batchTagStates = $this->initialTagStates();
        }
    }

    public function closeBatch(): void
    {
        $this->resetBatchState();
    }

    public function confirmBatchMove(): void
    {
        $place = $this->batchPlaceId === null ? null : Place::query()->findOrFail($this->batchPlaceId);

        $count = $this->eachSelected(fn (Item $item) => $item->update(['place_id' => $place?->id]));

        $this->dispatch('toast', message: $this->countLabel($count).' moved to '.($place?->label ?? '“No location”'));
    }

    public function batchSetStatus(string $status): void
    {
        $status = ItemStatus::from($status);

        $count = $this->eachSelected(fn (Item $item) => $item->update(['status' => $status]));

        $this->dispatch('toast', message: match ($status) {
            ItemStatus::InPlace => $this->countLabel($count).' marked in place',
            ItemStatus::Missing => $this->countLabel($count).' marked missing',
            ItemStatus::Broken => $this->countLabel($count).' marked broken',
            ItemStatus::Removed => $this->countLabel($count).' removed from inventory',
        });
    }

    public function confirmBatchCategory(): void
    {
        if ($this->batchCategoryId === null) {
            return;
        }

        $category = $this->batchCategoryId === 0 ? null : Category::query()->findOrFail($this->batchCategoryId);

        $count = $this->eachSelected(fn (Item $item) => $item->update(['category_id' => $category?->id]));

        $this->dispatch('toast', message: $this->countLabel($count).($category === null
            ? ' marked uncategorized'
            : ' categorized as '.$category->label));
    }

    public function toggleBatchTag(int $tagId): void
    {
        if (! array_key_exists($tagId, $this->batchTagStates)) {
            return;
        }

        $state = $this->batchTagStates[$tagId] === 'all' ? 'none' : 'all';
        $this->batchTagStates[$tagId] = $state;
        $this->batchTagChanges[$tagId] = $state;
    }

    public function confirmBatchTags(): void
    {
        // The home scope on Tag drops toggled ids that aren't ours.
        $attach = Tag::query()->whereIn('id', array_keys($this->batchTagChanges, 'all', true))->pluck('id')->all();
        $detach = array_keys($this->batchTagChanges, 'none', true);

        if ($attach === [] && $detach === []) {
            $this->closeBatch();

            return;
        }

        $count = $this->eachSelected(function (Item $item) use ($attach, $detach): void {
            $item->tags()->syncWithoutDetaching($attach);
            $item->tags()->detach($detach);
        });

        $this->dispatch('toast', message: 'Tags updated for '.$this->countLabel($count));
    }

    /**
     * Run the mutation over every selected item the user may update. The
     * home scope silently drops ids from other homes.
     */
    private function eachSelected(callable $mutation): int
    {
        $items = Item::withRemoved()->whereIn('id', $this->selectedIds)->get();

        foreach ($items as $item) {
            $this->authorize('update', $item);
            $mutation($item);
        }

        $this->selecting = false;
        $this->selectedIds = [];
        $this->resetBatchState();

        return $items->count();
    }

    private function resetBatchState(): void
    {
        $this->batchSheet = '';
        $this->batchPlaceId = null;
        $this->batchCategoryId = null;
        $this->batchTagStates = [];
        $this->batchTagChanges = [];
    }

    /**
     * @return array<int, string>
     */
    private function initialTagStates(): array
    {
        $items = Item::withRemoved()->whereIn('id', $this->selectedIds)->with('tags:tags.id')->get();
        $counts = $items->flatMap(fn (Item $item) => $item->tags->modelKeys())->countBy();

        return $this->batchTags
            ->mapWithKeys(fn (Tag $tag) => [$tag->id => match (true) {
                ($counts[$tag->id] ?? 0) === 0 => 'none',
                $counts[$tag->id] >= $items->count() => 'all',
                default => 'some',
            }])
            ->all();
    }

    private function countLabel(int $count): string
    {
        return $count.' '.Str::plural('item', $count);
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selectedIds);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function batchCategories(): Collection
    {
        return Category::pickerOrdered();
    }

    /**
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function batchTags(): Collection
    {
        return Tag::query()->orderBy('label')->get();
    }
}
