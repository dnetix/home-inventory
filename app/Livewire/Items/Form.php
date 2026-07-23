<?php

namespace App\Livewire\Items;

use App\Actions\StoreItemPhoto;
use App\Livewire\Forms\ItemForm;
use App\Models\Category;
use App\Models\Item;
use App\Models\Place;
use App\Models\Tag;
use App\Support\PlaceTree;
use App\Support\UnitFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ItemForm $form;

    #[Validate('nullable|image|max:8192')]
    public ?TemporaryUploadedFile $photo = null;

    public bool $removePhoto = false;

    public function mount(?Item $item = null): void
    {
        if ($item?->exists) {
            $this->authorize('update', $item);
            $this->form->setItem($item->load('tags'));
        } else {
            $this->authorize('create', Item::class);

            // Making inventory means many items in a row from the same spot:
            // prefill the pickers with what the last saved item used. Stale or
            // cross-home ids resolve to null through the home-scoped queries.
            $this->form->placeId = Place::query()->find(session('items.last_place_id'))?->id;
            $this->form->categoryId = Category::query()->find(session('items.last_category_id'))?->id;
        }
    }

    public function save(): void
    {
        $this->validate();

        // Upload before touching the database: a failed write aborts the whole
        // save with a form error instead of an item pointing at a missing object.
        $path = $this->photo !== null ? $this->storePhoto() : null;

        $item = $this->form->save();

        if ($this->form->item === null) {
            session([
                'items.last_place_id' => $item->place_id,
                'items.last_category_id' => $item->category_id,
            ]);
        }

        $this->applyPhoto($item, $path);

        session()->flash('toast', $this->form->item !== null ? 'Changes saved' : 'Item added');

        $this->redirectRoute('items.show', $item, navigate: true);
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->removePhoto = true;
    }

    private function storePhoto(): string
    {
        return app(StoreItemPhoto::class)->store($this->photo, auth()->user()->current_home_id);
    }

    private function applyPhoto(Item $item, ?string $path): void
    {
        $previous = $item->photo_path;

        if ($path !== null) {
            $item->update(['photo_path' => $path]);
        } elseif ($this->removePhoto && $previous !== null) {
            $item->update(['photo_path' => null]);
        } else {
            return;
        }

        if ($previous !== null && $previous !== $item->photo_path) {
            Item::photoDisk()->delete($previous);
        }
    }

    public function toggleTag(int $tagId): void
    {
        $this->form->tagIds = in_array($tagId, $this->form->tagIds, true)
            ? array_values(array_diff($this->form->tagIds, [$tagId]))
            : [...$this->form->tagIds, $tagId];
    }

    /**
     * Existing items whose names match what is being typed — shown while
     * creating so the user can spot "I already have this one".
     *
     * @return Collection<int, Item>
     */
    #[Computed]
    public function possibleDuplicates(): Collection
    {
        $words = Str::of($this->form->name)->squish()->lower()->explode(' ')->filter()->take(5);

        if ($this->form->item !== null || $words->isEmpty() || mb_strlen($words->join(' ')) < 2) {
            return new Collection;
        }

        $query = Item::query()->with('category');

        foreach ($words as $word) {
            $query->where('name', 'like', '%'.addcslashes($word, '%_\\').'%');
        }

        $first = addcslashes($words->first(), '%_\\');

        return $query
            ->orderByRaw('case when name like ? then 0 else 1 end, name', [$first.'%'])
            ->limit(4)
            ->get();
    }

    /**
     * All categories, top-level groups first, children after their parent.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::pickerOrdered();
    }

    /**
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return Tag::query()->orderBy('label')->get();
    }

    #[Computed]
    public function placeIndex(): PlaceTree
    {
        return new PlaceTree(Place::query()->get(), new Collection);
    }

    #[Computed]
    public function units(): UnitFormatter
    {
        return app(UnitFormatter::class);
    }

    public function render(): View
    {
        return view('livewire.items.form')->title($this->form->item !== null ? 'Edit item' : 'New item');
    }
}
