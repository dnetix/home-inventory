<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col">
    {{-- Search bar --}}
    <div class="flex items-center gap-2 px-3 pt-6 pr-4 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('items.index') }}" wire:navigate
            class="-ml-1 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <div
            class="flex min-h-[44px] flex-1 items-center gap-2.5 rounded-[13px] border border-accent bg-surface px-3.5 ring-[3.5px] ring-accent-soft">
            <x-icon name="search" :size="18" :stroke="1.9" class="shrink-0 text-ink-3" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search items, places, tags…"
                autofocus
                class="w-full bg-transparent py-2.5 text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3">
            @if ($search !== '')
                <button type="button" class="shrink-0 cursor-pointer text-ink-3" wire:click="$set('search', '')">
                    <x-icon name="x" :size="17" :stroke="2" />
                </button>
            @endif
        </div>
    </div>

    {{-- Scope chips --}}
    <div class="flex gap-2 overflow-x-auto px-5 py-3 lg:px-[30px]" style="scrollbar-width: none">
        <x-ui.chip :on="$scope === null" :outline="$scope !== null" wire:click="setScope(null)">Everywhere</x-ui.chip>
        @foreach ($this->scopes as $place)
            <x-ui.chip :on="$scope === $place->id" :outline="$scope !== $place->id"
                wire:click="setScope({{ $place->id }})">
                <x-icon name="map-pin" :size="13" /> {{ $place->label }}
            </x-ui.chip>
        @endforeach
    </div>

    <div class="flex-1 px-5 pb-6 lg:px-[30px]">
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
            <div class="mb-3 text-xs font-semibold text-ink-3">
                {{ $this->results->count() }} {{ Str::plural('match', $this->results->count()) }}
            </div>
            <div class="flex flex-col gap-2.5">
                @foreach ($this->results as $item)
                    <a href="{{ route('items.show', $item) }}" wire:navigate wire:key="r-{{ $item->id }}">
                        <x-ui.card class="flex items-center gap-3 px-3 py-2.5 transition active:scale-[0.98]">
                            <x-ui.ph class="size-12 rounded-xl" :icon="$item->category?->glyph ?? 'box'"
                                :tint="$item->category?->color" />
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
                            <span class="text-[13.5px] font-semibold text-ink-2 tabular-nums">{{ $item->value?->format() ?? '—' }}</span>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
