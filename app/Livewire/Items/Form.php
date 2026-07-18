<?php

namespace App\Livewire\Items;

use App\Livewire\Forms\ItemForm;
use App\Models\Category;
use App\Models\Item;
use App\Models\Place;
use App\Models\Tag;
use App\Support\PhotoShrinker;
use App\Support\PlaceTree;
use App\Support\UnitFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    public bool $placePickerOpen = false;

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

        $item = $this->form->save();

        if ($this->form->item === null) {
            session([
                'items.last_place_id' => $item->place_id,
                'items.last_category_id' => $item->category_id,
            ]);
        }

        $this->persistPhoto($item);

        session()->flash('toast', $this->form->item !== null ? 'Changes saved' : 'Item added');

        $this->redirectRoute('items.show', $item, navigate: true);
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->removePhoto = true;
    }

    private function persistPhoto(Item $item): void
    {
        $previous = $item->photo_path;

        if ($this->photo !== null) {
            $original = $this->photo->get();
            $shrunk = (new PhotoShrinker)->shrink($original);
            $name = $shrunk === $original
                ? $this->photo->hashName()
                : pathinfo($this->photo->hashName(), PATHINFO_FILENAME).'.jpg';

            $path = 'items/'.$item->home_id.'/'.$name;
            Storage::disk('s3')->put($path, $shrunk);

            $item->update(['photo_path' => $path]);
        } elseif ($this->removePhoto && $previous !== null) {
            $item->update(['photo_path' => null]);
        } else {
            return;
        }

        if ($previous !== null && $previous !== $item->photo_path) {
            Storage::disk('s3')->delete($previous);
        }
    }

    public function toggleTag(int $tagId): void
    {
        $this->form->tagIds = in_array($tagId, $this->form->tagIds, true)
            ? array_values(array_diff($this->form->tagIds, [$tagId]))
            : [...$this->form->tagIds, $tagId];
    }

    public function pickPlace(?int $placeId): void
    {
        $this->form->placeId = $placeId;
        $this->placePickerOpen = false;
    }

    public function closePlacePicker(): void
    {
        $this->placePickerOpen = false;
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
        $all = Category::query()->orderBy('label')->get();

        return $all
            ->whereNull('parent_id')
            ->flatMap(fn (Category $top) => [$top, ...$all->where('parent_id', $top->id)])
            ->values();
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
