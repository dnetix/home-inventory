{{-- Change-status sheet. Rendered while $this->statusItem is set. --}}
@php
    use App\Enums\ItemStatus;

    $meta = [
        ItemStatus::InPlace->value => ['icon' => 'check-circle', 'sub' => 'Back where it belongs'],
        ItemStatus::Missing->value => ['icon' => 'search', 'sub' => "Can't find it right now"],
        ItemStatus::Broken->value => ['icon' => 'wrench', 'sub' => 'Needs repair or replacement'],
        ItemStatus::Removed->value => ['icon' => 'trash', 'sub' => 'Hidden from the inventory, can be restored'],
    ];
@endphp

<x-ui.sheet :title="$this->statusItem->name" close="cancelStatus">
    <div class="flex flex-col">
        @foreach (ItemStatus::cases() as $status)
            <button type="button" wire:click="setStatus({{ $this->statusItem->id }}, '{{ $status->value }}')"
                @if ($status === ItemStatus::Removed && $this->statusItem->status !== ItemStatus::Removed)
                    wire:confirm="Remove “{{ $this->statusItem->name }}” from the inventory? It disappears from lists and stats but can be restored later."
                @endif
                class="flex cursor-pointer items-center gap-3.5 border-b border-line py-[13px] text-left last:border-0">
                <x-icon :name="$meta[$status->value]['icon']" :size="20" class="text-ink-2" />
                <div class="flex-1">
                    <div class="text-[15px] font-semibold">{{ $status->label() }}</div>
                    <div class="text-[12.5px] font-medium text-ink-3">{{ $meta[$status->value]['sub'] }}</div>
                </div>
                @if ($this->statusItem->status === $status)
                    <x-icon name="check" :size="18" class="text-accent" />
                @endif
            </button>
        @endforeach
    </div>
</x-ui.sheet>
