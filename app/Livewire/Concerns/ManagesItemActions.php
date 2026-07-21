<?php

namespace App\Livewire\Concerns;

use App\Actions\StoreItemPhoto;
use App\Enums\ItemStatus;
use App\Livewire\Forms\UpkeepTaskForm;
use App\Models\Item;
use App\Models\Lend;
use App\Models\Place;
use App\Models\UpkeepTask;
use App\Support\FitChecker;
use App\Support\FitResult;
use App\Support\PlaceTree;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Item actions shared by the Items index (desktop detail pane) and the
 * full item detail screen: mark a lend returned, change the status,
 * add/replace the photo, and the transfer flow with its "will it fit?"
 * verdict. Host components must also use WithFileUploads.
 */
trait ManagesItemActions
{
    public ?int $transferItemId = null;

    public ?int $transferPlaceId = null;

    public ?int $statusItemId = null;

    public ?TemporaryUploadedFile $detailPhoto = null;

    public UpkeepTaskForm $upkeepForm;

    public ?int $upkeepItemId = null;

    public function startUpkeep(int $itemId): void
    {
        $item = Item::findOrFail($itemId);

        $this->authorize('create', UpkeepTask::class);

        $this->upkeepForm->reset();
        $this->upkeepForm->resetValidation();
        $this->upkeepForm->itemId = $item->id;
        $this->upkeepItemId = $item->id;
    }

    public function cancelUpkeep(): void
    {
        $this->upkeepItemId = null;
        $this->upkeepForm->reset();
        $this->upkeepForm->resetValidation();
    }

    public function saveUpkeep(): void
    {
        $this->authorize('create', UpkeepTask::class);

        $this->upkeepForm->itemId = $this->upkeepItemId;
        $this->upkeepForm->save();

        $this->cancelUpkeep();

        $this->dispatch('toast', message: 'Upkeep task added');
    }

    #[Computed]
    public function upkeepItem(): ?Item
    {
        return $this->upkeepItemId === null ? null : Item::withRemoved()->find($this->upkeepItemId);
    }

    public function updatedDetailPhoto(): void
    {
        $this->validate(['detailPhoto' => ['required', 'image', 'max:8192']]);

        $item = $this->detailPhotoItem();

        $this->authorize('update', $item);

        $previous = $item->photo_path;
        $path = app(StoreItemPhoto::class)->store($this->detailPhoto, $item->home_id, 'detailPhoto');

        $item->update(['photo_path' => $path]);

        if ($previous !== null && $previous !== $path) {
            Item::photoDisk()->delete($previous);
        }

        $this->detailPhoto = null;
        $this->refreshBoundItem();

        $this->dispatch('toast', message: $previous === null ? 'Photo added' : 'Photo replaced');
    }

    /**
     * The item the detail view currently shows — the target for photo uploads.
     */
    abstract protected function detailPhotoItem(): Item;

    public function returnLend(int $lendId): void
    {
        $lend = Lend::findOrFail($lendId);

        $this->authorize('update', $lend);

        $lend->update(['returned_at' => today()]);

        $this->refreshBoundItem();

        $this->dispatch('toast', message: 'Marked returned');
    }

    public function startStatus(int $itemId): void
    {
        $this->statusItemId = $itemId;
    }

    public function cancelStatus(): void
    {
        $this->statusItemId = null;
    }

    public function setStatus(int $itemId, string $status): void
    {
        $status = ItemStatus::from($status);
        $item = Item::withRemoved()->findOrFail($itemId);

        $this->authorize('update', $item);

        $restoring = $item->status === ItemStatus::Removed && $status !== ItemStatus::Removed;

        $item->update(['status' => $status]);

        $this->cancelStatus();
        $this->refreshBoundItem();

        $this->dispatch('toast', message: match (true) {
            $restoring => 'Restored to inventory',
            $status === ItemStatus::InPlace => 'Marked in place',
            $status === ItemStatus::Missing => 'Marked missing',
            $status === ItemStatus::Broken => 'Marked broken',
            default => 'Removed from inventory',
        });
    }

    #[Computed]
    public function statusItem(): ?Item
    {
        return $this->statusItemId === null ? null : Item::withRemoved()->find($this->statusItemId);
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
