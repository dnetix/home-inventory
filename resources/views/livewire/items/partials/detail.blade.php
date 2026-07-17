{{-- Shared item detail content: desktop pane ($pane = true) and mobile show screen. --}}
@php
    $crumb = $item->place_id ? $this->placeIndex->breadcrumb($item->place_id) : [];
    $lend = $item->activeLend;
@endphp

<div>
    @if ($pane)
        <div class="mb-3 flex items-center justify-between">
            <x-ui.section-label>Item details</x-ui.section-label>
            <div class="flex items-center gap-1">
                <a href="{{ route('items.edit', $item) }}" wire:navigate>
                    <x-ui.icon-btn icon="edit" :size="16" bare />
                </a>
                <x-ui.icon-btn icon="x" :size="16" bare wire:click="$set('selected', null)" />
            </div>
        </div>
    @endif

    @if ($item->photo_path)
        <img src="{{ $item->photoUrl() }}" alt="{{ $item->name }}"
            class="h-[188px] w-full rounded-[18px] border border-line object-cover">
    @else
        <a href="{{ route('items.edit', $item) }}" wire:navigate class="block">
            <x-ui.ph class="h-[188px] w-full rounded-[18px]" :icon="$item->category?->glyph ?? 'box'"
                :tint="$item->category?->color" :icon-size="52" label="tap to add a photo" />
        </a>
    @endif

    <div class="mt-4 flex items-start gap-3">
        <div class="min-w-0 flex-1">
            <div class="text-[22px] font-bold tracking-[-0.3px]">{{ $item->name }}</div>
            <div class="mt-[3px] text-[13.5px] font-medium text-ink-3">
                {{ $item->note ?: ($item->category?->label ?? 'Uncategorized') }}
            </div>
        </div>
        <div class="text-[22px] font-bold tabular-nums">{{ $item->value?->format() ?? '—' }}</div>
    </div>

    @if ($item->tags->isNotEmpty())
        <div class="mt-3.5 flex flex-wrap gap-[7px]">
            @foreach ($item->tags as $tag)
                <x-ui.chip outline :dot="$tag->color" class="cursor-default">{{ $tag->label }}</x-ui.chip>
            @endforeach
        </div>
    @endif

    @if ($lend)
        <div @class([
            'mt-4 flex items-center gap-3 rounded-[14px] px-3.5 py-3',
            'bg-bad-soft text-bad' => $lend->isOverdue(),
            'bg-accent-soft text-accent-ink' => ! $lend->isOverdue(),
        ])>
            <x-icon name="hand" :size="20" :stroke="1.9" />
            <div class="flex-1">
                <div class="text-[13.5px] font-bold">Lent to {{ $lend->person }}</div>
                <div class="mt-px text-[12px] font-semibold opacity-80">
                    @if ($lend->due_date)
                        Due back {{ $lend->due_date->format('M j') }}{{ $lend->isOverdue() ? ' · overdue' : '' }}
                    @else
                        No due date
                    @endif
                </div>
            </div>
            <button type="button" class="cursor-pointer text-[13.5px] font-bold underline"
                wire:click="returnLend({{ $lend->id }})">Return</button>
        </div>
    @endif

    <x-ui.card flat class="mt-4 divide-y divide-line px-4">
        <div class="flex items-center gap-3 py-[13px]">
            <x-icon name="map-pin" :size="18" class="shrink-0 text-ink-3" />
            <span class="w-[82px] shrink-0 text-[13.5px] font-semibold text-ink-2">Location</span>
            <span class="flex-1 text-right text-[13.5px] font-semibold">
                @if ($crumb !== [])
                    <a href="{{ route('places.show', $item->place_id) }}" wire:navigate class="text-accent">
                        {{ implode(' › ', $crumb) }}
                    </a>
                @else
                    <span class="text-ink-4">No location</span>
                @endif
            </span>
        </div>
        <div class="flex items-center gap-3 py-[13px]">
            <x-icon name="layers" :size="18" class="shrink-0 text-ink-3" />
            <span class="w-[82px] shrink-0 text-[13.5px] font-semibold text-ink-2">Category</span>
            <span class="flex-1 text-right text-[13.5px] font-semibold">
                @if ($item->category)
                    {{ $item->category->parent ? $item->category->parent->label.' › ' : '' }}{{ $item->category->label }}
                @else
                    <span class="text-ink-4">Uncategorized</span>
                @endif
            </span>
        </div>
        <div class="flex items-center gap-3 py-[13px]">
            <x-icon name="hash" :size="18" class="shrink-0 text-ink-3" />
            <span class="w-[82px] shrink-0 text-[13.5px] font-semibold text-ink-2">Quantity</span>
            <span class="flex-1 text-right text-[13.5px] font-semibold tabular-nums">{{ $item->qty }}</span>
        </div>
        @if ($item->dim)
            <div class="flex items-center gap-3 py-[13px]">
                <x-icon name="cube" :size="18" class="shrink-0 text-ink-3" />
                <span class="w-[82px] shrink-0 text-[13.5px] font-semibold text-ink-2">Size</span>
                <span class="flex flex-1 flex-wrap items-center justify-end gap-x-2 text-right text-[13.5px] font-semibold">
                    <span class="whitespace-nowrap">{{ $this->units->dim($item->dim) }}</span>
                    <span class="whitespace-nowrap text-ink-3">{{ $this->units->volume($item->totalVolumeLitres()) }}</span>
                </span>
            </div>
        @endif
        <div class="flex items-center gap-3 py-[13px]">
            <x-icon name="shield" :size="18" class="shrink-0 text-ink-3" />
            <span class="w-[82px] shrink-0 text-[13.5px] font-semibold text-ink-2">Warranty</span>
            <span class="flex-1 text-right text-[13.5px] font-semibold">
                {{ $item->tags->contains('label', 'warranty') ? 'Active' : '—' }}
            </span>
        </div>
    </x-ui.card>

    <div class="mt-4 flex gap-2.5">
        <x-ui.btn variant="ghost" size="sm" class="flex-1" wire:click="startTransfer({{ $item->id }})">
            <x-icon name="map-pin" :size="16" /> Transfer
        </x-ui.btn>
        <x-ui.btn variant="primary" size="sm" class="flex-1 opacity-50" disabled title="Upkeep arrives in the next phase">
            <x-icon name="wrench" :size="16" /> Upkeep
        </x-ui.btn>
    </div>
</div>
