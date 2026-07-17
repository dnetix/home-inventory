@props(['icon', 'size' => 20, 'bare' => false, 'accent' => false])

<button type="button" {{ $attributes->class([
    'flex size-[38px] shrink-0 cursor-pointer items-center justify-center rounded-full transition active:scale-90',
    'border border-line bg-surface' => ! $bare,
    'border-transparent bg-transparent' => $bare,
    'text-accent' => $accent,
    'text-ink-2' => ! $accent,
]) }}>
    <x-icon :name="$icon" :size="$size" />
</button>
