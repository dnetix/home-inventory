@php
    $children = $this->tree->childrenOf($place->id);
    $isOpen = in_array($place->id, $open, true);
    $fill = $this->tree->fill($place->id);
    $pct = $fill->percent();
    $barColor = $fill->isOverCapacity() ? 'var(--bad)' : (($pct ?? 0) > 80 ? 'var(--warn)' : 'var(--good)');
@endphp

<div wire:key="tr-{{ $place->id }}" class="flex items-center gap-1 py-[9px] pr-1 {{ $first ?? false ? '' : 'border-t border-line' }}"
    style="padding-left: {{ 4 + $depth * 20 }}px">
    @if ($children->isNotEmpty())
        <button type="button" wire:click="toggle({{ $place->id }})"
            class="flex size-6 shrink-0 cursor-pointer items-center justify-center text-ink-2">
            <x-icon :name="$isOpen ? 'chevron-down' : 'chevron-right'" :size="16" :stroke="2.2" />
        </button>
    @else
        <span class="w-6 shrink-0"></span>
    @endif

    <a href="{{ route('places.show', $place) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-[11px]">
        <span @class([
            'flex size-9 shrink-0 items-center justify-center rounded-[10px]',
            'bg-accent-soft text-accent-ink' => $children->isNotEmpty(),
            'bg-fill text-ink-2' => $children->isEmpty(),
        ])>
            <x-icon :name="$place->glyph ?: 'box'" :size="18" :stroke="1.8" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-[15px] font-semibold">{{ $place->label }}</span>
            <span class="mt-0.5 block truncate text-xs font-semibold text-ink-3">
                {{ $fill->totalCount }} {{ Str::plural('item', $fill->totalCount) }}
                @if ($pct !== null)
                    · <span style="color: {{ $barColor }}" class="font-bold">{{ round($pct) }}% full</span>
                    · {{ $this->units->volume($fill->capacityLitres) }}
                @else
                    · No size set
                @endif
            </span>
            @if ($pct !== null)
                <span class="mt-1.5 block h-1 max-w-[190px] overflow-hidden rounded-sm bg-fill">
                    <span class="block h-full rounded-sm" style="width: {{ min(100, $pct) }}%; background: {{ $barColor }}"></span>
                </span>
            @endif
        </span>
        <x-icon name="chevron-right" :size="16" class="shrink-0 text-ink-4" />
    </a>
</div>

@if ($children->isNotEmpty() && $isOpen)
    @foreach ($children as $child)
        @include('livewire.places.partials.tree-row', ['place' => $child, 'depth' => $depth + 1, 'first' => false])
    @endforeach
@endif
