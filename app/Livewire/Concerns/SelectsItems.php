<?php

namespace App\Livewire\Concerns;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Place;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Multi-select + batch operations (move, change status) shared by the items
 * list, the Find results, and a place's item list. The blade side is the
 * batch-bar / batch-move-sheet / batch-status-sheet partials; the component
 * must also expose a placeIndex computed (PlaceTree) for the move sheet.
 */
trait SelectsItems
{
    public bool $selecting = false;

    /** @var list<int> */
    public array $selectedIds = [];

    /**
     * '' closed · 'move' place picker · 'status' status picker.
     */
    public string $batchSheet = '';

    public ?int $batchPlaceId = null;

    public function toggleSelecting(): void
    {
        $this->selecting = ! $this->selecting;
        $this->selectedIds = [];
        $this->batchSheet = '';
        $this->batchPlaceId = null;
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
        if ($this->selectedIds === [] || ! in_array($sheet, ['move', 'status'], true)) {
            return;
        }

        $this->batchSheet = $sheet;
        $this->batchPlaceId = null;
    }

    public function closeBatch(): void
    {
        $this->batchSheet = '';
        $this->batchPlaceId = null;
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
        $this->batchSheet = '';
        $this->batchPlaceId = null;

        return $items->count();
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
}
