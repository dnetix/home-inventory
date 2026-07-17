@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    <div class="px-5 pt-8 lg:px-[30px] lg:pt-[26px]">
        <h1 class="text-[30px] font-extrabold tracking-[-0.4px] lg:text-[26px]">More</h1>
    </div>

    <div class="flex-1 px-5 pt-4 pb-8 lg:px-[30px]">
        {{-- Account header --}}
        <a href="{{ route('account') }}" wire:navigate>
            <x-ui.card class="mb-[22px] flex items-center gap-3.5 px-4 py-[15px] transition active:scale-[0.99]">
                <span class="flex size-[50px] items-center justify-center rounded-full bg-accent text-[18px] font-bold text-on-accent">
                    {{ $initials }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[19px] font-bold tracking-[-0.2px]">{{ $user->name }}</span>
                    <span class="block truncate text-[13.5px] font-medium text-ink-3">{{ $user->email }}</span>
                </span>
                <x-icon name="chevron-right" :size="18" class="text-ink-4" />
            </x-ui.card>
        </a>

        {{-- Organize --}}
        <x-ui.section-label class="mb-2.5">Organize</x-ui.section-label>
        <x-ui.card class="mb-[22px] divide-y divide-line px-3.5">
            <a href="{{ route('categories.index') }}" wire:navigate class="flex items-center gap-[13px] py-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] text-[#4f74e3]"
                    style="background: color-mix(in srgb, #4f74e3 16%, var(--surface))">
                    <x-icon name="layers" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Categories</span>
                    <span class="block text-xs font-medium text-ink-3">Group items by type</span>
                </span>
                <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->counts['categories'] }}</span>
                <x-icon name="chevron-right" :size="16" class="text-ink-4" />
            </a>
            <a href="{{ route('tags.index') }}" wire:navigate class="flex items-center gap-[13px] py-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] text-[#a866c8]"
                    style="background: color-mix(in srgb, #a866c8 16%, var(--surface))">
                    <x-icon name="tag" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Tags</span>
                    <span class="block text-xs font-medium text-ink-3">Cross-cutting labels</span>
                </span>
                <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->counts['tags'] }}</span>
                <x-icon name="chevron-right" :size="16" class="text-ink-4" />
            </a>
        </x-ui.card>

        {{-- Manage --}}
        <x-ui.section-label class="mb-2.5">Manage</x-ui.section-label>
        <x-ui.card class="mb-[22px] divide-y divide-line px-3.5">
            <a href="{{ route('settings') }}" wire:navigate class="flex items-center gap-[13px] py-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] text-[#4f74e3]"
                    style="background: color-mix(in srgb, #4f74e3 16%, var(--surface))">
                    <x-icon name="cog" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Settings</span>
                    <span class="block text-xs font-medium text-ink-3">Units, appearance, reminders</span>
                </span>
                <x-icon name="chevron-right" :size="16" class="text-ink-4" />
            </a>
            <a href="{{ route('lending.index') }}" wire:navigate class="flex items-center gap-[13px] py-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] text-[#1f9d8f]"
                    style="background: color-mix(in srgb, #1f9d8f 16%, var(--surface))">
                    <x-icon name="hand" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Lending</span>
                    <span class="block text-xs font-medium text-ink-3">Who borrowed what</span>
                </span>
                <x-icon name="chevron-right" :size="16" class="text-ink-4" />
            </a>
            <a href="{{ route('upkeep.index') }}" wire:navigate class="flex items-center gap-[13px] py-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] text-[#df8f3c]"
                    style="background: color-mix(in srgb, #df8f3c 16%, var(--surface))">
                    <x-icon name="calendar" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Upkeep</span>
                    <span class="block text-xs font-medium text-ink-3">Maintenance &amp; expiry</span>
                </span>
                @if ($this->counts['attention'] > 0)
                    <x-ui.pill variant="bad">{{ $this->counts['attention'] }}</x-ui.pill>
                @endif
                <x-icon name="chevron-right" :size="16" class="text-ink-4" />
            </a>
        </x-ui.card>

        <div class="mt-6 text-center text-xs font-medium text-ink-3">HomeInventory · v1.0</div>
    </div>
</div>
