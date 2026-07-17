<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('more') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">Settings</span>
    </div>

    <div class="flex-1 px-5 pb-8 lg:px-[30px]">
        {{-- Measurement units --}}
        <x-ui.section-label class="mt-1 mb-2.5">Measurement units</x-ui.section-label>
        <x-ui.card class="px-4 pt-3.5 pb-4">
            <div class="mb-3.5 flex items-center gap-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] bg-accent-soft text-accent-ink">
                    <x-icon name="cube" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Size &amp; volume</span>
                    <span class="block text-xs font-medium text-ink-3">How dimensions are shown everywhere</span>
                </span>
            </div>

            <x-ui.seg>
                <x-ui.seg-btn :on="$unit === 'metric'" wire:click="setUnit('metric')">Metric</x-ui.seg-btn>
                <x-ui.seg-btn :on="$unit === 'imperial'" wire:click="setUnit('imperial')">Imperial</x-ui.seg-btn>
            </x-ui.seg>

            <div class="mt-4 flex gap-2">
                <div class="flex-1 rounded-[13px] bg-fill px-[13px] py-[11px]">
                    <div class="text-xs font-medium text-ink-3">Length</div>
                    <div class="mt-0.5 text-base font-bold">{{ $unit === 'imperial' ? 'inches (in)' : 'centimetres (cm)' }}</div>
                </div>
                <div class="flex-1 rounded-[13px] bg-fill px-[13px] py-[11px]">
                    <div class="text-xs font-medium text-ink-3">Volume</div>
                    <div class="mt-0.5 text-base font-bold">{{ $unit === 'imperial' ? 'in³ / ft³' : 'L / m³' }}</div>
                </div>
            </div>

            <div class="mt-3.5 flex items-center gap-2 rounded-[11px] bg-accent-soft px-[13px] py-[11px] text-accent-ink">
                <x-icon name="cube" :size="17" :stroke="1.8" />
                <span class="text-[13.5px] font-semibold">e.g. {{ $this->sample() }}</span>
            </div>
        </x-ui.card>

        {{-- Appearance --}}
        <x-ui.section-label class="mt-6 mb-2.5">Appearance</x-ui.section-label>
        <x-ui.card class="px-4 pt-3.5 pb-4">
            <div class="mb-3.5 flex items-center gap-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] bg-fill text-ink-2">
                    <x-icon name="sun" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Theme</span>
                    <span class="block text-xs font-medium text-ink-3">Applies on this device right away</span>
                </span>
            </div>
            <x-ui.seg>
                <x-ui.seg-btn :on="$theme === 'light'" wire:click="setTheme('light')">
                    <x-icon name="sun" :size="14" /> Light
                </x-ui.seg-btn>
                <x-ui.seg-btn :on="$theme === 'dark'" wire:click="setTheme('dark')">
                    <x-icon name="moon" :size="14" /> Dark
                </x-ui.seg-btn>
                <x-ui.seg-btn :on="$theme === 'system'" wire:click="setTheme('system')">System</x-ui.seg-btn>
            </x-ui.seg>
        </x-ui.card>

        {{-- Notifications --}}
        <x-ui.section-label class="mt-6 mb-2.5">Notifications</x-ui.section-label>
        <x-ui.card class="px-4 py-1">
            <div class="flex items-center gap-[13px] py-[11px]">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] bg-fill text-ink-2">
                    <x-icon name="bell" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">Reminders</span>
                    <span class="block text-xs font-medium text-ink-3">Upkeep due dates &amp; lending returns</span>
                </span>
                <x-ui.switch :checked="$notifications" wire:model.live="notifications" />
            </div>
        </x-ui.card>

        <div class="mt-6 text-center text-xs font-medium text-ink-3">HomeInventory · v1.0</div>
    </div>
</div>
