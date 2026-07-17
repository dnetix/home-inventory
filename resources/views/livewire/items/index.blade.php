@php
    $missingMeta = \App\Livewire\Items\Index::MISSING_META;
@endphp

<div class="mx-auto flex w-full flex-1 flex-col lg:max-w-none">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Items</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">
                {{ $this->stats['count'] }} {{ Str::plural('item', $this->stats['count']) }} · {{ $this->stats['units'] }} units
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('find') }}" wire:navigate>
                <x-ui.icon-btn icon="search" />
            </a>
            <x-ui.icon-btn icon="sliders" :accent="$missing !== ''" wire:click="$set('filterOpen', true)" />
        </div>
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Items"
            :subtitle="$this->stats['count'] . ' ' . Str::plural('item', $this->stats['count']) . ' · ' . $this->stats['units'] . ' units'" />
    @endteleport

    @teleport('#topbar-actions')
        {{-- Single root: x-teleport only carries the first element --}}
        <div class="flex items-center gap-2">
            <x-ui.seg>
                <x-ui.seg-btn :on="$view === 'table'" wire:click="setView('table')">
                    <x-icon name="list" :size="15" /> Table
                </x-ui.seg-btn>
                <x-ui.seg-btn :on="$view === 'grid'" wire:click="setView('grid')">
                    <x-icon name="box" :size="15" /> Grid
                </x-ui.seg-btn>
            </x-ui.seg>
            <x-ui.icon-btn icon="sliders" :accent="$missing !== ''" wire:click="$set('filterOpen', true)" />
        </div>
    @endteleport

    {{-- Category chips --}}
    <div class="flex gap-2 overflow-x-auto px-5 py-3 lg:px-[30px]" style="scrollbar-width: none">
        <x-ui.chip :on="$cat === 'all'" :outline="$cat !== 'all'" wire:click="$set('cat', 'all')">All</x-ui.chip>
        @foreach ($this->categories as $category)
            <x-ui.chip :on="$cat === (string) $category->id" :outline="$cat !== (string) $category->id"
                :dot="$category->color" wire:click="$set('cat', '{{ $category->id }}')">
                {{ $category->label }}
            </x-ui.chip>
        @endforeach
    </div>

    {{-- Active data-quality filter banner --}}
    @if ($missing !== '' && isset($missingMeta[$missing]))
        <div class="mx-5 mb-3 flex items-center gap-2.5 rounded-xl bg-accent-soft px-3 py-2.5 text-accent-ink lg:mx-[30px]">
            <x-icon name="filter" :size="16" :stroke="1.9" />
            <span class="flex-1 text-[13.5px] font-bold">{{ $missingMeta[$missing]['label'] }} · {{ $this->items->total() }}</span>
            <button type="button" class="cursor-pointer text-[13.5px] font-bold" wire:click="setMissing('')">Clear</button>
        </div>
    @endif

    {{-- ══ Mobile: row list ══ --}}
    <div class="px-5 pb-6 lg:hidden">
        @if ($this->items->isNotEmpty())
            <x-ui.card class="divide-y divide-line px-4">
                @foreach ($this->items as $item)
                    <a href="{{ route('items.show', $item) }}" wire:navigate wire:key="m-{{ $item->id }}"
                        class="flex items-center gap-3 py-2.5">
                        <x-item-thumb class="size-[46px] rounded-[11px]" :item="$item" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[15px] font-semibold">{{ $item->name }}</div>
                            <div class="mt-0.5 truncate text-[12.5px] font-medium text-ink-3">
                                {{ $item->place_id ? implode(' › ', $this->placeIndex->breadcrumb($item->place_id)) : 'No location' }}
                            </div>
                        </div>
                        @if ($item->activeLend)
                            <x-ui.pill variant="bad">lent</x-ui.pill>
                        @endif
                        <span class="text-[13.5px] font-semibold text-ink-2 tabular-nums">{{ $item->value?->format() ?? '—' }}</span>
                        <x-icon name="chevron-right" :size="16" class="text-ink-4" />
                    </a>
                @endforeach
            </x-ui.card>
        @else
            <x-empty-state :icon="$missing !== '' ? 'check-circle' : 'box'"
                :title="$missing !== '' ? 'All clear' : 'Nothing here yet'"
                :sub="$missing !== '' ? $missingMeta[$missing]['empty'] : ($search !== '' ? 'No items match your search.' : 'Add your first item with the + button.')" />
        @endif

        <div class="mt-4">{{ $this->items->links() }}</div>
    </div>

    {{-- ══ Desktop: split table/grid + detail pane ══ --}}
    <div class="hidden min-h-0 flex-1 gap-[18px] px-[30px] pb-[30px] lg:flex">
        <div class="min-w-0 flex-1">
            @if ($this->items->isEmpty())
                <x-empty-state :icon="$missing !== '' ? 'check-circle' : 'box'"
                    :title="$missing !== '' ? 'All clear' : 'Nothing here yet'"
                    :sub="$missing !== '' ? $missingMeta[$missing]['empty'] : ($search !== '' ? 'No items match your search.' : 'Add your first item.')" />
            @elseif ($view === 'table')
                <x-ui.card class="overflow-hidden">
                    <table class="w-full text-left text-[13.5px]">
                        <thead>
                            <tr class="border-b border-line text-[12px] font-bold tracking-[0.4px] text-ink-3 uppercase">
                                @foreach (['name' => 'Item', 'category' => 'Category', 'location' => 'Location', 'value' => 'Value', 'status' => 'Status'] as $col => $label)
                                    <th class="px-4 py-3 {{ $col === 'value' ? 'text-right' : '' }}">
                                        <button type="button" wire:click="sortBy('{{ $col }}')"
                                            class="inline-flex cursor-pointer items-center gap-1 uppercase {{ $sort === $col ? 'text-ink' : '' }}">
                                            {{ $label }}
                                            @if ($sort === $col)
                                                <x-icon name="chevron-down" :size="13" :stroke="2.4"
                                                    class="{{ $dir === 'desc' ? 'rotate-180' : '' }} transition-transform" />
                                            @endif
                                        </button>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->items as $item)
                                <tr wire:key="t-{{ $item->id }}" wire:click="select({{ $item->id }})"
                                    class="cursor-pointer border-b border-line last:border-0 {{ $selected === $item->id ? 'bg-accent-soft/60' : 'hover:bg-fill' }}">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <x-item-thumb class="size-9 rounded-[9px]" :item="$item" :icon-size="16" />
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold">{{ $item->name }}</div>
                                                @if ($item->note)
                                                    <div class="truncate text-[12px] text-ink-3">{{ $item->note }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if ($item->category)
                                            <span class="inline-flex items-center gap-1.5 font-medium text-ink-2">
                                                <span class="size-2 rounded-[3px]" style="background: {{ $item->category->color }}"></span>
                                                {{ $item->category->label }}
                                            </span>
                                        @else
                                            <span class="text-ink-4">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-ink-2">
                                        @if ($item->place_id)
                                            @php $crumb = $this->placeIndex->breadcrumb($item->place_id); @endphp
                                            @foreach ($crumb as $part)
                                                @if (! $loop->first)<span class="text-ink-4"> › </span>@endif
                                                <span @class(['font-semibold text-ink' => $loop->last])>{{ $part }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-ink-4">No location</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-semibold tabular-nums">{{ $item->value?->format() ?? '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        @if ($item->activeLend)
                                            <x-ui.pill variant="bad">lent</x-ui.pill>
                                        @else
                                            <span class="text-[12.5px] font-medium text-ink-3">In place</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.card>
            @else
                <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($this->items as $item)
                        <x-ui.card wire:key="g-{{ $item->id }}" wire:click="select({{ $item->id }})"
                            class="cursor-pointer p-3 transition hover:shadow-md {{ $selected === $item->id ? 'ring-2 ring-accent' : '' }}">
                            <x-item-thumb class="h-[110px] w-full rounded-[10px]" :item="$item" :icon-size="30" />
                            <div class="mt-2.5 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="truncate text-[14px] font-semibold">{{ $item->name }}</div>
                                    <div class="mt-0.5 truncate text-[12px] font-medium text-ink-3">
                                        {{ $item->place_id ? implode(' › ', $this->placeIndex->breadcrumb($item->place_id)) : 'No location' }}
                                    </div>
                                </div>
                                @if ($item->activeLend)
                                    <x-ui.pill variant="bad">lent</x-ui.pill>
                                @endif
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif

            <div class="mt-4">{{ $this->items->links() }}</div>
        </div>

        @if ($this->selectedItem)
            <aside class="w-[388px] shrink-0">
                <x-ui.card class="sticky top-[26px] max-h-[calc(100dvh-56px)] overflow-y-auto p-4">
                    @include('livewire.items.partials.detail', ['item' => $this->selectedItem, 'pane' => true])
                </x-ui.card>
            </aside>
        @endif
    </div>

    {{-- Data-quality filter sheet --}}
    @if ($filterOpen)
        <x-ui.sheet title="Show items" close="closeFilter">
            <div class="flex flex-col">
                @foreach (['' => ['label' => 'All items', 'sub' => null], 'cat' => ['label' => 'Uncategorized', 'sub' => 'No category set'], 'place' => ['label' => 'No location', 'sub' => 'Not assigned to a place'], 'value' => ['label' => 'Unpriced', 'sub' => 'No value recorded']] as $key => $option)
                    <button type="button" wire:click="setMissing('{{ $key }}')"
                        class="flex cursor-pointer items-center gap-3 border-b border-line py-[13px] text-left last:border-0">
                        <div class="flex-1">
                            <div class="text-[15px] font-semibold">{{ $option['label'] }}</div>
                            @if ($option['sub'])
                                <div class="text-[12.5px] font-medium text-ink-3">{{ $option['sub'] }}</div>
                            @endif
                        </div>
                        @if ($missing === $key)
                            <x-icon name="check" :size="18" class="text-accent" />
                        @endif
                    </button>
                @endforeach
            </div>
        </x-ui.sheet>
    @endif

    {{-- Transfer sheet --}}
    @if ($this->transferItem)
        @include('livewire.items.partials.transfer-sheet')
    @endif
</div>
