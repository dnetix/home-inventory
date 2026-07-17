@props(['name' => null, 'checked' => false])

<label class="relative inline-block h-[30px] w-[50px] shrink-0 cursor-pointer">
    <input type="checkbox" @if ($name) name="{{ $name }}" @endif @checked($checked)
        {{ $attributes->merge(['class' => 'peer sr-only']) }}>
    <span class="absolute inset-0 rounded-full bg-line-2 transition-colors duration-200 peer-checked:bg-good"></span>
    <span
        class="absolute top-[3px] left-[3px] size-6 rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,0.25)] transition-transform duration-200 peer-checked:translate-x-5"></span>
</label>
