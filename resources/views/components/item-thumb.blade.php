@props(['item', 'iconSize' => 20])

@if ($item->photo_path)
    <img src="{{ $item->photoUrl() }}" alt="{{ $item->name }}" loading="lazy"
        {{ $attributes->class(['shrink-0 border border-line object-cover']) }}>
@else
    <x-ui.ph :class="$attributes->get('class')" :icon="$item->category?->glyph ?? 'box'"
        :tint="$item->category?->color" :icon-size="$iconSize" />
@endif
