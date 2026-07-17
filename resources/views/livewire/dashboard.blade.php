@php
    $stats = $this->stats;
    $maxCount = max(1, $this->categoryBars->max('count') ?? 1);
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col lg:mx-0 lg:max-w-none">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Home</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">{{ today()->format('l, F j') }}</p>
        </div>
        <a href="{{ route('upkeep.index') }}" wire:navigate class="relative">
            <x-ui.icon-btn icon="bell" />
            @if ($stats['attention'] > 0)
                <span class="absolute -top-[3px] -right-[3px] flex h-[17px] min-w-[17px] items-center justify-center rounded-full border-2 border-screen bg-bad px-1 text-[10.5px] font-bold text-white">
                    {{ $stats['attention'] }}
                </span>
            @endif
        </a>
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Home" :subtitle="today()->format('l, F j')" />
    @endteleport

    @teleport('#topbar-actions')
        <a href="{{ route('upkeep.index') }}" wire:navigate class="relative">
            <x-ui.icon-btn icon="bell" />
            @if ($stats['attention'] > 0)
                <span class="absolute -top-[3px] -right-[3px] flex h-[17px] min-w-[17px] items-center justify-center rounded-full border-2 border-screen bg-bad px-1 text-[10.5px] font-bold text-white">
                    {{ $stats['attention'] }}
                </span>
            @endif
        </a>
    @endteleport

    <div class="flex-1 px-5 pt-3 pb-6 lg:px-[30px] lg:pb-[30px]">
        {{-- Search (mobile) --}}
        <a href="{{ route('find') }}" wire:navigate
            class="mb-4 flex min-h-[50px] items-center gap-2.5 rounded-2xl bg-fill px-3.5 lg:hidden">
            <x-icon name="search" :size="19" :stroke="1.9" class="text-ink-3" />
            <span class="flex-1 text-[15.5px] font-medium text-ink-3">Search your home…</span>
        </a>

        {{-- Desktop stat tiles --}}
        <div class="mb-[18px] hidden grid-cols-4 gap-3.5 lg:grid">
            <x-ui.card class="px-4 py-3.5">
                <div class="text-[26px] font-extrabold tabular-nums">{{ $stats['items'] }}</div>
                <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Items tracked · {{ $stats['units'] }} units</div>
            </x-ui.card>
            <x-ui.card class="px-4 py-3.5">
                <div class="text-[26px] font-extrabold tabular-nums">{{ $stats['value']->format() }}</div>
                <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Estimated value</div>
            </x-ui.card>
            <x-ui.card class="px-4 py-3.5">
                <div class="text-[26px] font-extrabold tabular-nums">{{ $stats['places'] }}</div>
                <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Locations · {{ $stats['rooms'] }} rooms</div>
            </x-ui.card>
            <x-ui.card class="px-4 py-3.5">
                <div class="text-[26px] font-extrabold tabular-nums {{ $stats['attention'] > 0 ? 'text-bad' : '' }}">{{ $stats['attention'] }}</div>
                <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Need attention</div>
            </x-ui.card>
        </div>

        <div class="lg:grid lg:grid-cols-[1.55fr_1fr] lg:gap-[18px]">
            {{-- Left column --}}
            <div class="min-w-0">
                {{-- Inventory by category --}}
                <x-ui.card class="mb-6 px-5 pt-[18px] pb-5">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <div class="text-[13.5px] font-semibold text-ink-2">Your inventory</div>
                            <div class="mt-1 flex items-baseline gap-[7px]">
                                <span class="text-[38px] leading-none font-extrabold tracking-[-1px] tabular-nums">{{ $stats['items'] }}</span>
                                <span class="text-base font-semibold text-ink-2">items</span>
                            </div>
                        </div>
                        <div class="text-right text-xs font-semibold text-ink-3">{{ $this->categoryBars->count() }} categories</div>
                    </div>

                    <div class="mt-[18px] flex flex-col gap-[11px]">
                        @foreach ($this->categoryBars as $bar)
                            <div class="flex items-center gap-2.5" wire:key="bar-{{ $bar['label'] }}">
                                <span class="size-2 shrink-0 rounded-[3px]" style="background: {{ $bar['color'] ?? 'var(--ink-4)' }}"></span>
                                <span class="w-[86px] shrink-0 truncate text-[13.5px] font-semibold">{{ $bar['label'] }}</span>
                                <span class="h-2 flex-1 overflow-hidden rounded-[5px] bg-fill">
                                    <span class="block h-full rounded-[5px]"
                                        style="width: {{ $bar['count'] / $maxCount * 100 }}%; background: {{ $bar['color'] ?? 'var(--ink-4)' }}"></span>
                                </span>
                                <span class="w-5 shrink-0 text-right text-[13.5px] font-medium text-ink-2 tabular-nums">{{ $bar['count'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-[18px] flex items-stretch border-t border-line pt-[15px]">
                        <a href="{{ route('places.index') }}" wire:navigate class="flex flex-1 items-center gap-2">
                            <x-icon name="map-pin" :size="17" :stroke="1.8" class="text-ink-3" />
                            <span class="text-[13.5px] font-semibold whitespace-nowrap">Places</span>
                            <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $stats['places'] }}</span>
                            <x-icon name="chevron-right" :size="15" class="ml-auto text-ink-4" />
                        </a>
                        <span class="mx-3.5 w-px bg-line"></span>
                        <a href="{{ route('lending.index') }}" wire:navigate class="flex flex-1 items-center gap-2">
                            <x-icon name="hand" :size="17" :stroke="1.8" class="text-ink-3" />
                            <span class="text-[13.5px] font-semibold whitespace-nowrap">Lent out</span>
                            <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->activeLends->count() }}</span>
                            <x-icon name="chevron-right" :size="15" class="ml-auto text-ink-4" />
                        </a>
                    </div>
                </x-ui.card>

                {{-- Recently added --}}
                <div class="mb-3 flex items-center justify-between">
                    <x-ui.section-label>Recently added</x-ui.section-label>
                    <a href="{{ route('items.index') }}" wire:navigate class="text-[13px] font-bold text-accent">See all</a>
                </div>
                <div class="-mx-5 flex gap-3 overflow-x-auto px-5 pb-1 lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0"
                    style="scrollbar-width: none">
                    @foreach ($this->recent as $item)
                        <a href="{{ route('items.show', $item) }}" wire:navigate wire:key="rc-{{ $item->id }}"
                            class="w-[116px] shrink-0 lg:w-auto">
                            <x-ui.ph class="h-[92px] w-full rounded-[14px]" :icon="$item->category?->glyph ?? 'box'"
                                :tint="$item->category?->color" :icon-size="26" />
                            <div class="mt-[7px] truncate text-[13.5px] font-semibold">{{ $item->name }}</div>
                            <div class="truncate text-xs font-medium text-ink-3">{{ $item->place?->label ?? 'No location' }}</div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Right column --}}
            <div class="mt-6 min-w-0 lg:mt-0">
                {{-- Upcoming --}}
                <div class="mb-3 flex items-center justify-between">
                    <x-ui.section-label>Upcoming</x-ui.section-label>
                    <a href="{{ route('upkeep.index') }}" wire:navigate class="text-[13px] font-bold text-accent">
                        {{ $this->upcoming->where('late', true)->count() > 0 ? $this->upcoming->where('late', true)->count().' late →' : 'All →' }}
                    </a>
                </div>

                @if ($this->upcoming->isEmpty())
                    <x-ui.card class="mb-6 flex items-center gap-3 px-[18px] py-4">
                        <span class="flex size-[38px] items-center justify-center rounded-[11px] bg-good-soft text-good">
                            <x-icon name="check" :size="20" :stroke="2" />
                        </span>
                        <span>
                            <span class="block text-[15px] font-semibold">Nothing scheduled</span>
                            <span class="block text-[13.5px] font-medium text-ink-2">No upkeep or returns coming up.</span>
                        </span>
                    </x-ui.card>
                @else
                    {{-- Mobile: horizontal cards · Desktop: stacked list --}}
                    <div class="-mx-5 mb-6 flex gap-3 overflow-x-auto px-5 pb-1 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0"
                        style="scrollbar-width: none">
                        @foreach ($this->upcoming as $event)
                            <a href="{{ $event['route'] }}" wire:navigate wire:key="up-{{ $loop->index }}"
                                class="w-[150px] shrink-0 lg:w-auto">
                                <x-ui.card class="flex h-full flex-col gap-3 p-[15px] lg:flex-row lg:items-center lg:gap-3 lg:px-3.5 lg:py-2.5">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                        style="background: {{ $event['tone'] === 'neutral' ? 'var(--fill)' : 'var(--'.$event['tone'].'-soft)' }}; color: {{ $event['tone'] === 'neutral' ? 'var(--ink-3)' : 'var(--'.$event['tone'].')' }}">
                                        <x-icon :name="$event['icon']" :size="20" :stroke="1.9" />
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-[15px] font-semibold lg:text-[14px]">{{ $event['title'] }}</span>
                                    <span>
                                        <x-ui.pill :variant="$event['late'] ? 'bad' : ($event['tone'] === 'warn' ? 'warn' : 'default')">
                                            @unless ($event['late'])
                                                <x-icon name="clock" :size="11" :stroke="2" />
                                            @endunless
                                            {{ $event['meta'] }}
                                        </x-ui.pill>
                                    </span>
                                </x-ui.card>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Lent out (desktop rail) --}}
                @if ($this->activeLends->isNotEmpty())
                    <div class="hidden lg:block">
                        <x-ui.section-label class="mb-3">Lent out</x-ui.section-label>
                        <x-ui.card class="divide-y divide-line px-3.5">
                            @foreach ($this->activeLends as $lend)
                                <a href="{{ route('lending.index') }}" wire:navigate wire:key="lo-{{ $lend->id }}"
                                    class="flex items-center gap-3 py-2.5">
                                    <x-ui.ph class="size-9 rounded-[10px]" :icon="$lend->item->category?->glyph ?? 'box'"
                                        :tint="$lend->item->category?->color" :icon-size="16" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[14px] font-semibold">{{ $lend->item->name }}</span>
                                        <span class="block text-xs font-medium text-ink-3">{{ $lend->person }}</span>
                                    </span>
                                    @if ($lend->isOverdue())
                                        <x-ui.pill variant="bad">overdue</x-ui.pill>
                                    @elseif ($lend->due_date)
                                        <span class="text-xs font-semibold text-ink-3">{{ $lend->due_date->format('M j') }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </x-ui.card>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
