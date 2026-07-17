@props(['icon' => 'box', 'title', 'sub' => null])

<div class="px-8 py-14 text-center text-ink-3">
    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-[20px] bg-fill">
        <x-icon :name="$icon" :size="28" />
    </div>
    <div class="text-base font-semibold text-ink">{{ $title }}</div>
    @if ($sub)
        <div class="mx-auto mt-1 max-w-xs text-[13.5px] font-medium">{{ $sub }}</div>
    @endif
    {{ $slot }}
</div>
