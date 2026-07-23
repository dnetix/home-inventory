@php
    $editing = $form->item !== null;
    $selectedCategory = $this->categories->firstWhere('id', $form->categoryId);
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ $editing ? route('items.show', $form->item) : route('items.index') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">{{ $editing ? 'Edit item' : 'New item' }}</span>
        <span class="flex-1"></span>
        <button type="button" wire:click="save"
            class="cursor-pointer pr-1.5 text-base font-bold text-accent {{ trim($form->name) === '' ? 'opacity-40' : '' }}">
            Save
        </button>
    </div>

    <div class="flex-1 px-5 pb-8 lg:px-[30px]">
        {{-- Photo + name --}}
        <div class="mb-[18px] flex gap-3.5">
            <div class="relative shrink-0" x-data>
                <input type="file" accept="image/*" x-ref="file" class="hidden"
                    x-on:change="$event.target.files[0] && window.shrinkPhoto($event.target.files[0]).then((file) => { $wire.upload('photo', file); $refs.file.value = '' })">
                <button type="button" x-on:click="$refs.file.click()"
                    class="relative block size-[84px] cursor-pointer overflow-hidden rounded-2xl">
                    @if ($photo?->isPreviewable())
                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="size-full object-cover">
                    @elseif (! $removePhoto && $form->item?->photo_path)
                        <img src="{{ $form->item->photoUrl() }}" alt="" class="size-full object-cover">
                    @else
                        <x-ui.ph class="size-full rounded-2xl" icon="camera" :tint="$selectedCategory?->color" :icon-size="24" />
                    @endif
                    <span wire:loading wire:target="photo"
                        class="absolute inset-0 flex items-center justify-center bg-[rgba(8,10,15,0.45)] text-[11px] font-bold text-white">
                        Uploading…
                    </span>
                </button>
                @if ($photo || (! $removePhoto && $form->item?->photo_path))
                    <button type="button" wire:click="clearPhoto"
                        class="absolute -top-1.5 -right-1.5 flex size-6 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink-2 shadow-sm">
                        <x-icon name="x" :size="13" :stroke="2.2" />
                    </button>
                @endif
            </div>
            <div class="flex flex-1 flex-col justify-center">
                <x-ui.field label="Name" name="form.name" placeholder="What is it?" required
                    wire:model.live.debounce.300ms="form.name" />
            </div>
        </div>
        @error('photo')<p class="-mt-3 mb-3 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror

        {{-- Possible duplicates (create only) --}}
        @if (! $editing && $this->possibleDuplicates->isNotEmpty())
            <div class="-mt-2 mb-4">
                <div class="mb-1.5 text-xs font-semibold text-ink-3">Already in your inventory?</div>
                <div class="flex flex-col overflow-hidden rounded-[14px] border border-line">
                    @foreach ($this->possibleDuplicates as $match)
                        <a href="{{ route('items.show', $match) }}" wire:navigate wire:key="dup-{{ $match->id }}"
                            class="flex items-center gap-2.5 border-b border-line bg-surface px-3 py-2 last:border-b-0 hover:bg-fill">
                            <x-item-thumb class="size-9 rounded-lg" :item="$match" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[13.5px] font-semibold">{{ $match->name }}</div>
                                <div class="flex items-center gap-1 truncate text-[11.5px] font-semibold text-accent">
                                    <x-icon name="map-pin" :size="11" :stroke="2" />
                                    @if ($match->place_id)
                                        {{ implode(' › ', $this->placeIndex->breadcrumb($match->place_id)) }}
                                    @else
                                        <span class="text-ink-4">No location</span>
                                    @endif
                                </div>
                            </div>
                            <x-icon name="chevron-right" :size="15" class="shrink-0 text-ink-3" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mb-4 flex items-center gap-2.5">
            <span class="text-xs font-semibold whitespace-nowrap text-ink-3">Everything below is optional</span>
            <div class="flex-1 border-t border-dashed border-line-2"></div>
        </div>

        <div class="flex flex-col gap-[18px]">
            {{-- Description --}}
            <div>
                <label class="mb-[7px] block text-[12.5px] font-bold text-ink-2">Description</label>
                <div class="rounded-btn border border-line-2 bg-surface px-3.5 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
                    <textarea wire:model="form.note" rows="2" placeholder="Add a note…"
                        class="w-full resize-none bg-transparent py-[13px] text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3"></textarea>
                </div>
                @error('form.note')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
            </div>

            {{-- Category (searchable — real inventories have too many for chips) --}}
            <div>
                <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Category</div>
                <x-category-combobox :categories="$this->categories" property="form.categoryId" />
                @error('form.categoryId')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
            </div>

            {{-- Location + qty --}}
            <div class="flex gap-3">
                <div class="min-w-0 flex-1">
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Location</div>
                    <button type="button" wire:click="$set('placePickerOpen', true)"
                        class="flex min-h-[50px] w-full cursor-pointer items-center gap-2.5 rounded-btn border border-line-2 bg-surface px-3.5 text-left">
                        <x-icon name="map-pin" :size="19" class="shrink-0 text-ink-3" />
                        <span class="flex-1 truncate text-[15.5px] font-medium {{ $form->placeId ? '' : 'text-ink-3' }}">
                            {{ $form->placeId ? implode(' › ', $this->placeIndex->breadcrumb($form->placeId)) : 'No location' }}
                        </span>
                        <x-icon name="chevron-down" :size="16" class="shrink-0 text-ink-3" />
                    </button>
                    @error('form.placeId')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
                </div>
                <div class="w-[92px] shrink-0">
                    <x-ui.field label="Qty" name="form.qty" icon="hash" inputmode="numeric" wire:model="form.qty" />
                </div>
            </div>

            {{-- Dimensions --}}
            @include('livewire.partials.dim-fields', ['prefix' => 'form', 'label' => 'Dimensions'])

            {{-- Tags --}}
            @if ($this->tags->isNotEmpty())
                <div>
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Tags</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->tags as $tag)
                            <x-ui.chip wire:key="tag-{{ $tag->id }}" :dot="$tag->color"
                                :on="in_array($tag->id, $form->tagIds, true)"
                                :outline="! in_array($tag->id, $form->tagIds, true)"
                                :title="$tag->description"
                                wire:click="toggleTag({{ $tag->id }})">
                                {{ $tag->label }}
                            </x-ui.chip>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Value + warranty --}}
            <div class="flex gap-3">
                <div class="min-w-0 flex-1">
                    <x-ui.field label="Approx. value" name="form.value" icon="star" inputmode="decimal" placeholder="0"
                        autocomplete="off" x-data="moneyInput('form.value')" x-on:input="onInput" />
                </div>
                <div class="min-w-0 flex-1">
                    <x-ui.field label="Warranty until" name="form.warrantyUntil" icon="shield" type="date"
                        wire:model="form.warrantyUntil" />
                </div>
            </div>
        </div>

        <x-ui.btn variant="primary" class="mt-[26px] w-full {{ trim($form->name) === '' ? 'opacity-50' : '' }}"
            wire:click="save">
            {{ $editing ? 'Save changes' : 'Add item' }}
        </x-ui.btn>
    </div>

    {{-- Place picker sheet --}}
    @if ($placePickerOpen)
        <x-ui.sheet title="Location" close="closePlacePicker">
            <div class="flex max-h-[50vh] flex-col overflow-y-auto rounded-[14px] border border-line">
                <button type="button" wire:click="pickPlace(null)"
                    class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left {{ $form->placeId === null ? 'bg-accent-soft' : 'hover:bg-fill' }}">
                    <span class="flex-1 text-[14px] font-semibold {{ $form->placeId === null ? 'text-accent-ink' : 'text-ink-3' }}">No location</span>
                </button>
                @foreach ($this->placeIndex->flatten() as $entry)
                    <button type="button" wire:key="pp-{{ $entry['place']->id }}"
                        wire:click="pickPlace({{ $entry['place']->id }})"
                        class="flex cursor-pointer items-center gap-2.5 border-b border-line px-3.5 py-2.5 text-left last:border-0 {{ $form->placeId === $entry['place']->id ? 'bg-accent-soft' : 'hover:bg-fill' }}"
                        style="padding-left: {{ 14 + $entry['depth'] * 18 }}px">
                        <x-icon :name="$entry['place']->glyph ?: 'box'" :size="16"
                            class="{{ $form->placeId === $entry['place']->id ? 'text-accent-ink' : 'text-ink-3' }}" />
                        <span class="flex-1 text-[14px] font-semibold {{ $form->placeId === $entry['place']->id ? 'text-accent-ink' : '' }}">
                            {{ $entry['place']->label }}
                        </span>
                    </button>
                @endforeach
            </div>
        </x-ui.sheet>
    @endif
</div>
