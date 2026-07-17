<x-layouts.guest title="Sign in">
    <div class="mx-auto flex min-h-dvh w-full max-w-md flex-col px-[26px]">
        <div class="h-16 shrink-0"></div>

        {{-- Brand --}}
        <div class="mb-[34px] flex flex-col items-center gap-4">
            <div class="flex size-[62px] items-center justify-center rounded-[19px] bg-accent text-on-accent shadow-[0_12px_26px_-10px_var(--accent)]">
                <x-icon name="box" :size="30" :stroke="1.7" />
            </div>
            <div class="text-center">
                <div class="text-[22px] leading-tight tracking-[-0.4px]">
                    <span class="font-extrabold">Home</span><span class="font-semibold text-ink-2">Inventory</span>
                </div>
                <div class="mt-1 text-[13.5px] font-medium text-ink-2">Everything you own, organized.</div>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="mb-[5px] text-[22px] font-bold leading-tight tracking-[-0.3px]">Welcome back</h1>
        <p class="mb-[22px] text-[13.5px] font-medium text-ink-2">Sign in to pick up where you left off.</p>

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col">
            @csrf

            <div class="flex flex-col gap-3.5">
                <x-ui.field label="Email" name="email" icon="mail" type="email" inputmode="email"
                    placeholder="you@home.co" :value="old('email')" required autofocus autocomplete="email" />

                <div x-data="{ show: false }">
                    <x-ui.field label="Password" name="password" icon="lock" placeholder="Enter your password"
                        required autocomplete="current-password" x-bind:type="show ? 'text' : 'password'">
                        <x-slot:trailing>
                            <button type="button" class="shrink-0 cursor-pointer text-ink-3" x-on:click="show = !show">
                                <x-icon name="eye" :size="19" x-show="!show" />
                                <x-icon name="eye-off" :size="19" x-show="show" x-cloak />
                            </button>
                        </x-slot:trailing>
                    </x-ui.field>
                </div>
            </div>

            <label class="mt-[14px] flex cursor-pointer items-center gap-2.5 text-[13.5px] font-semibold text-ink-2">
                <input type="checkbox" name="remember" class="size-[18px] rounded-[5px] border-line-2 accent-[var(--accent)]">
                Remember me
            </label>

            <x-ui.btn variant="primary" type="submit" class="mt-[18px] w-full">
                Sign in
                <x-icon name="arrow-right" :size="19" />
            </x-ui.btn>
        </form>
    </div>
</x-layouts.guest>
