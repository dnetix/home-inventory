@php
    $fill = $this->tree->fill($place->id);
    $pct = $fill->percent();
    $barColor = $fill->isOverCapacity() ? 'var(--bad)' : (($pct ?? 0) > 80 ? 'var(--warn)' : 'var(--good)');
    $children = $this->tree->childrenOf($place->id);
    $directItems = $this->tree->itemsIn($place->id);
    $allItems = $this->tree->itemsUnder($place->id);
    $listItems = $directItems->isNotEmpty() ? $directItems : $allItems;
    $crumb = $this->tree->breadcrumbPlaces($place->id);
    $counts = $allItems->count().' '.Str::plural('item', $allItems->count())
        .($children->isNotEmpty() ? ' · '.$children->count().' sub-'.Str::plural('location', $children->count()) : '');
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col lg:mx-0 lg:max-w-none" x-data="itemSelection($wire.entangle('selectedIds'))">
    {{-- Nav bar (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:hidden">
        <a href="{{ route('places.index') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="flex-1"></span>
        <x-ui.icon-btn icon="edit" :size="17" wire:click="openEdit" />
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading :title="$place->label" :subtitle="$counts" />
    @endteleport

    @teleport('#topbar-actions')
        <x-ui.btn variant="tonal" size="sm" wire:click="openEdit">
            <x-icon name="edit" :size="15" /> Edit
        </x-ui.btn>
    @endteleport

    <div class="flex flex-1 gap-[18px] px-5 pb-6 lg:px-[30px] lg:pt-[18px] lg:pb-[30px]">
        <div class="min-w-0 flex-1">
            {{-- Breadcrumb --}}
            <div class="mb-[5px] flex items-center gap-[5px] text-xs font-semibold text-ink-3 lg:mb-4">
                <a href="{{ route('places.index') }}" wire:navigate class="transition hover:text-accent">
                    <x-icon name="home" :size="13" :stroke="1.9" />
                </a>
                @foreach ($crumb as $part)
                    @if (! $loop->first)<span>›</span>@endif
                    @if ($loop->last)
                        <span class="font-bold text-ink-2">{{ $part->label }}</span>
                    @else
                        <a href="{{ route('places.show', $part) }}" wire:navigate
                            class="transition hover:text-accent hover:underline">{{ $part->label }}</a>
                    @endif
                @endforeach
            </div>

            {{-- Title (mobile — desktop title lives in the top bar) --}}
            <div class="mb-[18px] flex items-start gap-3 lg:hidden">
                <div class="flex-1">
                    <h1 class="text-[22px] font-bold tracking-[-0.3px]">{{ $place->label }}</h1>
                    <p class="mt-[3px] text-[13.5px] font-medium text-ink-3">{{ $counts }}</p>
                    @if ($place->description)
                        <p class="mt-2 text-[13.5px] font-medium text-ink-2">{{ $place->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Space / capacity (mobile — desktop shows it in the rail) --}}
            <div class="mb-[22px] lg:hidden">
                @include('livewire.places.partials.space-card')
            </div>

            {{-- Sub-locations --}}
            <div class="mb-3 flex items-center justify-between">
                <x-ui.section-label>Sub-locations</x-ui.section-label>
                <button type="button" wire:click="openAddChild" class="cursor-pointer text-[13px] font-bold text-accent">+ Add</button>
            </div>
            @if ($children->isNotEmpty())
                <div class="mb-[22px] grid grid-cols-2 gap-2.5 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($children as $child)
                        @php
                            $childFill = $this->tree->fill($child->id);
                            $childPct = $childFill->percent();
                        @endphp
                        <a href="{{ route('places.show', $child) }}" wire:navigate wire:key="sub-{{ $child->id }}">
                            <x-ui.card class="flex items-center gap-2.5 px-3 py-2.5 transition active:scale-[0.98] lg:hover:shadow-md">
                                <x-ui.ph class="size-9 rounded-[10px]" :icon="$child->glyph ?: 'box'" :icon-size="16" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13.5px] font-semibold">{{ $child->label }}</span>
                                    <span class="block text-xs font-semibold text-ink-3">
                                        {{ $childFill->totalCount }} items{{ $childPct !== null ? ' · '.round($childPct).'%' : '' }}
                                    </span>
                                    @if ($childPct !== null)
                                        <span class="mt-1.5 block h-1 overflow-hidden rounded-sm bg-fill">
                                            <span class="block h-full rounded-sm"
                                                style="width: {{ min(100, $childPct) }}%; background: {{ $childFill->isOverCapacity() ? 'var(--bad)' : ($childPct > 80 ? 'var(--warn)' : 'var(--good)') }}"></span>
                                        </span>
                                    @endif
                                </span>
                            </x-ui.card>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="mb-[22px] text-[13.5px] font-medium text-ink-3">Nothing nested here yet.</p>
            @endif

            {{-- Items here (mobile caps the list at 6; desktop has room for all) --}}
            <div class="mb-3 flex items-center justify-between">
                <x-ui.section-label>Items here</x-ui.section-label>
                @if ($listItems->isNotEmpty())
                    <button type="button" wire:click="toggleSelecting"
                        class="cursor-pointer text-[13px] font-bold {{ $selecting ? 'text-accent' : 'text-ink-3' }}">
                        {{ $selecting ? 'Done' : 'Select' }}
                    </button>
                @endif
            </div>
            <x-ui.card class="divide-y divide-line px-3.5">
                @forelse ($listItems as $item)
                    {{-- Keyed by mode: wire:navigate binds its listener at element creation,
                         so the row must be recreated (not morphed) when selection toggles --}}
                    <a wire:key="pi-{{ $item->id }}-{{ $selecting ? 'sel' : 'nav' }}"
                        @if ($selecting) x-on:click.prevent="toggle({{ $item->id }})" @else href="{{ route('items.show', $item) }}" wire:navigate @endif
                        @class(['cursor-pointer items-center gap-3 py-2.5', 'flex' => $loop->index < 6, 'hidden lg:flex' => $loop->index >= 6])>
                        @if ($selecting)
                            <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full border transition"
                                x-bind:class="has({{ $item->id }}) ? 'border-accent bg-accent text-on-accent' : 'border-line-2'">
                                <x-icon name="check" :size="13" :stroke="2.5" x-show="has({{ $item->id }})" x-cloak />
                            </span>
                        @endif
                        <x-item-thumb class="size-[42px] rounded-[10px]" :item="$item" :icon-size="18" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[14px] font-semibold">{{ $item->name }}@if ($item->qty > 1)<span class="font-medium text-ink-3"> ×{{ $item->qty }}</span>@endif</div>
                            <div class="mt-0.5 truncate text-xs font-medium text-ink-3">
                                {{ $item->place_id ? implode(' › ', $this->tree->breadcrumb($item->place_id)) : '' }}
                            </div>
                        </div>
                        <span class="text-[13.5px] font-semibold text-ink-2 tabular-nums">{{ $item->value?->format() ?? '—' }}</span>
                        <x-icon name="chevron-right" :size="16" class="text-ink-4" />
                    </a>
                @empty
                    <div class="py-4 text-center text-[13.5px] font-medium text-ink-3">No items here yet.</div>
                @endforelse
                @if ($listItems->count() > 6)
                    <div class="py-2.5 text-center text-xs font-semibold text-ink-3 lg:hidden">
                        + {{ $listItems->count() - 6 }} more
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Desktop rail: capacity + notes --}}
        <aside class="hidden w-[320px] shrink-0 flex-col gap-3.5 lg:flex">
            @include('livewire.places.partials.space-card')
            @if ($place->description)
                <x-ui.card class="px-4 py-3.5">
                    <x-ui.section-label>Notes</x-ui.section-label>
                    <p class="mt-1.5 text-[13.5px] leading-relaxed font-medium text-ink-2">{{ $place->description }}</p>
                </x-ui.card>
            @endif
        </aside>
    </div>

    {{-- Batch selection --}}
    @if ($selecting)
        @include('livewire.items.partials.batch-bar', ['selectableIds' => $listItems->pluck('id')->values()->all()])
    @endif
    @if ($batchSheet === 'move')
        @include('livewire.items.partials.batch-move-sheet')
    @elseif ($batchSheet === 'status')
        @include('livewire.items.partials.batch-status-sheet')
    @endif

    {{-- Edit / add-child sheet --}}
    @if ($editor !== '')
        @include('livewire.places.partials.editor-sheet', [
            'title' => $editor === 'edit' ? 'Edit location' : 'New sub-location',
            'cta' => $editor === 'edit' ? 'Save changes' : 'Add location',
            'deletable' => $editor === 'edit',
        ])
    @endif
</div>
