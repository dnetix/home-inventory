{{-- New upkeep task for the item being viewed — created in place, the
     browser stays on this page. Uses $this->upkeepItem + upkeepForm from
     ManagesItemActions. --}}
@php
    use App\Livewire\Forms\UpkeepTaskForm;
@endphp
<x-ui.sheet title="New upkeep task" close="cancelUpkeep">
    <div class="mb-[18px] flex items-center gap-3 rounded-[14px] bg-fill px-[13px] py-[11px]">
        <x-item-thumb class="size-[38px] rounded-[11px]" :item="$this->upkeepItem" :icon-size="16" />
        <span class="flex-1">
            <span class="block text-[13.5px] font-semibold">{{ $this->upkeepItem->name }}</span>
            <span class="block text-xs font-medium text-ink-3">Task will be linked to this item</span>
        </span>
    </div>

    <div class="flex flex-col gap-4">
        <div>
            <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Kind</div>
            <x-ui.seg>
                <x-ui.seg-btn :on="$upkeepForm->kind === 'maint'" wire:click="$set('upkeepForm.kind', 'maint')">
                    <x-icon name="wrench" :size="14" /> Maintenance
                </x-ui.seg-btn>
                <x-ui.seg-btn :on="$upkeepForm->kind === 'expiry'" wire:click="$set('upkeepForm.kind', 'expiry')">
                    <x-icon name="shield" :size="14" /> Expiry
                </x-ui.seg-btn>
            </x-ui.seg>
        </div>

        <x-ui.field label="Task" name="upkeepForm.task" placeholder="e.g. Replace air filter" required
            wire:model="upkeepForm.task" />

        <x-ui.field label="Due date" name="upkeepForm.dueDate" icon="calendar" type="date" required
            wire:model="upkeepForm.dueDate" />

        <div>
            <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Repeat</div>
            <select wire:model="upkeepForm.every"
                class="min-h-[50px] w-full cursor-pointer rounded-btn border border-line-2 bg-surface px-3.5 text-[15.5px] font-medium text-ink outline-none focus:border-accent">
                @foreach (UpkeepTaskForm::RECURRENCES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-ui.btn variant="primary" class="mt-[22px] w-full {{ trim($upkeepForm->task) === '' ? 'opacity-50' : '' }}"
        wire:click="saveUpkeep">
        Add task
    </x-ui.btn>
</x-ui.sheet>
