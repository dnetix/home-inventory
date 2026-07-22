@php
    $missingMeta = \App\Livewire\Items\Index::MISSING_META;
    $statusFilters = \App\Livewire\Items\Index::STATUS_FILTERS;
    $filtering = $missing !== '' || $status !== '' || $tag !== null || $cat !== null;
@endphp

<div class="mx-auto flex w-full flex-1 flex-col lg:max-w-none" x-data="itemSelection($wire.entangle('selectedIds'))"
    x-on:scroll.window.passive="document.activeElement?.type === 'search' && document.activeElement.blur()">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Items</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">
                {{ $this->stats['count'] }} {{ Str::plural('item', $this->stats['count']) }} · {{ $this->stats['units'] }} units
            </p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.icon-btn icon="check-circle" :accent="$selecting" wire:click="toggleSelecting" />
            <x-ui.icon-btn icon="sliders" :accent="$filtering" wire:click="$set('filterOpen', true)" />
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
            <x-ui.icon-btn icon="check-circle" :accent="$selecting" wire:click="toggleSelecting" />
            <x-ui.icon-btn icon="sliders" :accent="$filtering" wire:click="$set('filterOpen', true)" />
        </div>
    @endteleport

    {{-- Search (mobile — pinned; the desktop input teleports into the top bar) --}}
    <div class="sticky top-0 z-30 bg-screen px-5 py-3 lg:hidden">
        @include('livewire.items.partials.search-input')
    </div>

    @teleport('#topbar-search')
        @include('livewire.items.partials.search-input')
    @endteleport

    {{-- Active filter banner (data-quality + status) --}}
    @if ($filtering)
        @php
            $filterLabels = array_filter([
                $cat !== null ? $this->categories->firstWhere('id', $cat)?->label : null,
                $missingMeta[$missing]['label'] ?? null,
                $statusFilters[$status] ?? null,
                $tag !== null ? '#'.$this->tags->firstWhere('id', $tag)?->label : null,
            ]);
        @endphp
        <div class="mx-5 mb-3 flex items-center gap-2.5 rounded-xl bg-accent-soft px-3 py-2.5 text-accent-ink lg:mx-[30px] lg:mt-[18px] lg:-mb-1">
            <x-icon name="filter" :size="16" :stroke="1.9" />
            <span class="flex-1 text-[13.5px] font-bold">{{ implode(' · ', $filterLabels) }} · {{ $this->items->total() }}</span>
            <button type="button" class="cursor-pointer text-[13.5px] font-bold" wire:click="clearFilters">Clear</button>
        </div>
    @endif

    {{-- ══ Mobile: row list ══ --}}
    <div class="px-5 pb-6 lg:hidden">
        @if ($this->items->isNotEmpty())
            <x-ui.card class="divide-y divide-line px-4">
                @foreach ($this->items as $item)
                    <a href="{{ route('items.show', $item) }}" wire:navigate wire:key="m-{{ $item->id }}"
                        @if ($selecting) x-on:click.prevent="toggle({{ $item->id }})" @endif
                        class="flex items-center gap-3 py-2.5">
                        @if ($selecting)
                            <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full border transition"
                                x-bind:class="has({{ $item->id }}) ? 'border-accent bg-accent text-on-accent' : 'border-line-2'">
                                <x-icon name="check" :size="13" :stroke="2.5" x-show="has({{ $item->id }})" x-cloak />
                            </span>
                        @endif
                        <x-item-thumb class="size-[46px] rounded-[11px]" :item="$item" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[15px] font-semibold">{{ $item->name }}@if ($item->qty > 1)<span class="font-medium text-ink-3"> ×{{ $item->qty }}</span>@endif</div>
                            <div class="mt-0.5 truncate text-[12.5px] font-medium text-ink-3">
                                {{ $item->place_id ? implode(' › ', $this->placeIndex->breadcrumb($item->place_id)) : 'No location' }}
                            </div>
                            @if ($showTags && $item->tags->isNotEmpty())
                                <div class="mt-1">@include('livewire.items.partials.tag-chips')</div>
                            @endif
                        </div>
                        @if ($item->status->pillVariant())
                            <x-ui.pill :variant="$item->status->pillVariant()">{{ strtolower($item->status->label()) }}</x-ui.pill>
                        @elseif ($item->activeLend)
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
    <div class="hidden min-h-0 flex-1 gap-[18px] px-[30px] pb-[30px] lg:flex lg:pt-[18px]">
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
                                @if ($selecting)
                                    <th class="w-10 pl-4"></th>
                                @endif
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
                                @if ($showTags)
                                    <th class="px-4 py-3 uppercase">Tags</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->items as $item)
                                <tr wire:key="t-{{ $item->id }}"
                                    @if ($selecting)
                                        x-on:click="toggle({{ $item->id }})"
                                        x-bind:class="has({{ $item->id }}) ? 'bg-accent-soft/60' : 'hover:bg-fill'"
                                        class="cursor-pointer border-b border-line last:border-0"
                                    @else
                                        wire:click="select({{ $item->id }})"
                                        class="cursor-pointer border-b border-line last:border-0 {{ $selected === $item->id ? 'bg-accent-soft/60' : 'hover:bg-fill' }}"
                                    @endif>
                                    @if ($selecting)
                                        <td class="py-2.5 pl-4">
                                            <span class="flex size-[20px] items-center justify-center rounded-full border transition"
                                                x-bind:class="has({{ $item->id }}) ? 'border-accent bg-accent text-on-accent' : 'border-line-2'">
                                                <x-icon name="check" :size="12" :stroke="2.5" x-show="has({{ $item->id }})" x-cloak />
                                            </span>
                                        </td>
                                    @endif
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <x-item-thumb class="size-9 rounded-[9px]" :item="$item" :icon-size="16" />
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold">{{ $item->name }}@if ($item->qty > 1)<span class="font-medium text-ink-3"> ×{{ $item->qty }}</span>@endif</div>
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
                                        @if ($item->status->pillVariant())
                                            <x-ui.pill :variant="$item->status->pillVariant()">{{ strtolower($item->status->label()) }}</x-ui.pill>
                                        @elseif ($item->activeLend)
                                            <x-ui.pill variant="bad">lent</x-ui.pill>
                                        @else
                                            <span class="text-[12.5px] font-medium text-ink-3">In place</span>
                                        @endif
                                    </td>
                                    @if ($showTags)
                                        <td class="px-4 py-2.5">
                                            @if ($item->tags->isNotEmpty())
                                                @include('livewire.items.partials.tag-chips')
                                            @else
                                                <span class="text-ink-4">—</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.card>
            @else
                <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($this->items as $item)
                        @php
                            $gridRing = $selecting ? "has($item->id) && 'ring-2 ring-accent'" : "''";
                        @endphp
                        <x-ui.card wire:key="g-{{ $item->id }}"
                            x-on:click="{{ $selecting ? 'toggle('.$item->id.')' : '$wire.select('.$item->id.')' }}"
                            x-bind:class="{{ $gridRing }}"
                            class="cursor-pointer p-3 transition hover:shadow-md {{ ! $selecting && $selected === $item->id ? 'ring-2 ring-accent' : '' }}">
                            <x-item-thumb class="h-[110px] w-full rounded-[10px]" :item="$item" :icon-size="30" />
                            <div class="mt-2.5 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="truncate text-[14px] font-semibold">{{ $item->name }}@if ($item->qty > 1)<span class="font-medium text-ink-3"> ×{{ $item->qty }}</span>@endif</div>
                                    <div class="mt-0.5 truncate text-[12px] font-medium text-ink-3">
                                        {{ $item->place_id ? implode(' › ', $this->placeIndex->breadcrumb($item->place_id)) : 'No location' }}
                                    </div>
                                </div>
                                @if ($item->status->pillVariant())
                                    <x-ui.pill :variant="$item->status->pillVariant()">{{ strtolower($item->status->label()) }}</x-ui.pill>
                                @elseif ($item->activeLend)
                                    <x-ui.pill variant="bad">lent</x-ui.pill>
                                @endif
                            </div>
                            @if ($showTags && $item->tags->isNotEmpty())
                                <div class="mt-1.5">@include('livewire.items.partials.tag-chips')</div>
                            @endif
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

    {{-- Filter sheet: data-quality + status --}}
    @if ($filterOpen)
        <x-ui.sheet title="Show items" close="closeFilter">
            <x-ui.section-label class="mb-2">Category</x-ui.section-label>
            <x-category-combobox :categories="$this->categories" property="cat" null-label="All categories" live />

            <x-ui.section-label class="mt-5 mb-1">Tags</x-ui.section-label>
            <div class="flex items-center gap-3 border-b border-line py-[13px]">
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Show tags</span>
                    <span class="block text-[12.5px] font-medium text-ink-3">Display each item's tags on rows and cards</span>
                </span>
                <x-ui.switch :checked="$showTags" wire:model.live="showTags" />
            </div>
            @if ($this->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 py-3">
                    <x-ui.chip :on="$tag === null" :outline="$tag !== null" wire:click="setTagFilter('')">Any tag</x-ui.chip>
                    @foreach ($this->tags as $option)
                        <x-ui.chip wire:key="ft-{{ $option->id }}" :dot="$option->color"
                            :on="$tag === $option->id" :outline="$tag !== $option->id"
                            wire:click="setTagFilter({{ $option->id }})">
                            {{ $option->label }}
                        </x-ui.chip>
                    @endforeach
                </div>
            @endif

            <x-ui.section-label class="mt-5 mb-1">Data quality</x-ui.section-label>
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

            <x-ui.section-label class="mt-5 mb-1">Status</x-ui.section-label>
            <div class="flex flex-col">
                @foreach (['' => 'Any status', ...$statusFilters] as $key => $label)
                    <button type="button" wire:click="setStatusFilter('{{ $key }}')"
                        class="flex cursor-pointer items-center gap-3 border-b border-line py-[13px] text-left last:border-0">
                        <div class="flex-1 text-[15px] font-semibold">{{ $label }}</div>
                        @if ($status === (string) $key)
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

    {{-- Status sheet --}}
    @if ($this->statusItem)
        @include('livewire.items.partials.status-sheet')
    @endif

    {{-- Batch selection --}}
    @if ($selecting)
        @include('livewire.items.partials.batch-bar', ['selectableIds' => $this->items->pluck('id')->values()->all()])
    @endif
    @if ($batchSheet === 'move')
        @include('livewire.items.partials.batch-move-sheet')
    @elseif ($batchSheet === 'status')
        @include('livewire.items.partials.batch-status-sheet')
    @endif
</div>
