{{-- The Find search input, shared by the mobile header and the desktop top
     bar (teleported). Enter blurs the field so the mobile keyboard closes;
     the search itself is live while typing. --}}
<div class="flex min-h-[44px] w-full items-center gap-2.5 rounded-[13px] border border-accent bg-surface px-3.5 ring-[3.5px] ring-accent-soft"
    x-data>
    <x-icon name="search" :size="18" :stroke="1.9" class="shrink-0 text-ink-3" />
    {{-- x-init focus instead of autofocus: this partial renders twice (mobile
         header + desktop top bar) and only the visible instance may grab focus --}}
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search items, places, tags…"
        x-init="$el.offsetParent !== null && $el.focus()" x-on:keydown.enter="$event.target.blur()"
        class="w-full bg-transparent py-2.5 text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3">
    @if ($search !== '')
        <button type="button" class="shrink-0 cursor-pointer text-ink-3" wire:click="$set('search', '')">
            <x-icon name="x" :size="17" :stroke="2" />
        </button>
    @endif
</div>
