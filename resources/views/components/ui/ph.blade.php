@props(['icon' => 'box', 'tint' => null, 'iconSize' => 20, 'label' => null])

<div {{ $attributes->class(['flex shrink-0 flex-col items-center justify-center gap-1.5 overflow-hidden border border-line']) }}
    style="background: repeating-linear-gradient(135deg, var(--fill) 0 9px, var(--fill-2) 9px 18px); color: {{ $tint ?? 'var(--ink-4)' }}">
    <x-icon :name="$icon" :size="$iconSize" class="opacity-80" />
    @if ($label)
        <span class="text-[11px] font-semibold text-ink-3">{{ $label }}</span>
    @endif
</div>
