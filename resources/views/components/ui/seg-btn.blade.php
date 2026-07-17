@props(['on' => false])

<button type="button" {{ $attributes->class([
    'inline-flex cursor-pointer items-center gap-[5px] rounded-lg px-[11px] py-[5px] text-[13.5px] font-semibold transition',
    'bg-surface text-ink shadow-sm' => $on,
    'text-ink-2' => ! $on,
]) }}>
    {{ $slot }}
</button>
