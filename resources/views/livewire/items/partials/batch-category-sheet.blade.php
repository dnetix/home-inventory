{{-- Batch category: apply one category to every selected item. --}}
<x-ui.sheet close="closeBatch" :title="'Category for '.$this->selectedCount.' '.Str::plural('item', $this->selectedCount)">
    <div class="flex max-h-[44vh] flex-col overflow-y-auto rounded-[14px] border border-line">
        <button type="button" wire:click="$set('batchCategoryId', 0)"
            class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left {{ $batchCategoryId === 0 ? 'bg-accent-soft' : 'hover:bg-fill' }}">
            <x-icon name="slash" :size="16" class="{{ $batchCategoryId === 0 ? 'text-accent-ink' : 'text-ink-3' }}" />
            <span class="flex-1 text-[14px] font-semibold {{ $batchCategoryId === 0 ? 'text-accent-ink' : 'text-ink-3' }}">
                No category
            </span>
        </button>
        @foreach ($this->batchCategories as $category)
            <button type="button" wire:key="bcat-{{ $category->id }}"
                wire:click="$set('batchCategoryId', {{ $category->id }})"
                class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left last:border-0 {{ $batchCategoryId === $category->id ? 'bg-accent-soft' : 'hover:bg-fill' }}"
                style="padding-left: {{ $category->parent_id === null ? 14 : 32 }}px">
                <x-icon :name="$category->glyph ?: 'box'" :size="16"
                    class="{{ $batchCategoryId === $category->id ? 'text-accent-ink' : 'text-ink-3' }}" />
                <span class="flex-1 text-[14px] font-semibold {{ $batchCategoryId === $category->id ? 'text-accent-ink' : '' }}">
                    {{ $category->label }}
                </span>
            </button>
        @endforeach
    </div>

    <div class="mt-4 flex gap-2.5">
        <x-ui.btn variant="ghost" class="flex-1" wire:click="closeBatch">Cancel</x-ui.btn>
        <x-ui.btn variant="primary" class="flex-1 {{ $batchCategoryId === null ? 'pointer-events-none opacity-50' : '' }}"
            wire:click="confirmBatchCategory">
            Apply
        </x-ui.btn>
    </div>
</x-ui.sheet>
