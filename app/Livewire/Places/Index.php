<?php

namespace App\Livewire\Places;

use App\Livewire\Forms\PlaceForm;
use App\Models\Item;
use App\Models\Place;
use App\Support\PlaceTree;
use App\Support\UnitFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Places')]
class Index extends Component
{
    use AuthorizesRequests;

    public PlaceForm $form;

    /** @var list<int> ids of expanded tree nodes */
    public array $open = [];

    public bool $editorOpen = false;

    public function mount(): void
    {
        $this->open = $this->tree->roots()->pluck('id')->all();
    }

    public function toggle(int $placeId): void
    {
        $this->open = in_array($placeId, $this->open, true)
            ? array_values(array_diff($this->open, [$placeId]))
            : [...$this->open, $placeId];
    }

    public function toggleAll(): void
    {
        $parents = $this->parentIds();

        $this->open = array_diff($parents, $this->open) === [] ? [] : $parents;
    }

    public function openEditor(): void
    {
        $this->authorize('create', Place::class);

        $this->form->reset();
        $this->editorOpen = true;
    }

    public function closeEditor(): void
    {
        $this->editorOpen = false;
        $this->form->reset();
        $this->form->resetValidation();
    }

    public function save(): void
    {
        $place = $this->form->save();

        $this->closeEditor();
        unset($this->tree);

        if ($place->parent_id !== null && ! in_array($place->parent_id, $this->open, true)) {
            $this->open[] = $place->parent_id;
        }

        $this->dispatch('toast', message: 'Location added');
    }

    #[Computed]
    public function tree(): PlaceTree
    {
        return new PlaceTree(Place::query()->get(), Item::query()->get());
    }

    /**
     * @return array{places: int, items: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'places' => Place::query()->count(),
            'items' => Item::query()->count(),
        ];
    }

    #[Computed]
    public function units(): UnitFormatter
    {
        return app(UnitFormatter::class);
    }

    /**
     * @return list<int>
     */
    private function parentIds(): array
    {
        return $this->tree->flatten()
            ->map(fn (array $entry) => $entry['place'])
            ->filter(fn (Place $place) => $this->tree->childrenOf($place->id)->isNotEmpty())
            ->pluck('id')
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.places.index');
    }
}
