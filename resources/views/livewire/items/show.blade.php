<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('items.index') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="flex-1"></span>
        <a href="{{ route('items.edit', $item) }}" wire:navigate>
            <x-ui.icon-btn icon="edit" :size="17" />
        </a>
        <x-ui.icon-btn icon="dots" :size="18" wire:click="$set('menuOpen', true)" />
    </div>

    <div class="flex-1 px-5 pb-6 lg:px-[30px]">
        @include('livewire.items.partials.detail', ['item' => $item, 'pane' => false])
    </div>

    {{-- Action menu sheet --}}
    @if ($menuOpen)
        <x-ui.sheet :title="$item->name" close="closeMenu">
            <div class="flex flex-col">
                @if ($item->activeLend)
                    <button type="button"
                        wire:click="returnLend({{ $item->activeLend->id }}); $set('menuOpen', false)"
                        class="flex cursor-pointer items-center gap-3.5 border-b border-line py-[13px] text-left">
                        <x-icon name="undo" :size="20" class="text-ink-2" />
                        <div class="flex-1">
                            <div class="text-[15px] font-semibold">Mark returned</div>
                            <div class="text-[12.5px] font-medium text-ink-3">Lent to {{ $item->activeLend->person }}</div>
                        </div>
                    </button>
                @else
                    <div class="flex items-center gap-3.5 border-b border-line py-[13px] opacity-45">
                        <x-icon name="hand" :size="20" class="text-ink-2" />
                        <div class="flex-1">
                            <div class="text-[15px] font-semibold">Lend item</div>
                            <div class="text-[12.5px] font-medium text-ink-3">Coming with the Lending module</div>
                        </div>
                    </div>
                @endif

                <button type="button" wire:click="$set('menuOpen', false); startStatus({{ $item->id }})"
                    class="flex w-full cursor-pointer items-center gap-3.5 border-b border-line py-[13px] text-left">
                    <x-icon name="check-circle" :size="20" class="text-ink-2" />
                    <div class="flex-1">
                        <div class="text-[15px] font-semibold">Change status</div>
                        <div class="text-[12.5px] font-medium text-ink-3">{{ $item->status->label() }} now</div>
                    </div>
                </button>

                <a href="{{ route('items.edit', $item) }}" wire:navigate
                    class="flex items-center gap-3.5 border-b border-line py-[13px]">
                    <x-icon name="edit" :size="20" class="text-ink-2" />
                    <div class="flex-1 text-[15px] font-semibold">Edit details</div>
                </a>

                <button type="button" wire:click="delete"
                    wire:confirm="Delete “{{ $item->name }}”? This can't be undone."
                    class="flex cursor-pointer items-center gap-3.5 py-[13px] text-left text-bad">
                    <x-icon name="trash" :size="20" />
                    <div class="flex-1 text-[15px] font-semibold">Delete item</div>
                </button>
            </div>
        </x-ui.sheet>
    @endif

    {{-- Transfer sheet --}}
    @if ($this->transferItem)
        @include('livewire.items.partials.transfer-sheet')
    @endif

    {{-- Status sheet --}}
    @if ($this->statusItem)
        @include('livewire.items.partials.status-sheet')
    @endif
</div>
