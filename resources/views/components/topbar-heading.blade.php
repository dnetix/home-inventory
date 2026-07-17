@props(['title', 'subtitle' => null])

<div class="min-w-0">
    <h1 class="truncate text-[19px] leading-[1.15] font-extrabold tracking-[-0.3px]">{{ $title }}</h1>
    @if ($subtitle)
        <p class="truncate text-[12px] font-medium text-ink-2">{{ $subtitle }}</p>
    @endif
</div>
