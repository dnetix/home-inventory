<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col lg:mx-0 lg:max-w-5xl">
    {{-- Nav bar (mobile — desktop heading lives in the top bar) --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:hidden">
        <a href="{{ route('more') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">Tags</span>
        <span class="flex-1"></span>
        <span class="pr-2 text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->tags->count() }} tags</span>
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Tags"
            :subtitle="$this->tags->count().' '.Str::plural('tag', $this->tags->count())" />
    @endteleport

    <div class="flex-1 px-5 pb-8 lg:flex lg:items-start lg:gap-[18px] lg:px-[30px] lg:pt-[18px]">
        {{-- Existing tags --}}
        <div class="mb-[22px] lg:mb-0 lg:min-w-0 lg:flex-1">
            <x-ui.card class="divide-y divide-line px-3.5">
                @forelse ($this->tags as $tag)
                    <button type="button" wire:key="tag-{{ $tag->id }}" wire:click="startEdit({{ $tag->id }})"
                        class="flex w-full cursor-pointer items-center gap-[11px] py-3 text-left">
                        <span class="size-2.5 shrink-0 rounded-[3px]" style="background: {{ $tag->color }}"></span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block truncate text-[15px] font-semibold', 'text-accent' => $form->tag?->id === $tag->id])>
                                {{ $tag->label }}
                            </span>
                            @if ($tag->description)
                                <span class="mt-0.5 block text-xs font-medium text-ink-3">{{ $tag->description }}</span>
                            @endif
                        </span>
                        <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $tag->items_count }}</span>
                        <x-icon name="dots" :size="16" class="shrink-0 text-ink-4" />
                    </button>
                @empty
                    <div class="py-4 text-center text-[13.5px] font-medium text-ink-3">
                        No tags yet — create the first one with the form.
                    </div>
                @endforelse
            </x-ui.card>
        </div>

        {{-- New / edit tag --}}
        @php
            $tagRgb = sscanf($form->color, '#%02x%02x%02x');
        @endphp
        <x-ui.card class="px-4 pt-4 pb-[18px] lg:w-[380px] lg:shrink-0"
            wire:key="tag-editor-{{ $form->tag?->id ?? 'new' }}"
            x-data="{
                rgb: [{{ implode(', ', $tagRgb) }}],
                get hex() { return '#' + this.rgb.map(v => (+v).toString(16).padStart(2, '0')).join('') },
                sync() { $wire.set('form.color', this.hex, false) },
                pick(hexColor) {
                    this.rgb = [1, 3, 5].map(i => parseInt(hexColor.slice(i, i + 2), 16));
                    this.sync();
                },
            }">
            <div class="mb-3.5 flex items-center justify-between">
                <span class="text-base font-semibold">{{ $form->tag !== null ? 'Edit tag' : 'New tag' }}</span>
                @if ($form->tag !== null)
                    <button type="button" wire:click="cancelEdit"
                        class="cursor-pointer text-[13px] font-bold text-ink-3 transition hover:text-ink-2">
                        Cancel
                    </button>
                @endif
            </div>

            <x-ui.field name="form.label" icon="tag" placeholder="Tag name" wire:model="form.label" />

            <div class="mt-4 mb-[7px] text-[12.5px] font-bold text-ink-2">Notes</div>
            <div class="rounded-btn border border-line-2 bg-surface px-3.5 transition focus-within:border-accent focus-within:ring-[3.5px] focus-within:ring-accent-soft">
                <textarea wire:model="form.description" rows="2" placeholder="Why this tag exists, what belongs in it…"
                    class="w-full resize-none bg-transparent py-[13px] text-[15.5px] font-medium text-ink outline-none placeholder:text-ink-3"></textarea>
            </div>
            @error('form.description')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror

            <div class="mt-4 mb-[7px] text-[12.5px] font-bold text-ink-2">Color</div>
            <div class="mb-4 flex flex-wrap items-center gap-[9px]">
                @foreach (\App\Livewire\Forms\TagForm::COLORS as $color)
                    <button type="button" x-on:click="pick('{{ $color }}')"
                        class="size-[30px] cursor-pointer rounded-[9px] transition"
                        x-bind:class="hex.toLowerCase() === '{{ $color }}' ? 'ring-2 ring-ink ring-offset-2 ring-offset-surface' : ''"
                        style="background: {{ $color }}"></button>
                @endforeach
            </div>

            {{-- Live preview --}}
            <div class="mb-4 flex items-center gap-3 rounded-[13px] bg-fill px-3.5 py-3">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-line-2 bg-surface px-3 py-1.5 text-[13px] font-semibold">
                    <span class="size-2 rounded-[3px]" x-bind:style="{ background: hex }"></span>
                    <span x-text="$wire.form.label.trim() || 'preview'"></span>
                </span>
                <span class="ml-auto font-mono text-[13px] font-medium text-ink-3 uppercase" x-text="hex"></span>
            </div>

            {{-- RGB sliders --}}
            <div class="flex flex-col gap-3.5">
                @foreach ([['R', 0, '#d8514a'], ['G', 1, '#4e9b54'], ['B', 2, '#4f74e3']] as [$channel, $idx, $sliderColor])
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 text-[13.5px] font-bold text-ink-2">{{ $channel }}</span>
                        <input type="range" min="0" max="255" x-model="rgb[{{ $idx }}]" x-on:change="sync"
                            class="h-1 flex-1 cursor-pointer" style="accent-color: {{ $sliderColor }}">
                        <span class="w-[30px] text-right text-[13.5px] font-medium text-ink-2 tabular-nums"
                            x-text="rgb[{{ $idx }}]"></span>
                    </div>
                @endforeach
            </div>

            @error('form.label')<p class="mt-3 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
            @error('form.color')<p class="mt-3 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror

            <x-ui.btn variant="primary" class="mt-[18px] w-full {{ trim($form->label) === '' ? 'opacity-50' : '' }}"
                wire:click="save">
                {{ $form->tag !== null ? 'Save changes' : 'Create tag' }}
            </x-ui.btn>

            @if ($form->tag !== null)
                <button type="button" wire:click="delete({{ $form->tag->id }})"
                    wire:confirm="Delete “{{ $form->tag->label }}”? It will be removed from every item using it."
                    class="mt-3 flex w-full cursor-pointer items-center justify-center gap-2 py-2 text-[14px] font-bold text-bad">
                    <x-icon name="trash" :size="17" /> Delete tag
                </button>
            @endif
        </x-ui.card>
    </div>
</div>
