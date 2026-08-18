{{-- Filterable location list for the move sheets: a search box over the
     server-rendered place tree, filtered client-side against full
     breadcrumbs — the place-combobox UX adapted to live inside a sheet,
     where a floating dropdown would be clipped. While searching, rows show
     their breadcrumb since matched children often appear without parents.
     Expects $property (the Livewire property picks write to), $selectedId,
     and optionally $currentId (badged "current"). --}}
@php
    $entries = $this->placeIndex->flatten()->map(fn (array $entry) => [
        'place' => $entry['place'],
        'depth' => $entry['depth'],
        'crumb' => implode(' › ', $this->placeIndex->breadcrumb($entry['place']->id)),
    ])->values();
    $currentId ??= null;
@endphp

<div x-data="{
        q: '',
        keys: @js($entries->map(fn (array $entry) => mb_strtolower($entry['crumb']))->all()),
        ids: @js($entries->map(fn (array $entry) => $entry['place']->id)->all()),
        get query() { return this.q.trim().toLowerCase(); },
        show(index) { return this.query === '' || this.keys[index].includes(this.query); },
        get firstMatch() { const index = this.keys.findIndex((key) => key.includes(this.query)); return index === -1 ? null : this.ids[index]; },
    }">
    <div class="mb-2.5 flex items-center gap-2.5 rounded-btn border border-line-2 bg-surface px-3.5 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
        <x-icon name="search" :size="17" class="shrink-0 text-ink-3" />
        <input type="text" placeholder="Search locations…" autocomplete="off" x-model="q"
            x-on:keydown.enter.prevent="query !== '' && firstMatch !== null && $wire.set('{{ $property }}', firstMatch)"
            class="w-full bg-transparent py-[11px] text-[15px] font-medium text-ink outline-none placeholder:text-ink-3">
        <button type="button" x-show="q !== ''" x-cloak x-on:click="q = ''"
            class="flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-3 hover:text-ink-2">
            <x-icon name="x" :size="15" :stroke="2.2" />
        </button>
    </div>

    <div class="flex max-h-[38vh] flex-col overflow-y-auto rounded-[14px] border border-line">
        @foreach ($entries as $index => $entry)
            <button type="button" wire:key="pfl-{{ $entry['place']->id }}"
                wire:click="$set('{{ $property }}', {{ $entry['place']->id }})"
                x-show="show({{ $index }})"
                x-bind:style="query === '' ? @js('padding-left: '.(14 + $entry['depth'] * 18).'px') : ''"
                class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left last:border-0 {{ $selectedId === $entry['place']->id ? 'bg-accent-soft' : 'hover:bg-fill' }}">
                <x-icon :name="$entry['place']->glyph ?: 'box'" :size="16"
                    class="shrink-0 {{ $selectedId === $entry['place']->id ? 'text-accent-ink' : 'text-ink-3' }}" />
                <span class="flex-1 truncate text-[14px] font-semibold {{ $selectedId === $entry['place']->id ? 'text-accent-ink' : '' }}"
                    x-text="query === '' ? @js($entry['place']->label) : @js($entry['crumb'])">{{ $entry['place']->label }}</span>
                @if ($currentId === $entry['place']->id)
                    <span class="shrink-0 text-[11px] font-bold text-ink-3 uppercase">current</span>
                @endif
            </button>
        @endforeach
        <div x-show="query !== '' && firstMatch === null" x-cloak class="px-3.5 py-3 text-[13.5px] font-medium text-ink-3">
            No matching location
        </div>
    </div>
</div>
