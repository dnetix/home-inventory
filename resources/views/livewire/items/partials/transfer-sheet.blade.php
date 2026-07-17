{{-- Transfer flow: pick a destination, see the fit verdict, confirm. --}}
@php
    $fit = $this->transferFit;
    $fitStyles = [
        'fit' => 'bg-good-soft text-good',
        'tight' => 'bg-warn-soft text-warn',
        'full' => 'bg-bad-soft text-bad',
        'toobig' => 'bg-bad-soft text-bad',
        'unknown' => 'bg-fill text-ink-2',
    ];
@endphp

<x-ui.sheet close="cancelTransfer" :title="'Move “'.$this->transferItem->name.'”'">
    <div class="flex max-h-[44vh] flex-col overflow-y-auto rounded-[14px] border border-line">
        @foreach ($this->placeIndex->flatten() as $entry)
            <button type="button" wire:key="tp-{{ $entry['place']->id }}"
                wire:click="$set('transferPlaceId', {{ $entry['place']->id }})"
                class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left last:border-0 {{ $transferPlaceId === $entry['place']->id ? 'bg-accent-soft' : 'hover:bg-fill' }}"
                style="padding-left: {{ 14 + $entry['depth'] * 18 }}px">
                <x-icon :name="$entry['place']->glyph ?: 'box'" :size="16"
                    class="{{ $transferPlaceId === $entry['place']->id ? 'text-accent-ink' : 'text-ink-3' }}" />
                <span class="flex-1 text-[14px] font-semibold {{ $transferPlaceId === $entry['place']->id ? 'text-accent-ink' : '' }}">
                    {{ $entry['place']->label }}
                </span>
                @if ($this->transferItem->place_id === $entry['place']->id)
                    <span class="text-[11px] font-bold text-ink-3 uppercase">current</span>
                @endif
            </button>
        @endforeach
    </div>

    @if ($fit)
        <div class="mt-3.5 flex items-center gap-3 rounded-[14px] px-3.5 py-3 {{ $fitStyles[$fit->status->value] }}">
            <x-icon :name="$fit->status === \App\Enums\FitStatus::Fit ? 'check-circle' : ($fit->status === \App\Enums\FitStatus::Unknown ? 'cube' : 'x')"
                :size="19" :stroke="1.9" />
            <div class="flex-1">
                <div class="text-[13.5px] font-bold">{{ $fit->status->label() }}</div>
                <div class="mt-px text-[12px] font-semibold opacity-80">
                    @if ($fit->status === \App\Enums\FitStatus::Unknown)
                        Set item and location sizes to check the fit.
                    @else
                        {{ $this->units->volume(max(0, $fit->remainingLitres())) }} free of {{ $this->units->volume($fit->capacityLitres) }}
                        · needs {{ $this->units->volume($fit->neededLitres) }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4 flex gap-2.5">
        <x-ui.btn variant="ghost" class="flex-1" wire:click="cancelTransfer">Cancel</x-ui.btn>
        <x-ui.btn variant="primary" class="flex-1 {{ $transferPlaceId === null ? 'pointer-events-none opacity-50' : '' }}"
            wire:click="confirmTransfer">
            Move here
        </x-ui.btn>
    </div>
</x-ui.sheet>
