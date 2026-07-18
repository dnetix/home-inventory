{{-- Batch status: apply one status to every selected item. --}}
@php
    use App\Enums\ItemStatus;

    $meta = [
        ItemStatus::InPlace->value => ['icon' => 'check-circle', 'sub' => 'Back where they belong'],
        ItemStatus::Missing->value => ['icon' => 'search', 'sub' => "Can't find them right now"],
        ItemStatus::Broken->value => ['icon' => 'wrench', 'sub' => 'Need repair or replacement'],
        ItemStatus::Removed->value => ['icon' => 'trash', 'sub' => 'Hidden from the inventory, can be restored'],
    ];
@endphp

<x-ui.sheet close="closeBatch" :title="'Status for '.$this->selectedCount.' '.Str::plural('item', $this->selectedCount)">
    <div class="flex flex-col">
        @foreach (ItemStatus::cases() as $status)
            <button type="button" wire:click="batchSetStatus('{{ $status->value }}')"
                @if ($status === ItemStatus::Removed)
                    wire:confirm="Remove {{ $this->selectedCount }} {{ Str::plural('item', $this->selectedCount) }} from the inventory? They disappear from lists and stats but can be restored later."
                @endif
                class="flex cursor-pointer items-center gap-3.5 border-b border-line py-[13px] text-left last:border-0">
                <x-icon :name="$meta[$status->value]['icon']" :size="20" class="text-ink-2" />
                <div class="flex-1">
                    <div class="text-[15px] font-semibold">{{ $status->label() }}</div>
                    <div class="text-[12.5px] font-medium text-ink-3">{{ $meta[$status->value]['sub'] }}</div>
                </div>
            </button>
        @endforeach
    </div>
</x-ui.sheet>
