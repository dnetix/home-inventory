@props(['item', 'iconSize' => 20])

@if ($item->photo_path)
    {{-- Thumbnails may not exist yet for photos stored before they did —
         onerror swaps in the full-size original exactly once. --}}
    <img src="{{ $item->photoThumbUrl() }}" alt="{{ $item->name }}" loading="lazy"
        data-full="{{ $item->photoUrl() }}"
        onerror="if (this.src !== this.dataset.full) this.src = this.dataset.full"
        {{ $attributes->class(['shrink-0 border border-line object-cover']) }}>
@else
    <x-ui.ph :class="$attributes->get('class')" :icon="$item->category?->glyph ?? 'box'"
        :tint="$item->category?->color" :icon-size="$iconSize" />
@endif
