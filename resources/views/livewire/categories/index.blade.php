<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
    {{-- Nav bar --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:px-[30px] lg:pt-[26px]">
        <a href="{{ route('more') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">Categories</span>
        <span class="flex-1"></span>
        <x-ui.icon-btn icon="plus" accent wire:click="openCreate" />
    </div>

    <div class="flex-1 px-5 pb-6 lg:px-[30px]">
        @if ($this->categories->isEmpty())
            <x-empty-state icon="layers" title="No categories yet" sub="Group your items with the + button." />
        @else
            <x-ui.card class="px-3.5 py-1">
                @foreach ($this->categories->whereNull('parent_id') as $top)
                    @php
                        $kids = $this->categories->where('parent_id', $top->id);
                        $isOpen = in_array($top->id, $open, true);
                    @endphp
                    <div wire:key="cat-{{ $top->id }}" @class(['border-t border-line' => ! $loop->first])>
                        <div class="flex items-center gap-[11px] py-[13px]">
                            <button type="button" @if ($kids->isNotEmpty()) wire:click="toggle({{ $top->id }})" @endif
                                class="flex size-5 shrink-0 items-center justify-center {{ $kids->isNotEmpty() ? 'cursor-pointer text-ink-2' : 'text-transparent' }}">
                                <x-icon :name="$kids->isNotEmpty() && $isOpen ? 'chevron-down' : 'chevron-right'" :size="16" :stroke="2" />
                            </button>
                            <span class="size-2.5 shrink-0 rounded-[3px]" style="background: {{ $top->color }}"></span>
                            <span class="flex-1 text-[15px] font-semibold">{{ $top->label }}</span>
                            <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->counts[$top->id] ?? 0 }}</span>
                            <button type="button" wire:click="openEdit({{ $top->id }})"
                                class="cursor-pointer text-ink-4 transition hover:text-ink-2">
                                <x-icon name="dots" :size="16" />
                            </button>
                        </div>
                        @if ($isOpen)
                            @foreach ($kids as $kid)
                                <div wire:key="cat-{{ $kid->id }}"
                                    class="flex items-center gap-[11px] border-t border-line py-[11px] pl-[27px]">
                                    <span class="size-2 shrink-0 rounded-[2.5px] opacity-70" style="background: {{ $kid->color }}"></span>
                                    <span class="flex-1 text-[13.5px] font-medium">{{ $kid->label }}</span>
                                    <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->counts[$kid->id] ?? 0 }}</span>
                                    <button type="button" wire:click="openEdit({{ $kid->id }})"
                                        class="cursor-pointer text-ink-4 transition hover:text-ink-2">
                                        <x-icon name="dots" :size="16" />
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </x-ui.card>
        @endif

        <button type="button" wire:click="openCreate"
            class="mt-3.5 flex cursor-pointer items-center gap-2 px-0.5 py-1 text-accent">
            <x-icon name="plus" :size="18" :stroke="2" />
            <span class="text-[15px] font-semibold">New category</span>
            <span class="ml-auto text-xs font-medium text-ink-3">can nest under a parent</span>
        </button>
    </div>

    {{-- Create / edit sheet --}}
    @if ($editorOpen)
        <x-ui.sheet :title="$form->category !== null ? 'Edit category' : 'New category'" close="closeEditor">
            <div class="flex flex-col gap-4">
                <x-ui.field label="Name" name="form.label" icon="layers" placeholder="Category name" required
                    wire:model="form.label" />

                <div>
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Color</div>
                    <div class="flex flex-wrap items-center gap-[9px]">
                        @foreach (\App\Livewire\Forms\CategoryForm::COLORS as $color)
                            <button type="button" wire:click="$set('form.color', '{{ $color }}')"
                                class="size-[30px] cursor-pointer rounded-[9px] transition {{ strtolower($form->color) === $color ? 'ring-2 ring-ink ring-offset-2 ring-offset-surface' : '' }}"
                                style="background: {{ $color }}"></button>
                        @endforeach
                    </div>
                    @error('form.color')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Icon</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Livewire\Forms\CategoryForm::GLYPHS as $glyph)
                            <button type="button" wire:click="$set('form.glyph', '{{ $glyph }}')"
                                class="flex size-10 cursor-pointer items-center justify-center rounded-xl border transition {{ $form->glyph === $glyph ? 'border-accent bg-accent-soft text-accent-ink' : 'border-line-2 bg-surface text-ink-2' }}">
                                <x-icon :name="$glyph" :size="18" />
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Nest under</div>
                    <select wire:model="form.parentId"
                        class="min-h-[50px] w-full cursor-pointer rounded-btn border border-line-2 bg-surface px-3.5 text-[15.5px] font-medium text-ink outline-none focus:border-accent">
                        <option value="">Top level</option>
                        @foreach ($this->categories->whereNull('parent_id') as $top)
                            @if ($form->category === null || $form->category->id !== $top->id)
                                <option value="{{ $top->id }}">{{ $top->label }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('form.parentId')<p class="mt-1.5 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror
                </div>
            </div>

            <x-ui.btn variant="primary" class="mt-[22px] w-full {{ trim($form->label) === '' ? 'opacity-50' : '' }}"
                wire:click="save">
                {{ $form->category !== null ? 'Save changes' : 'Add category' }}
            </x-ui.btn>

            @if ($form->category !== null)
                <button type="button" wire:click="delete({{ $form->category->id }})"
                    wire:confirm="Delete this category? Its items become uncategorized."
                    class="mt-3 flex cursor-pointer items-center justify-center gap-2 py-2 text-[14px] font-bold text-bad">
                    <x-icon name="trash" :size="17" /> Delete category
                </button>
            @endif
        </x-ui.sheet>
    @endif
</div>
