@props(['on' => false, 'outline' => false, 'dot' => null])

<button type="button" {{ $attributes->class([
    'inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-[13px] font-semibold whitespace-nowrap transition',
    'border-transparent bg-accent text-on-accent' => $on,
    'border-line-2 bg-transparent text-ink' => ! $on && $outline,
    'border-transparent bg-fill text-ink-2' => ! $on && ! $outline,
]) }}>
    @if ($dot)
        <span class="size-2 rounded-[3px]" style="background: {{ $dot }}"></span>
    @endif
    {{ $slot }}
</button>
