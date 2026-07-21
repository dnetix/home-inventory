<?php

namespace App\Livewire\Places;

use App\Livewire\Concerns\SearchesGlyphs;
use App\Livewire\Concerns\SelectsItems;
use App\Livewire\Forms\PlaceForm;
use App\Models\Item;
use App\Models\Place;
use App\Support\PlaceTree;
use App\Support\UnitFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests, SearchesGlyphs, SelectsItems;

    public Place $place;

    public PlaceForm $form;

    /**
     * '' closed · 'edit' editing this place · 'add' adding a sub-location.
     */
    public string $editor = '';

    public function mount(Place $place): void
    {
        $this->authorize('view', $place);

        $this->place = $place;
    }

    public function openEdit(): void
    {
        $this->authorize('update', $this->place);

        $this->form->setPlace($this->place);
        $this->editor = 'edit';
    }

    public function openAddChild(): void
    {
        $this->authorize('create', Place::class);

        $this->form->reset();
        $this->form->parentId = $this->place->id;
        $this->editor = 'add';
    }

    public function closeEditor(): void
    {
        $this->editor = '';
        $this->form->reset();
        $this->form->resetValidation();
        $this->reset('glyphSearch');
    }

    /**
     * @return list<string>
     */
    protected function defaultGlyphs(): array
    {
        return PlaceForm::GLYPHS;
    }

    protected function currentGlyph(): string
    {
        return $this->form->glyph;
    }

    public function save(): void
    {
        $adding = $this->editor === 'add';

        $this->form->save();

        $this->closeEditor();
        $this->place->refresh();
        unset($this->tree);

        $this->dispatch('toast', message: $adding ? 'Location added' : 'Location saved');
    }

    public function deletePlace(): void
    {
        $this->authorize('delete', $this->place);

        if ($this->tree->childrenOf($this->place->id)->isNotEmpty() || $this->tree->itemsUnder($this->place->id)->isNotEmpty()) {
            $this->dispatch('toast', message: 'Move or empty this location first');

            return;
        }

        $this->place->delete();

        session()->flash('toast', 'Location deleted');

        $this->redirectRoute('places.index', navigate: true);
    }

    #[Computed]
    public function tree(): PlaceTree
    {
        return new PlaceTree(Place::query()->get(), Item::query()->with(['category'])->get());
    }

    /**
     * Alias for the shared batch partials, which expect a placeIndex.
     */
    #[Computed]
    public function placeIndex(): PlaceTree
    {
        return $this->tree;
    }

    #[Computed]
    public function units(): UnitFormatter
    {
        return app(UnitFormatter::class);
    }

    public function render(): View
    {
        return view('livewire.places.show')->title($this->place->label);
    }
}
