<?php

namespace App\Livewire\Concerns;

use App\Models\Item;
use App\Models\Lend;
use App\Models\Place;
use App\Support\FitChecker;
use App\Support\FitResult;
use App\Support\PlaceTree;
use Livewire\Attributes\Computed;

/**
 * Item actions shared by the Items index (desktop detail pane) and the
 * full item detail screen: mark a lend returned, and the transfer flow
 * with its "will it fit?" verdict.
 */
trait ManagesItemActions
{
    public ?int $transferItemId = null;

    public ?int $transferPlaceId = null;

    public function returnLend(int $lendId): void
    {
        $lend = Lend::findOrFail($lendId);

        $this->authorize('update', $lend);

        $lend->update(['returned_at' => today()]);

        $this->refreshBoundItem();

        $this->dispatch('toast', message: 'Marked returned');
    }

    public function startTransfer(int $itemId): void
    {
        $this->transferItemId = $itemId;
        $this->transferPlaceId = null;
    }

    public function cancelTransfer(): void
    {
        $this->transferItemId = null;
        $this->transferPlaceId = null;
    }

    public function confirmTransfer(): void
    {
        $item = Item::findOrFail($this->transferItemId);

        $this->authorize('update', $item);

        $place = $this->transferPlaceId === null ? null : Place::findOrFail($this->transferPlaceId);

        $item->update(['place_id' => $place?->id]);

        $this->cancelTransfer();
        $this->refreshBoundItem();

        $this->dispatch('toast', message: $place === null ? 'Moved to “No location”' : 'Moved to '.$place->label);
    }

    /**
     * The Show screen holds the item as a hydrated property — reload it after
     * a mutation so the re-render reflects the change. No-op on the index.
     */
    private function refreshBoundItem(): void
    {
        if (property_exists($this, 'item') && isset($this->item)) {
            $this->item->refresh();
        }
    }

    #[Computed]
    public function transferItem(): ?Item
    {
        return $this->transferItemId === null ? null : Item::find($this->transferItemId);
    }

    #[Computed]
    public function transferFit(): ?FitResult
    {
        if ($this->transferItemId === null || $this->transferPlaceId === null) {
            return null;
        }

        $item = Item::find($this->transferItemId);
        $place = Place::find($this->transferPlaceId);

        if ($item === null || $place === null) {
            return null;
        }

        return (new FitChecker)->check($item, $place, $this->fullTree());
    }

    private function fullTree(): PlaceTree
    {
        return new PlaceTree(Place::query()->get(), Item::query()->get());
    }
}
