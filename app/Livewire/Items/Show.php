<?php

namespace App\Livewire\Items;

use App\Livewire\Concerns\ManagesItemActions;
use App\Models\Item;
use App\Models\Place;
use App\Support\PlaceTree;
use App\Support\UnitFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use AuthorizesRequests, ManagesItemActions, WithFileUploads;

    public Item $item;

    public bool $menuOpen = false;

    protected function detailPhotoItem(): Item
    {
        return $this->item;
    }

    public function mount(Item $item): void
    {
        $this->authorize('view', $item);

        $this->item = $item->load(['category.parent', 'place', 'tags', 'activeLend']);
    }

    public function closeMenu(): void
    {
        $this->menuOpen = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->item);

        $this->item->delete();

        session()->flash('toast', 'Item deleted');

        $this->redirectRoute('items.index', navigate: true);
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
        return view('livewire.items.show')->title($this->item->name);
    }
}
