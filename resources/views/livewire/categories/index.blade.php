<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col lg:mx-0 lg:max-w-5xl">
    {{-- Nav bar (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex min-h-11 items-center gap-1.5 px-3 pt-4 pb-2 lg:hidden">
        <a href="{{ route('more') }}" wire:navigate
            class="-ml-1.5 flex size-[38px] shrink-0 items-center justify-center rounded-full text-accent transition active:scale-90">
            <x-icon name="chevron-left" :size="26" :stroke="2" />
        </a>
        <span class="text-[17px] font-bold tracking-[-0.2px]">Categories</span>
        <span class="flex-1"></span>
        <span class="pr-2 text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->categories->count() }}</span>
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Categories"
            :subtitle="$this->categories->count().' '.Str::plural('category', $this->categories->count())" />
    @endteleport

    @teleport('#topbar-actions')
        <x-ui.btn variant="tonal" size="sm" wire:click="cancelEdit">
            <x-icon name="plus" :size="15" /> New category
        </x-ui.btn>
    @endteleport

    <div class="flex-1 px-5 pb-8 lg:flex lg:items-start lg:gap-[18px] lg:px-[30px] lg:pt-[18px]">
        {{-- Category tree --}}
        <div class="mb-[22px] lg:mb-0 lg:min-w-0 lg:flex-1">
            @if ($this->categories->isEmpty())
                <x-empty-state icon="layers" title="No categories yet" sub="Group your items with the form." />
            @else
                <x-ui.card class="px-3.5 py-1">
                    @foreach ($this->categories->whereNull('parent_id') as $top)
                        @php
                            $kids = $this->categories->where('parent_id', $top->id);
                            $isOpen = in_array($top->id, $open, true);
                        @endphp
                        <div wire:key="cat-{{ $top->id }}" @class(['border-t border-line' => ! $loop->first])>
                            <div class="flex items-center gap-[11px]">
                                <button type="button" @if ($kids->isNotEmpty()) wire:click="toggle({{ $top->id }})" @endif
                                    class="flex size-5 shrink-0 items-center justify-center {{ $kids->isNotEmpty() ? 'cursor-pointer text-ink-2' : 'text-transparent' }}">
                                    <x-icon :name="$kids->isNotEmpty() && $isOpen ? 'chevron-down' : 'chevron-right'" :size="16" :stroke="2" />
                                </button>
                                <button type="button" wire:click="startEdit({{ $top->id }})"
                                    class="flex min-w-0 flex-1 cursor-pointer items-center gap-[11px] py-[13px] text-left">
                                    <span class="size-2.5 shrink-0 rounded-[3px]" style="background: {{ $top->color }}"></span>
                                    <span @class(['min-w-0 flex-1 truncate text-[15px] font-semibold', 'text-accent' => $form->category?->id === $top->id])>
                                        {{ $top->label }}
                                    </span>
                                    <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->counts[$top->id] ?? 0 }}</span>
                                    <x-icon name="dots" :size="16" class="shrink-0 text-ink-4" />
                                </button>
                            </div>
                            @if ($isOpen)
                                @foreach ($kids as $kid)
                                    <button type="button" wire:key="cat-{{ $kid->id }}" wire:click="startEdit({{ $kid->id }})"
                                        class="flex w-full cursor-pointer items-center gap-[11px] border-t border-line py-[11px] pl-[27px] text-left">
                                        <span class="size-2 shrink-0 rounded-[2.5px] opacity-70" style="background: {{ $kid->color }}"></span>
                                        <span @class(['min-w-0 flex-1 truncate text-[13.5px] font-medium', 'text-accent' => $form->category?->id === $kid->id])>
                                            {{ $kid->label }}
                                        </span>
                                        <span class="text-[13.5px] font-medium text-ink-3 tabular-nums">{{ $this->counts[$kid->id] ?? 0 }}</span>
                                        <x-icon name="dots" :size="16" class="shrink-0 text-ink-4" />
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </x-ui.card>
            @endif
        </div>

        {{-- New / edit category --}}
        <x-ui.card class="px-4 pt-4 pb-[18px] lg:w-[380px] lg:shrink-0">
            <div class="mb-3.5 flex items-center justify-between">
                <span class="text-base font-semibold">{{ $form->category !== null ? 'Edit category' : 'New category' }}</span>
                @if ($form->category !== null)
                    <button type="button" wire:click="cancelEdit"
                        class="cursor-pointer text-[13px] font-bold text-ink-3 transition hover:text-ink-2">
                        Cancel
                    </button>
                @endif
            </div>

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
                    @include('livewire.partials.glyph-picker')
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
                    class="mt-3 flex w-full cursor-pointer items-center justify-center gap-2 py-2 text-[14px] font-bold text-bad">
                    <x-icon name="trash" :size="17" /> Delete category
                </button>
            @endif
        </x-ui.card>
    </div>
</div>
