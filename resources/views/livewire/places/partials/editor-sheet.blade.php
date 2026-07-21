{{-- Create/edit location sheet. Expects $title, $cta, $deletable. --}}
<x-ui.sheet :title="$title" close="closeEditor">
    <div class="flex flex-col gap-4">
        <x-ui.field label="Name" name="form.label" icon="home" placeholder="Location name" required
            wire:model="form.label" />

        <div>
            <label class="mb-[7px] block text-[12.5px] font-bold text-ink-2">Description</label>
            <div class="rounded-btn border border-line-2 bg-surface px-3.5 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
                <textarea wire:model="form.description" rows="2" placeholder="Add a note about this spot…"
                    class="w-full resize-none bg-transparent py-[13px] text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3"></textarea>
            </div>
            @error('form.description')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
        </div>

        <div>
            <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Icon</div>
            @include('livewire.partials.glyph-picker')
        </div>

        <div>
            <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Inside</div>
            <select wire:model="form.parentId"
                class="min-h-[50px] w-full cursor-pointer rounded-btn border border-line-2 bg-surface px-3.5 text-[15.5px] font-medium text-ink outline-none focus:border-accent">
                <option value="">Top level (a room)</option>
                @foreach ($this->tree->flatten() as $entry)
                    @if ($form->place === null || $form->place->id !== $entry['place']->id)
                        <option value="{{ $entry['place']->id }}">
                            {{ str_repeat('— ', $entry['depth']) }}{{ $entry['place']->label }}
                        </option>
                    @endif
                @endforeach
            </select>
            @error('form.parentId')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
        </div>

        @include('livewire.partials.dim-fields', ['prefix' => 'form', 'label' => 'Interior size'])
    </div>

    <x-ui.btn variant="primary" class="mt-[22px] w-full {{ trim($form->label) === '' ? 'opacity-50' : '' }}"
        wire:click="save">
        {{ $cta }}
    </x-ui.btn>

    @if ($deletable)
        <button type="button" wire:click="deletePlace"
            wire:confirm="Delete this location? Items and sub-locations must be moved first."
            class="mt-3 flex cursor-pointer items-center justify-center gap-2 py-2 text-[14px] font-bold text-bad">
            <x-icon name="trash" :size="17" /> Delete location
        </button>
    @endif
</x-ui.sheet>
