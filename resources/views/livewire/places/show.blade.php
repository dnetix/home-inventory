@php
    $fill = $this->tree->fill($place->id);
    $pct = $fill->percent();
    $barColor = $fill->isOverCapacity() ? 'var(--bad)' : (($pct ?? 0) > 80 ? 'var(--warn)' : 'var(--good)');
    $children = $this->tree->childrenOf($place->id);
    $directItems = $this->tree->itemsIn($place->id);
    $allItems = $this->tree->itemsUnder($place->id);
    $crumb = $this->tree->breadcrumb($place->id);
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('places.index') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="flex-1"></span>
        <x-ui.icon-btn icon="edit" :size="17" wire:click="openEdit" />
    </div>

    <div class="flex-1 px-5 pb-6 lg:px-[30px]">
        {{-- Breadcrumb --}}
        <div class="mb-[5px] flex items-center gap-[5px] text-xs font-semibold text-ink-3">
            <x-icon name="home" :size="13" :stroke="1.9" />
            @foreach ($crumb as $part)
                @if (! $loop->first)<span>›</span>@endif
                <span @class(['font-bold text-ink-2' => $loop->last])>{{ $part }}</span>
            @endforeach
        </div>

        {{-- Title --}}
        <div class="mb-[18px] flex items-start gap-3">
            <div class="flex-1">
                <h1 class="text-[22px] font-bold tracking-[-0.3px]">{{ $place->label }}</h1>
                <p class="mt-[3px] text-[13.5px] font-medium text-ink-3">
                    {{ $allItems->count() }} {{ Str::plural('item', $allItems->count()) }}{{ $children->isNotEmpty() ? ' · '.$children->count().' sub-'.Str::plural('location', $children->count()) : '' }}
                </p>
                @if ($place->description)
                    <p class="mt-2 text-[13.5px] font-medium text-ink-2">{{ $place->description }}</p>
                @endif
            </div>
        </div>

        {{-- Space / capacity --}}
        <x-ui.card class="mb-[22px] px-4 pt-[15px] pb-4">
            <div class="flex items-center gap-2.5 {{ $pct !== null ? 'mb-[13px]' : 'mb-1' }}">
                <x-icon name="cube" :size="18" :stroke="1.8" class="text-ink-2" />
                <span class="flex-1 text-base font-semibold">Space</span>
                @if ($place->dim)
                    <span class="text-xs font-semibold whitespace-nowrap text-ink-3 tabular-nums">{{ $this->units->dim($place->dim) }}</span>
                @endif
            </div>
            @if ($pct !== null)
                <div class="flex items-baseline gap-[7px]">
                    <span class="text-[22px] font-bold tabular-nums">{{ round($pct) }}%</span>
                    <span class="text-[13.5px] font-medium text-ink-2">full</span>
                    <span class="ml-auto text-[13.5px] font-medium whitespace-nowrap text-ink-3 tabular-nums">
                        {{ $this->units->volume($fill->usedLitres) }} of {{ $this->units->volume($fill->capacityLitres) }}
                    </span>
                </div>
                <div class="mt-2.5 h-2.5 overflow-hidden rounded-md bg-fill">
                    <div class="h-full rounded-md" style="width: {{ min(100, $pct) }}%; background: {{ $barColor }}"></div>
                </div>
                <div class="mt-[9px] text-xs font-semibold text-ink-3">
                    {{ $fill->measuredCount }} of {{ $fill->totalCount }} items measured{{ $fill->isOverCapacity() ? ' · over capacity' : '' }}
                </div>
            @else
                <div class="mb-1 text-[13.5px] font-medium text-ink-3">No size set for this location yet.</div>
            @endif
        </x-ui.card>

        {{-- Sub-locations --}}
        <div class="mb-3 flex items-center justify-between">
            <x-ui.section-label>Sub-locations</x-ui.section-label>
            <button type="button" wire:click="openAddChild" class="cursor-pointer text-[13px] font-bold text-accent">+ Add</button>
        </div>
        @if ($children->isNotEmpty())
            <div class="mb-[22px] grid grid-cols-2 gap-2.5">
                @foreach ($children as $child)
                    @php
                        $childFill = $this->tree->fill($child->id);
                        $childPct = $childFill->percent();
                    @endphp
                    <a href="{{ route('places.show', $child) }}" wire:navigate wire:key="sub-{{ $child->id }}">
                        <x-ui.card class="flex items-center gap-2.5 px-3 py-2.5 transition active:scale-[0.98]">
                            <x-ui.ph class="size-9 rounded-[10px]" :icon="$child->glyph ?: 'box'" :icon-size="16" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13.5px] font-semibold">{{ $child->label }}</span>
                                <span class="block text-xs font-semibold text-ink-3">
                                    {{ $childFill->totalCount }} items{{ $childPct !== null ? ' · '.round($childPct).'%' : '' }}
                                </span>
                                @if ($childPct !== null)
                                    <span class="mt-1.5 block h-1 overflow-hidden rounded-sm bg-fill">
                                        <span class="block h-full rounded-sm"
                                            style="width: {{ min(100, $childPct) }}%; background: {{ $childFill->isOverCapacity() ? 'var(--bad)' : ($childPct > 80 ? 'var(--warn)' : 'var(--good)') }}"></span>
                                    </span>
                                @endif
                            </span>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        @else
            <p class="mb-[22px] text-[13.5px] font-medium text-ink-3">Nothing nested here yet.</p>
        @endif

        {{-- Items here --}}
        <x-ui.section-label class="mb-3">Items here</x-ui.section-label>
        <x-ui.card class="divide-y divide-line px-3.5">
            @forelse (($directItems->isNotEmpty() ? $directItems : $allItems)->take(6) as $item)
                <a href="{{ route('items.show', $item) }}" wire:navigate wire:key="pi-{{ $item->id }}"
                    class="flex items-center gap-3 py-2.5">
                    <x-item-thumb class="size-[42px] rounded-[10px]" :item="$item" :icon-size="18" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[14px] font-semibold">{{ $item->name }}</div>
                        <div class="mt-0.5 truncate text-xs font-medium text-ink-3">
                            {{ $item->place_id ? implode(' › ', $this->tree->breadcrumb($item->place_id)) : '' }}
                        </div>
                    </div>
                    <x-icon name="chevron-right" :size="16" class="text-ink-4" />
                </a>
            @empty
                <div class="py-4 text-center text-[13.5px] font-medium text-ink-3">No items here yet.</div>
            @endforelse
        </x-ui.card>
    </div>

    {{-- Edit / add-child sheet --}}
    @if ($editor !== '')
        @include('livewire.places.partials.editor-sheet', [
            'title' => $editor === 'edit' ? 'Edit location' : 'New sub-location',
            'cta' => $editor === 'edit' ? 'Save changes' : 'Add location',
            'deletable' => $editor === 'edit',
        ])
    @endif
</div>
