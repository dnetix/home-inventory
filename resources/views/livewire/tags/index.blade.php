<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('more') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">Tags</span>
        <span class="flex-1"></span>
        <span class="pr-2 text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->tags->count() }} tags</span>
    </div>

    <div class="flex-1 px-5 pb-8 lg:px-[30px]">
        {{-- Existing tags --}}
        <div class="mb-[22px] flex flex-wrap gap-2">
            @forelse ($this->tags as $tag)
                <span wire:key="tag-{{ $tag->id }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-line-2 px-3 py-1.5 text-[13px] font-semibold">
                    <span class="size-2 rounded-[3px]" style="background: {{ $tag->color }}"></span>
                    {{ $tag->label }}
                    <span class="text-ink-3 tabular-nums">{{ $tag->items_count }}</span>
                    <button type="button" wire:click="delete({{ $tag->id }})"
                        wire:confirm="Delete “{{ $tag->label }}”? It will be removed from {{ $tag->items_count }} {{ Str::plural('item', $tag->items_count) }}."
                        class="-mr-1 cursor-pointer text-ink-4 transition hover:text-bad">
                        <x-icon name="x" :size="13" :stroke="2.2" />
                    </button>
                </span>
            @empty
                <span class="text-[13.5px] font-medium text-ink-3">No tags yet — create the first one below.</span>
            @endforelse
        </div>

        {{-- New tag --}}
        <x-ui.card class="px-4 pt-4 pb-[18px]"
            x-data="{
                rgb: [192, 86, 74],
                get hex() { return '#' + this.rgb.map(v => (+v).toString(16).padStart(2, '0')).join('') },
                sync() { $wire.set('form.color', this.hex, false) },
                pick(hexColor) {
                    this.rgb = [1, 3, 5].map(i => parseInt(hexColor.slice(i, i + 2), 16));
                    this.sync();
                },
            }">
            <div class="mb-3.5 text-base font-semibold">New tag</div>

            <x-ui.field name="form.label" icon="tag" placeholder="Tag name" wire:model="form.label" />

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
                Create tag
            </x-ui.btn>
        </x-ui.card>
    </div>
</div>
