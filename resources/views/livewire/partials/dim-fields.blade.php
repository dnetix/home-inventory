{{-- W × H × D inputs in the user's display unit. Expects $prefix ('form'), optional $label. --}}
<div>
    @if ($label ?? null)
        <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">
            {{ $label }}
            <span class="font-semibold text-ink-3">(W × H × D, {{ $this->units->lengthUnitLabel() }})</span>
        </div>
    @endif

    <div class="flex items-center gap-2">
        @foreach (['w' => 'W', 'h' => 'H', 'd' => 'D'] as $key => $ph)
            <div class="min-w-0 flex-1 rounded-btn border border-line-2 bg-surface px-3 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
                <input type="text" inputmode="decimal" placeholder="{{ $ph }}"
                    wire:model="{{ $prefix }}.{{ $key }}"
                    class="w-full bg-transparent py-[13px] text-center text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3">
            </div>
            @if (! $loop->last)
                <span class="shrink-0 text-[13px] font-semibold text-ink-4">×</span>
            @endif
        @endforeach
    </div>

    @error($prefix.'.w')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
    @error($prefix.'.h')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
    @error($prefix.'.d')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
</div>
