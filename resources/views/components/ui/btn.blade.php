@props(['variant' => 'default', 'size' => 'md', 'type' => 'button'])

<button type="{{ $type }}" {{ $attributes->class([
    'inline-flex cursor-pointer items-center justify-center gap-2 font-bold whitespace-nowrap transition active:scale-[0.975]',
    'h-[50px] rounded-btn px-[18px] text-base tracking-[-0.1px]' => $size === 'md',
    'h-10 rounded-[11px] px-3.5 text-[14.5px]' => $size === 'sm',
    'bg-accent text-on-accent shadow-[0_6px_16px_-8px_var(--accent)] active:bg-accent-press' => $variant === 'primary',
    'bg-accent-soft text-accent-ink' => $variant === 'tonal',
    'border border-line-2 bg-transparent text-ink' => $variant === 'ghost',
    'bg-fill text-ink' => $variant === 'default',
]) }}>
    {{ $slot }}
</button>
