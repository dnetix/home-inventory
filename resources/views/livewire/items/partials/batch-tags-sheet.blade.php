{{-- Batch tags: tri-state chips. Check = on every selected item, dash = on
     some of them, plain = on none. Tapping puts the tag on all selected
     items, tapping again removes it from all; untouched tags keep whatever
     each item already has. --}}
<x-ui.sheet close="closeBatch" :title="'Tags for '.$this->selectedCount.' '.Str::plural('item', $this->selectedCount)">
    <p class="mb-3.5 text-[12.5px] leading-relaxed font-medium text-ink-3">
        Tap a tag to add it to every selected item, tap again to remove it from all of them.
        A dash means only some of the items have it — leave it untouched to keep it that way.
    </p>

    <div class="flex flex-wrap gap-2">
        @foreach ($this->batchTags as $tag)
            @php $state = $batchTagStates[$tag->id] ?? 'none'; @endphp
            <x-ui.chip wire:key="btag-{{ $tag->id }}" :dot="$tag->color"
                :on="$state === 'all'" :outline="$state === 'none'"
                :title="$tag->description"
                wire:click="toggleBatchTag({{ $tag->id }})">
                @if ($state === 'all')
                    <x-icon name="check" :size="12" :stroke="2.5" />
                @elseif ($state === 'some')
                    <x-icon name="minus" :size="12" :stroke="2.5" />
                @endif
                {{ $tag->label }}
            </x-ui.chip>
        @endforeach
    </div>

    <div class="mt-4 flex gap-2.5">
        <x-ui.btn variant="ghost" class="flex-1" wire:click="closeBatch">Cancel</x-ui.btn>
        <x-ui.btn variant="primary" class="flex-1 {{ $batchTagChanges === [] ? 'pointer-events-none opacity-50' : '' }}"
            wire:click="confirmBatchTags">
            Apply
        </x-ui.btn>
    </div>
</x-ui.sheet>
