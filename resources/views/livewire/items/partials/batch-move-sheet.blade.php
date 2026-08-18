{{-- Batch move: pick a destination for every selected item. --}}
<x-ui.sheet close="closeBatch" :title="'Move '.$this->selectedCount.' '.Str::plural('item', $this->selectedCount)">
    @include('livewire.items.partials.place-filter-list', [
        'property' => 'batchPlaceId',
        'selectedId' => $batchPlaceId,
    ])

    <div class="mt-4 flex gap-2.5">
        <x-ui.btn variant="ghost" class="flex-1" wire:click="closeBatch">Cancel</x-ui.btn>
        <x-ui.btn variant="primary" class="flex-1 {{ $batchPlaceId === null ? 'pointer-events-none opacity-50' : '' }}"
            wire:click="confirmBatchMove">
            Move here
        </x-ui.btn>
    </div>
</x-ui.sheet>
