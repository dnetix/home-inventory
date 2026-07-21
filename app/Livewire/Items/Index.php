<?php

namespace App\Livewire\Items;

use App\Enums\ItemStatus;
use App\Livewire\Concerns\ManagesItemActions;
use App\Livewire\Concerns\SelectsItems;
use App\Models\Category;
use App\Models\Item;
use App\Models\Place;
use App\Models\Tag;
use App\Support\PlaceTree;
use App\Support\SearchItems;
use App\Support\UnitFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Items')]
class Index extends Component
{
    use AuthorizesRequests, ManagesItemActions, SelectsItems, WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?int $cat = null;

    #[Url]
    public string $missing = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $tag = null;

    #[Url]
    public ?int $selected = null;

    #[Session('items.view')]
    public string $view = 'table';

    #[Session('items.tags')]
    public bool $showTags = false;

    #[Url]
    public string $sort = 'name';

    #[Url]
    public string $dir = 'asc';

    public bool $filterOpen = false;

    /**
     * Labels + empty-state copy for the data-quality filter.
     */
    public const array MISSING_META = [
        'cat' => ['label' => 'Uncategorized', 'empty' => 'No uncategorized items — everything has a category.'],
        'place' => ['label' => 'No location', 'empty' => 'Every item is assigned to a place.'],
        'value' => ['label' => 'Unpriced', 'empty' => 'Every item has a value recorded.'],
    ];

    /**
     * Status-filter options: url value => label.
     */
    public const array STATUS_FILTERS = [
        'lent' => 'Lent',
        'missing' => 'Missing',
        'broken' => 'Broken',
        'removed' => 'Removed',
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'cat', 'missing', 'status', 'tag'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            [$this->sort, $this->dir] = [$column, 'asc'];
        }
    }

    public function select(int $id): void
    {
        $this->selected = $id;
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['table', 'grid'], true) ? $view : 'table';
    }

    public function setMissing(string $missing): void
    {
        $this->missing = array_key_exists($missing, self::MISSING_META) ? $missing : '';
        $this->filterOpen = false;
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->status = array_key_exists($status, self::STATUS_FILTERS) ? $status : '';
        $this->filterOpen = false;
        $this->resetPage();
    }

    public function setTagFilter(int|string $tagId): void
    {
        $this->tag = $tagId === '' ? null : Tag::query()->find($tagId)?->id;
        $this->filterOpen = false;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->missing = '';
        $this->status = '';
        $this->tag = null;
        $this->cat = null;
        $this->resetPage();
    }

    public function closeFilter(): void
    {
        $this->filterOpen = false;
    }

    /**
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

    /**
     * @return LengthAwarePaginator<int, Item>
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $searching = trim($this->search) !== '';

        $query = $searching
            ? (new SearchItems)->query($this->search)
            : Item::query()->with(['category', 'place', 'tags']);

        $query->with('activeLend');

        if ($this->cat !== null) {
            $ids = Category::query()
                ->whereKey($this->cat)
                ->orWhere('parent_id', $this->cat)
                ->pluck('id');

            $query->whereIn('category_id', $ids);
        }

        match ($this->missing) {
            'cat' => $query->whereNull('category_id'),
            'place' => $query->whereNull('place_id'),
            'value' => $query->whereNull('value'),
            default => null,
        };

        match ($this->status) {
            'lent' => $query->whereHas('activeLend'),
            'missing' => $query->where('status', ItemStatus::Missing),
            'broken' => $query->where('status', ItemStatus::Broken),
            'removed' => $query->withRemoved()->where('status', ItemStatus::Removed),
            default => null,
        };

        if ($this->tag !== null) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($this->tag));
        }

        if (! $searching || $this->sort !== 'name' || $this->dir !== 'asc') {
            $this->applySort($query);
        }

        return $query->paginate(30);
    }

    #[Computed]
    public function selectedItem(): ?Item
    {
        return $this->selected === null
            ? null
            : Item::withRemoved()->with(['category.parent', 'place', 'tags', 'activeLend'])->find($this->selected);
    }

    protected function detailPhotoItem(): Item
    {
        return Item::withRemoved()->findOrFail($this->selected);
    }

    /**
     * @return array{count: int, units: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'count' => Item::query()->count(),
            'units' => (int) Item::query()->sum('qty'),
        ];
    }

    /**
     * Places only — used for breadcrumbs and the transfer picker; fill math
     * is not needed on this screen.
     */
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

    /**
     * @param  Builder<Item>  $query
     */
    private function applySort(Builder $query): void
    {
        $query->reorder();

        match ($this->sort) {
            'category' => $query->orderBy(
                Category::query()->select('label')->whereColumn('categories.id', 'items.category_id'),
                $this->dir,
            ),
            'location' => $query->orderBy(
                Place::query()->select('label')->whereColumn('places.id', 'items.place_id'),
                $this->dir,
            ),
            'value' => $query->orderBy('value', $this->dir),
            'status' => $query
                ->withCount(['lends as active_lends_count' => fn (Builder $q) => $q->whereNull('returned_at')])
                ->orderBy('status', $this->dir)
                ->orderBy('active_lends_count', $this->dir),
            default => $query->orderBy('name', $this->dir),
        };

        $query->orderBy('id');
    }

    public function render(): View
    {
        return view('livewire.items.index');
    }
}
