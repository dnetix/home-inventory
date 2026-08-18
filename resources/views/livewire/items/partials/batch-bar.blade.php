{{-- Floating batch-action bar, shown while selection mode is on. Selection
     lives client-side (itemSelection Alpine scope on the screen root) so
     checking items is instant; the server sees it with the next request.
     Expects $selectableIds: ids of the currently listed items ("All").
     On small phones the action labels collapse to icons so all fit. --}}
<div class="fixed inset-x-0 bottom-[96px] z-40 flex justify-center px-5 lg:bottom-8">
    <div class="flex items-center gap-1 rounded-[16px] border border-line bg-surface py-1.5 pr-1.5 pl-4 shadow-lg">
        <span class="text-[13.5px] font-bold whitespace-nowrap tabular-nums"><span x-text="sel.length"></span><span class="max-sm:hidden"> selected</span></span>
        <button type="button" x-on:click="all(@js($selectableIds))"
            class="ml-1 cursor-pointer rounded-[10px] px-2.5 py-2 text-[13.5px] font-bold text-accent">
            All
        </button>
        <button type="button" wire:click="openBatch('move')" title="Move"
            x-bind:class="sel.length === 0 && 'pointer-events-none opacity-50'"
            class="flex cursor-pointer items-center gap-1.5 rounded-[10px] px-2.5 py-2 text-[13.5px] font-bold text-accent">
            <x-icon name="map-pin" :size="15" /> <span class="max-sm:hidden">Move</span>
        </button>
        <button type="button" wire:click="openBatch('status')" title="Status"
            x-bind:class="sel.length === 0 && 'pointer-events-none opacity-50'"
            class="flex cursor-pointer items-center gap-1.5 rounded-[10px] px-2.5 py-2 text-[13.5px] font-bold text-accent">
            <x-icon name="check-circle" :size="15" /> <span class="max-sm:hidden">Status</span>
        </button>
        <button type="button" wire:click="openBatch('category')" title="Category"
            x-bind:class="sel.length === 0 && 'pointer-events-none opacity-50'"
            class="flex cursor-pointer items-center gap-1.5 rounded-[10px] px-2.5 py-2 text-[13.5px] font-bold text-accent">
            <x-icon name="layers" :size="15" /> <span class="max-sm:hidden">Category</span>
        </button>
        @if ($this->batchTags->isNotEmpty())
            <button type="button" wire:click="openBatch('tags')" title="Tags"
                x-bind:class="sel.length === 0 && 'pointer-events-none opacity-50'"
                class="flex cursor-pointer items-center gap-1.5 rounded-[10px] px-2.5 py-2 text-[13.5px] font-bold text-accent">
                <x-icon name="tag" :size="15" /> <span class="max-sm:hidden">Tags</span>
            </button>
        @endif
        <button type="button" wire:click="toggleSelecting"
            class="flex size-9 cursor-pointer items-center justify-center rounded-[10px] text-ink-2 hover:bg-fill">
            <x-icon name="x" :size="17" :stroke="2" />
        </button>
    </div>
</div>
