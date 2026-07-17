@props(['label' => null, 'name', 'icon' => null, 'required' => false])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-[7px] block text-[12.5px] font-bold text-ink-2">
            {{ $label }}@if ($required)<span class="text-bad">*</span>@endif
        </label>
    @endif

    <div
        class="flex min-h-[50px] items-center gap-2.5 rounded-btn border border-line-2 bg-surface px-3.5 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
        @if ($icon)
            <x-icon :name="$icon" :size="19" class="shrink-0 text-ink-3" />
        @endif

        <input id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge([
            'class' => 'w-full bg-transparent py-[13px] text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3',
        ]) }}>

        {{ $trailing ?? '' }}
    </div>

    @error($name)
        <p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>
    @enderror
</div>
