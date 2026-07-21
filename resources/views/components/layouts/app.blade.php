@props(['title' => null])

@php
    $nav = [
        ['label' => 'Home', 'icon' => 'home', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Items', 'icon' => 'box', 'route' => 'items.index', 'active' => 'items.*'],
        ['label' => 'Places', 'icon' => 'map-pin', 'route' => 'places.index', 'active' => 'places.*'],
        ['label' => 'Lending', 'icon' => 'hand', 'route' => 'lending.index', 'active' => 'lending.*'],
        ['label' => 'Upkeep', 'icon' => 'calendar', 'route' => 'upkeep.index', 'active' => 'upkeep.*'],
        ['label' => 'Categories', 'icon' => 'layers', 'route' => 'categories.index', 'active' => 'categories.*'],
        ['label' => 'Tags', 'icon' => 'tag', 'route' => 'tags.index', 'active' => 'tags.*'],
    ];

    $tabs = [
        ['label' => 'Home', 'icon' => 'home', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Items', 'icon' => 'box', 'route' => 'items.index', 'active' => 'items.*'],
        null, // FAB slot
        ['label' => 'Places', 'icon' => 'map-pin', 'route' => 'places.index', 'active' => 'places.*'],
        ['label' => 'More', 'icon' => 'cog', 'route' => 'more', 'active' => ['more', 'settings', 'account', 'categories.*', 'tags.*', 'lending.*', 'upkeep.*']],
    ];

    $initials = collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <x-layouts.head :title="$title" />
</head>

<body class="min-h-dvh">
    {{-- Desktop icon rail --}}
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-[76px] flex-col items-center border-r border-line bg-screen pt-5 pb-6 lg:flex">
        <a href="{{ route('dashboard') }}" wire:navigate
            class="flex size-[42px] items-center justify-center rounded-[13px] bg-accent text-on-accent shadow-[0_8px_18px_-8px_var(--accent)]">
            <x-icon name="box" :size="22" />
        </a>

        <nav class="mt-7 flex flex-col items-center gap-2.5">
            @foreach ($nav as $entry)
                <a href="{{ route($entry['route']) }}" wire:navigate
                    class="flex flex-col items-center gap-1 {{ request()->routeIs($entry['active']) ? 'text-accent' : 'text-ink-3 hover:text-ink-2' }}">
                    <span
                        class="flex size-[46px] items-center justify-center rounded-[13px] transition {{ request()->routeIs($entry['active']) ? 'bg-accent-soft' : '' }}">
                        <x-icon :name="$entry['icon']" :size="21" />
                    </span>
                    <span class="text-[9.5px] font-semibold">{{ $entry['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-auto flex flex-col items-center gap-4">
            <a href="{{ route('settings') }}" wire:navigate
                class="{{ request()->routeIs('settings') ? 'text-accent' : 'text-ink-3 hover:text-ink-2' }}">
                <x-icon name="cog" :size="21" />
            </a>
            <a href="{{ route('account') }}" wire:navigate
                class="flex size-[34px] items-center justify-center rounded-full bg-accent-soft text-[12.5px] font-bold text-accent-ink">
                {{ $initials }}
            </a>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-h-dvh flex-col pb-[88px] lg:pb-0 lg:pl-[76px]">
        {{-- Desktop top bar: page heading (teleported per screen) + global search + page actions + add item --}}
        <header class="sticky top-0 z-30 hidden min-h-[62px] items-center gap-4 border-b border-line bg-screen px-[30px] py-2.5 lg:flex">
            <div id="topbar-page" class="min-w-0 shrink-0 empty:hidden"></div>
            {{-- On /find the real search input teleports into #topbar-page instead --}}
            @unless (request()->routeIs('find'))
                <a href="{{ route('find') }}" wire:navigate
                    class="flex min-h-[42px] w-full max-w-[400px] items-center gap-2.5 rounded-[13px] border border-line-2 bg-surface px-3.5 text-ink-3 transition hover:text-ink-2 hover:shadow-sm">
                    <x-icon name="search" :size="17" :stroke="1.9" class="shrink-0" />
                    <span class="flex-1 truncate text-left text-[14.5px] font-medium">Search items, places, tags…</span>
                    <kbd x-data x-text="navigator.platform.includes('Mac') ? '⌘ K' : 'Ctrl K'"
                        class="rounded-[7px] border border-line-2 bg-fill px-1.5 py-0.5 font-sans text-[11px] font-semibold whitespace-nowrap text-ink-3"></kbd>
                </a>
            @endunless
            <span class="flex-1"></span>
            <div id="topbar-actions" class="flex shrink-0 items-center gap-2 empty:hidden"></div>
            <a href="{{ route('items.create') }}" wire:navigate
                class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-[11px] bg-accent px-3.5 text-[14.5px] font-bold whitespace-nowrap text-on-accent shadow-[0_6px_16px_-8px_var(--accent)] transition active:scale-[0.975] active:bg-accent-press">
                <x-icon name="plus" :size="17" :stroke="2.2" />
                Add item
            </a>
        </header>

        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile tab bar --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 flex items-start justify-around border-t border-line bg-screen px-2 pt-[11px] pb-[max(env(safe-area-inset-bottom),18px)] lg:hidden">
        @foreach ($tabs as $tab)
            @if ($tab === null)
                <a href="{{ route('items.create') }}" wire:navigate
                    class="-mt-6 flex size-14 items-center justify-center rounded-[19px] bg-accent text-on-accent shadow-[0_10px_22px_-8px_var(--accent)] transition active:scale-[0.92]">
                    <x-icon name="plus" :size="26" />
                </a>
            @else
                <a href="{{ route($tab['route']) }}" wire:navigate
                    class="flex w-16 flex-col items-center gap-1 transition {{ request()->routeIs($tab['active']) ? 'text-accent' : 'text-ink-3' }}">
                    <x-icon :name="$tab['icon']" :size="22" />
                    <span class="text-[10.5px] font-semibold tracking-[0.1px]">{{ $tab['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Toast --}}
    <div x-data="{ msg: @js(session('toast')), show: false, timer: null, pop(m) { this.msg = m; this.show = true; clearTimeout(this.timer); this.timer = setTimeout(() => this.show = false, 2400) } }"
        x-init="if (msg) pop(msg)" x-on:toast.window="pop($event.detail.message ?? $event.detail)" x-cloak
        x-show="show" x-transition.opacity.duration.250ms
        class="fixed bottom-[110px] left-1/2 z-50 flex -translate-x-1/2 items-center gap-2 rounded-[14px] bg-ink px-[18px] py-3 text-sm font-semibold whitespace-nowrap text-screen shadow-lg lg:bottom-10">
        <x-icon name="check" :size="16" />
        <span x-text="msg"></span>
    </div>

    @livewireScripts
</body>

</html>
