{{-- Batch move: pick a destination for every selected item. --}}
<x-ui.sheet close="closeBatch" :title="'Move '.$this->selectedCount.' '.Str::plural('item', $this->selectedCount)">
    <div class="flex max-h-[44vh] flex-col overflow-y-auto rounded-[14px] border border-line">
        @foreach ($this->placeIndex->flatten() as $entry)
            <button type="button" wire:key="bp-{{ $entry['place']->id }}"
                wire:click="$set('batchPlaceId', {{ $entry['place']->id }})"
                class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left last:border-0 {{ $batchPlaceId === $entry['place']->id ? 'bg-accent-soft' : 'hover:bg-fill' }}"
                style="padding-left: {{ 14 + $entry['depth'] * 18 }}px">
                <x-icon :name="$entry['place']->glyph ?: 'box'" :size="16"
                    class="{{ $batchPlaceId === $entry['place']->id ? 'text-accent-ink' : 'text-ink-3' }}" />
                <span class="flex-1 text-[14px] font-semibold {{ $batchPlaceId === $entry['place']->id ? 'text-accent-ink' : '' }}">
                    {{ $entry['place']->label }}
                </span>
            </button>
        @endforeach
    </div>

    <div class="mt-4 flex gap-2.5">
        <x-ui.btn variant="ghost" class="flex-1" wire:click="closeBatch">Cancel</x-ui.btn>
        <x-ui.btn variant="primary" class="flex-1 {{ $batchPlaceId === null ? 'pointer-events-none opacity-50' : '' }}"
            wire:click="confirmBatchMove">
            Move here
        </x-ui.btn>
    </div>
</x-ui.sheet>
