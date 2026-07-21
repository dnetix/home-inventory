<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col" x-data="itemSelection($wire.entangle('selectedIds'))"
    x-on:scroll.window.passive="document.activeElement?.type === 'search' && document.activeElement.blur()">
    {{-- Search bar (mobile — pinned; the desktop input lives in the top bar) --}}
    <div class="sticky top-0 z-30 flex items-center gap-2 bg-screen px-3 pt-4 pr-4 pb-3 lg:hidden">
        <a href="{{ route('items.index') }}" wire:navigate
            class="-ml-1 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        @include('livewire.items.partials.find-input')
    </div>

    @teleport('#topbar-page')
        <div class="w-[400px]">
            @include('livewire.items.partials.find-input')
        </div>
    @endteleport

    <div class="flex-1 px-5 pb-6 lg:px-[30px] lg:pt-[18px]">
        @if (trim($search) === '')
            <x-ui.section-label class="mt-2 mb-3">Try searching</x-ui.section-label>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->suggestions as $suggestion)
                    <x-ui.chip outline wire:click="$set('search', '{{ $suggestion }}')" wire:key="s-{{ $suggestion }}">
                        <x-icon name="search" :size="13" /> {{ $suggestion }}
                    </x-ui.chip>
                @endforeach
            </div>
        @elseif ($this->results->isEmpty())
            <x-empty-state icon="search" title="No matches for “{{ $search }}”"
                sub="Check the spelling or widen the location." />
        @else
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-semibold text-ink-3">
                    {{ $this->results->count() }} {{ Str::plural('match', $this->results->count()) }}
                </span>
                <button type="button" wire:click="toggleSelecting"
                    class="cursor-pointer text-[13px] font-bold {{ $selecting ? 'text-accent' : 'text-ink-3' }}">
                    {{ $selecting ? 'Done' : 'Select' }}
                </button>
            </div>
            <div class="flex flex-col gap-2.5">
                @foreach ($this->results as $item)
                    @php
                        $resultRing = $selecting ? "has($item->id) && 'ring-2 ring-accent'" : "''";
                    @endphp
                    <a href="{{ route('items.show', $item) }}" wire:navigate wire:key="r-{{ $item->id }}"
                        @if ($selecting) x-on:click.prevent="toggle({{ $item->id }})" @endif>
                        <x-ui.card class="flex items-center gap-3 px-3 py-2.5 transition active:scale-[0.98]"
                            x-bind:class="{{ $resultRing }}">
                            @if ($selecting)
                                <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full border transition"
                                    x-bind:class="has({{ $item->id }}) ? 'border-accent bg-accent text-on-accent' : 'border-line-2'">
                                    <x-icon name="check" :size="13" :stroke="2.5" x-show="has({{ $item->id }})" x-cloak />
                                </span>
                            @endif
                            <x-item-thumb class="size-12 rounded-xl" :item="$item" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[15px] font-semibold">{{ $item->name }}</div>
                                <div class="mt-[3px] flex items-center gap-1 text-xs font-semibold text-accent">
                                    <x-icon name="map-pin" :size="12" :stroke="2" />
                                    @if ($item->place_id)
                                        @foreach ($this->placeIndex->breadcrumb($item->place_id) as $part)
                                            @if (! $loop->first)<span class="text-ink-4">›</span>@endif
                                            <span>{{ $part }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-ink-4">No location</span>
                                    @endif
                                </div>
                            </div>
                            @if ($item->status->pillVariant())
                                <x-ui.pill :variant="$item->status->pillVariant()">{{ strtolower($item->status->label()) }}</x-ui.pill>
                            @elseif ($item->activeLend)
                                <x-ui.pill variant="bad">lent</x-ui.pill>
                            @endif
                            <span class="text-[13.5px] font-semibold text-ink-2 tabular-nums">{{ $item->value?->format() ?? '—' }}</span>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Batch selection --}}
    @if ($selecting)
        @include('livewire.items.partials.batch-bar', ['selectableIds' => $this->results->pluck('id')->values()->all()])
    @endif
    @if ($batchSheet === 'move')
        @include('livewire.items.partials.batch-move-sheet')
    @elseif ($batchSheet === 'status')
        @include('livewire.items.partials.batch-status-sheet')
    @endif
</div>
