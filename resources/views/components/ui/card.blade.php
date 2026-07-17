@props(['flat' => false])

<div {{ $attributes->class([
    'rounded-card border border-line bg-surface lg:rounded-card-desk',
    'shadow-sm' => ! $flat,
]) }}>
    {{ $slot }}
</div>
