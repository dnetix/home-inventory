<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col lg:mx-0 lg:max-w-none">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Places</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">
                {{ $this->stats['places'] }} locations · {{ $this->stats['items'] }} items
            </p>
        </div>
        <x-ui.icon-btn icon="plus" accent wire:click="openEditor" />
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Places"
            :subtitle="$this->stats['places'] . ' locations · ' . $this->stats['items'] . ' items'" />
    @endteleport

    @teleport('#topbar-actions')
        <x-ui.btn variant="tonal" size="sm" wire:click="openEditor">
            <x-icon name="plus" :size="16" /> Add location
        </x-ui.btn>
    @endteleport

    <div class="flex flex-1 gap-[18px] px-5 pt-1 pb-6 lg:px-[30px] lg:pt-4 lg:pb-[30px]">
        <div class="min-w-0 flex-1">
            <div class="mb-3 flex items-center justify-between">
                <x-ui.section-label>Rooms</x-ui.section-label>
                <button type="button" wire:click="toggleAll" class="cursor-pointer text-[13px] font-bold text-accent">
                    Expand / collapse all
                </button>
            </div>

            @if ($this->tree->roots()->isEmpty())
                <x-empty-state icon="map-pin" title="No places yet" sub="Add your first room with the + button." />
            @else
                <x-ui.card class="px-2.5 py-0.5">
                    @foreach ($this->tree->roots() as $root)
                        @include('livewire.places.partials.tree-row', ['place' => $root, 'depth' => 0, 'first' => $loop->first])
                    @endforeach
                </x-ui.card>
            @endif
        </div>

        {{-- Desktop summary rail --}}
        @php
            $storage = $this->summary['storage'];
            $storagePct = $storage->percent();
        @endphp
        <aside class="hidden w-[320px] shrink-0 flex-col gap-3.5 lg:flex">
            <div class="grid grid-cols-2 gap-3.5">
                <x-ui.card class="px-4 py-3.5">
                    <div class="text-[26px] font-extrabold tabular-nums">{{ $this->stats['places'] }}</div>
                    <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">
                        Locations · {{ $this->summary['rooms'] }} {{ Str::plural('room', $this->summary['rooms']) }}
                    </div>
                </x-ui.card>
                <a href="{{ route('items.index', ['missing' => 'place']) }}" wire:navigate>
                    <x-ui.card class="px-4 py-3.5 transition hover:shadow-md">
                        <div class="text-[26px] font-extrabold tabular-nums {{ $this->summary['unplaced'] > 0 ? 'text-warn' : '' }}">
                            {{ $this->summary['unplaced'] }}
                        </div>
                        <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Unplaced items</div>
                    </x-ui.card>
                </a>
            </div>

            <x-ui.card class="px-4 py-3.5">
                <x-ui.section-label>Total storage</x-ui.section-label>
                @if ($storage->capacityLitres !== null)
                    @php $storageColor = $storage->isOverCapacity() ? 'var(--bad)' : ($storagePct > 80 ? 'var(--warn)' : 'var(--good)'); @endphp
                    <div class="mt-1.5 text-[22px] font-bold tabular-nums">{{ $this->units->volume($storage->capacityLitres) }}</div>
                    <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">
                        {{ $this->units->volume($storage->usedLitres) }} used · <span style="color: {{ $storageColor }}" class="font-bold">{{ round($storagePct) }}% full</span>
                    </div>
                    <div class="mt-2.5 h-1.5 overflow-hidden rounded-sm bg-fill">
                        <div class="h-full rounded-sm" style="width: {{ min(100, $storagePct) }}%; background: {{ $storageColor }}"></div>
                    </div>
                @else
                    <div class="mt-1.5 text-[13.5px] font-medium text-ink-3">
                        Set interior sizes on your places to track how full they are.
                    </div>
                @endif
            </x-ui.card>

            @if ($this->summary['fullest']->isNotEmpty())
                <x-ui.card class="px-4 py-3.5">
                    <x-ui.section-label class="mb-2.5">Fullest spots</x-ui.section-label>
                    <div class="flex flex-col gap-3">
                        @foreach ($this->summary['fullest'] as $row)
                            @php
                                $pct = $row['fill']->percent();
                                $color = $row['fill']->isOverCapacity() ? 'var(--bad)' : ($pct > 80 ? 'var(--warn)' : 'var(--good)');
                            @endphp
                            <a href="{{ route('places.show', $row['place']) }}" wire:navigate class="block"
                                wire:key="full-{{ $row['place']->id }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-[13.5px] font-semibold">{{ $row['place']->label }}</span>
                                    <span class="shrink-0 text-[13px] font-bold tabular-nums" style="color: {{ $color }}">
                                        {{ round($pct) }}% · {{ $this->units->volume($row['fill']->capacityLitres) }}
                                    </span>
                                </div>
                                <div class="mt-1.5 h-1 overflow-hidden rounded-sm bg-fill">
                                    <div class="h-full rounded-sm" style="width: {{ min(100, $pct) }}%; background: {{ $color }}"></div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            {{-- How volumes read --}}
            <x-ui.card class="flex gap-3 px-4 py-3.5">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] bg-accent-soft text-accent-ink">
                    <x-icon name="cube" :size="18" :stroke="1.85" />
                </span>
                <p class="text-[12.5px] leading-relaxed font-medium text-ink-2">
                    Capacity comes from each place's interior size.
                    @if ($this->units->unit === \App\Enums\Unit::Imperial)
                        Volumes under 1 ft³ are shown in cubic inches; larger spaces switch to cubic feet.
                    @else
                        Volumes under 1,000 L are shown in litres; larger spaces switch to cubic metres (1 m³ = 1,000 L).
                    @endif
                </p>
            </x-ui.card>
        </aside>
    </div>

    {{-- Create sheet --}}
    @if ($editorOpen)
        @include('livewire.places.partials.editor-sheet', ['title' => 'New location', 'cta' => 'Add location', 'deletable' => false])
    @endif
</div>
