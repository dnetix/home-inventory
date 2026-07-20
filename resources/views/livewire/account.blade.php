@php
    $user = auth()->user();
    $home = $user->currentHome;
    $initials = collect(explode(' ', $user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('more') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">User account</span>
    </div>

    <div class="flex-1 px-5 pb-8 lg:px-[30px]">
        {{-- Avatar header --}}
        <div class="flex flex-col items-center gap-3 pt-3 pb-[22px]">
            <span class="flex size-[76px] items-center justify-center rounded-full bg-accent text-[26px] font-bold text-on-accent shadow-[0_12px_26px_-10px_var(--accent)]">
                {{ $initials }}
            </span>
            <span class="text-center">
                <span class="block text-[22px] font-bold tracking-[-0.3px]">{{ $user->name }}</span>
                <span class="mt-0.5 block text-[13.5px] font-medium text-ink-3">{{ $user->email }}</span>
            </span>
        </div>

        {{-- Profile --}}
        <x-ui.section-label class="mb-2.5">Profile</x-ui.section-label>
        <x-ui.card class="flex flex-col gap-4 px-4 py-4">
            <x-ui.field label="Name" name="name" icon="user" wire:model="name" required />
            <x-ui.field label="Email" name="email" icon="mail" type="email" wire:model="email" required />
            <x-ui.btn variant="tonal" size="sm" class="self-start" wire:click="save">Save changes</x-ui.btn>
        </x-ui.card>

        {{-- Password --}}
        <x-ui.section-label class="mt-6 mb-2.5">Password</x-ui.section-label>
        <x-ui.card class="flex flex-col gap-4 px-4 py-4">
            <x-ui.field label="Current password" name="currentPassword" icon="lock" type="password"
                wire:model="currentPassword" autocomplete="current-password" required />
            <x-ui.field label="New password" name="password" icon="lock" type="password"
                wire:model="password" autocomplete="new-password" required />
            <x-ui.field label="Confirm new password" name="passwordConfirmation" icon="lock" type="password"
                wire:model="passwordConfirmation" autocomplete="new-password" required />
            <x-ui.btn variant="tonal" size="sm" class="self-start" wire:click="updatePassword">
                Update password
            </x-ui.btn>
        </x-ui.card>

        {{-- Home --}}
        <x-ui.section-label class="mt-6 mb-2.5">Home</x-ui.section-label>
        <x-ui.card class="px-4 py-1">
            <div class="flex items-center gap-[13px] py-3">
                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] bg-fill text-ink-2">
                    <x-icon name="home" :size="18" :stroke="1.85" />
                </span>
                <span class="flex-1">
                    <span class="block text-[15px] font-semibold">{{ $home?->name ?? 'No home' }}</span>
                    <span class="block text-xs font-medium text-ink-3">
                        {{ $home ? $home->users()->count().' '.Str::plural('member', $home->users()->count()) : '' }}
                        · sharing coming later
                    </span>
                </span>
            </div>
        </x-ui.card>

        {{-- Log out --}}
        <form method="POST" action="{{ route('logout') }}" class="mt-7">
            @csrf
            <button type="submit"
                class="flex h-[50px] w-full cursor-pointer items-center justify-center gap-2 rounded-btn border border-[color-mix(in_srgb,var(--bad)_35%,var(--line-2))] text-base font-bold text-bad transition active:scale-[0.975]">
                <x-icon name="log-out" :size="18" /> Log out
            </button>
        </form>
    </div>
</div>
