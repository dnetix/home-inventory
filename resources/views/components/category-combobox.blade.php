{{-- Searchable category picker (real inventories have too many categories for
     chips). Entangles the given Livewire property with the picked id or null;
     $nullLabel names the null choice ("Uncategorized", "All categories", …). --}}
{{-- `live` syncs each pick to the server immediately (filters); without it the
     value is deferred until the next request (forms, synced on submit). --}}
@props(['categories', 'property', 'nullLabel' => 'Uncategorized', 'live' => false])

@php
    $options = $categories->map(fn ($category) => [
        'id' => $category->id,
        'label' => $category->label,
        'child' => $category->parent_id !== null,
        'color' => $category->color,
        'search' => mb_strtolower(trim(
            ($category->parent_id ? $categories->firstWhere('id', $category->parent_id)?->label.' ' : '').$category->label
        )),
    ])->values();
@endphp

<div class="relative" x-on:click.outside="close()" x-data="{
        open: false,
        search: '',
        selected: $wire.entangle(@js($property)){{ $live ? '.live' : '' }},
        options: @js($options),
        get filtered() {
            const query = this.search.trim().toLowerCase();
            return query === '' ? this.options : this.options.filter((option) => option.search.includes(query));
        },
        label(id) { return this.options.find((option) => option.id === id)?.label ?? ''; },
        pick(id) { this.selected = id; this.close(); },
        close() { this.open = false; this.search = ''; },
    }">
    <div class="flex min-h-[50px] items-center gap-2.5 rounded-btn border border-line-2 bg-surface px-3.5 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
        <x-icon name="search" :size="19" class="shrink-0 text-ink-3" />
        <input type="text" placeholder="{{ $nullLabel }}" autocomplete="off"
            class="w-full bg-transparent py-[13px] text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3"
            :value="open ? search : label(selected)"
            x-on:input="search = $event.target.value; open = true"
            x-on:focus="open = true; search = ''"
            x-on:keydown.escape.prevent="close(); $event.target.blur()"
            x-on:keydown.enter.prevent="search.trim() ? pick(filtered[0]?.id ?? null) : close()"
            x-on:keydown.tab="close()">
        <button type="button" x-show="selected !== null" x-cloak x-on:click="pick(null)"
            class="flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-3 hover:text-ink-2">
            <x-icon name="x" :size="15" :stroke="2.2" />
        </button>
        <x-icon name="chevron-down" :size="16" class="shrink-0 text-ink-3" x-show="selected === null" />
    </div>
    <div x-show="open" x-cloak
        class="absolute inset-x-0 top-full z-20 mt-1.5 max-h-[280px] overflow-y-auto rounded-[14px] border border-line bg-surface shadow-lg">
        <button type="button" x-on:click="pick(null)" x-show="!search.trim()"
            class="flex w-full cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left hover:bg-fill"
            :class="selected === null && 'bg-accent-soft'">
            <span class="flex-1 text-[14px] font-semibold" :class="selected === null ? 'text-accent-ink' : 'text-ink-3'">{{ $nullLabel }}</span>
        </button>
        <template x-for="option in filtered" :key="option.id">
            <button type="button" x-on:click="pick(option.id)"
                class="flex w-full cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left last:border-0 hover:bg-fill"
                :class="selected === option.id && 'bg-accent-soft'">
                <span class="size-2 shrink-0 rounded-full" :class="option.child && 'ml-3'"
                    :style="`background: ${option.color ?? 'var(--line-2)'}`"></span>
                <span class="flex-1 truncate text-[14px] font-semibold" :class="selected === option.id && 'text-accent-ink'"
                    x-text="option.label"></span>
            </button>
        </template>
        <div x-show="!filtered.length" class="px-3.5 py-3 text-[13.5px] font-medium text-ink-3">
            No matching category
        </div>
    </div>
</div>
