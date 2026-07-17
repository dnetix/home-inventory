@props(['variant' => 'default'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-[9px] py-[3px] text-[11.5px] font-bold tracking-[0.1px] whitespace-nowrap',
    'bg-fill text-ink-2' => $variant === 'default',
    'bg-warn-soft text-warn' => $variant === 'warn',
    'bg-bad-soft text-bad' => $variant === 'bad',
    'bg-good-soft text-good' => $variant === 'good',
    'bg-accent-soft text-accent-ink' => $variant === 'accent',
]) }}>
    {{ $slot }}
</span>
