<?php

namespace App\Livewire\Items;

use App\Livewire\Concerns\SelectsItems;
use App\Models\Item;
use App\Models\Place;
use App\Support\PlaceTree;
use App\Support\SearchItems;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Find')]
class Find extends Component
{
    use AuthorizesRequests, SelectsItems;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @return Collection<int, Item>
     */
    #[Computed]
    public function results(): Collection
    {
        if (trim($this->search) === '') {
            return new Collection;
        }

        return (new SearchItems)->query($this->search)->with('activeLend')->limit(30)->get();
    }

    /**
     * First words of the newest items, as "try searching" chips.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function suggestions(): Collection
    {
        return Item::query()
            ->latest()
            ->limit(4)
            ->pluck('name')
            ->map(fn (string $name) => (string) Str::of($name)->lower()->explode(' ')->first())
            ->unique()
            ->values();
    }

    #[Computed]
    public function placeIndex(): PlaceTree
    {
        return new PlaceTree(Place::query()->get(), new Collection);
    }

    public function render(): View
    {
        return view('livewire.items.find');
    }
}
