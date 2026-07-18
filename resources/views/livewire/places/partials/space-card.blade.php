{{-- Space / capacity card. Expects $place, $fill, $pct, $barColor from the parent view. --}}
<x-ui.card class="px-4 pt-[15px] pb-4">
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
