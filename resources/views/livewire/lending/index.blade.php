<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col lg:mx-0 lg:max-w-none">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Lent out</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">{{ $this->summary['active'] }} out now</p>
        </div>
        <x-ui.btn variant="primary" size="sm" wire:click="openLend">
            <x-icon name="hand" :size="16" /> Lend an item
        </x-ui.btn>
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Lent out" :subtitle="$this->summary['active'] . ' out now'" />
    @endteleport

    @teleport('#topbar-actions')
        <x-ui.btn variant="tonal" size="sm" wire:click="openLend">
            <x-icon name="hand" :size="16" /> Lend an item
        </x-ui.btn>
    @endteleport

    {{-- Filter chips --}}
    <div class="flex gap-2 px-5 py-3 lg:px-[30px]">
        <x-ui.chip :on="$filter === 'all'" :outline="$filter !== 'all'" wire:click="setFilter('all')">All</x-ui.chip>
        <x-ui.chip :on="$filter === 'overdue'" :outline="$filter !== 'overdue'" wire:click="setFilter('overdue')">
            Overdue · {{ $this->summary['overdue'] }}
        </x-ui.chip>
        <x-ui.chip :on="$filter === 'returned'" :outline="$filter !== 'returned'" wire:click="setFilter('returned')">Returned</x-ui.chip>
    </div>

    <div class="flex flex-1 gap-[18px] px-5 pb-6 lg:px-[30px] lg:pb-[30px]">
        {{-- Loans list --}}
        <div class="min-w-0 flex-1">
            @if ($this->lends->isEmpty())
                <x-empty-state icon="hand" title="Nothing here"
                    :sub="$filter === 'returned' ? 'No returned items yet.' : 'Nothing is lent out.'" />
            @else
                <div class="flex flex-col gap-[11px]">
                    @foreach ($this->lends as $lend)
                        <x-ui.card wire:key="lend-{{ $lend->id }}" class="flex items-center gap-[13px] px-3.5 py-3">
                            <x-ui.ph class="size-[46px] rounded-xl" :icon="$lend->item->category?->glyph ?? 'box'"
                                :tint="$lend->item->category?->color" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('items.show', $lend->item) }}" wire:navigate
                                    class="block truncate text-[15px] font-semibold">{{ $lend->item->name }}</a>
                                <div class="mt-[3px] flex items-center gap-[5px] text-xs font-semibold text-ink-3">
                                    <x-icon name="user" :size="13" :stroke="1.9" />
                                    {{ $lend->person }} · since {{ $lend->out_date->format('M j') }}
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                @if ($lend->returned_at)
                                    <x-ui.pill variant="good">
                                        <x-icon name="check" :size="11" :stroke="2.4" /> returned
                                    </x-ui.pill>
                                @elseif ($lend->isOverdue())
                                    <x-ui.pill variant="bad">overdue</x-ui.pill>
                                @else
                                    <x-ui.pill variant="good">{{ $lend->due_date ? 'due '.$lend->due_date->format('M j') : 'no date' }}</x-ui.pill>
                                @endif
                                @if (! $lend->returned_at)
                                    <button type="button" wire:click="returnLend({{ $lend->id }})"
                                        class="cursor-pointer text-xs font-bold text-accent underline">Mark returned</button>
                                @endif
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Desktop summary rail --}}
        <aside class="hidden w-[320px] shrink-0 flex-col gap-3.5 lg:flex">
            <div class="grid grid-cols-2 gap-3.5">
                <x-ui.card class="px-4 py-3.5">
                    <div class="text-[26px] font-extrabold tabular-nums">{{ $this->summary['active'] }}</div>
                    <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Out now</div>
                </x-ui.card>
                <x-ui.card class="px-4 py-3.5">
                    <div class="text-[26px] font-extrabold tabular-nums {{ $this->summary['overdue'] > 0 ? 'text-bad' : '' }}">
                        {{ $this->summary['overdue'] }}
                    </div>
                    <div class="mt-0.5 text-[12.5px] font-semibold text-ink-2">Overdue</div>
                </x-ui.card>
            </div>
            <x-ui.card class="px-4 py-3.5">
                <x-ui.section-label>Value on loan</x-ui.section-label>
                <div class="mt-1.5 text-[22px] font-bold tabular-nums">{{ $this->summary['valueOnLoan']->format() }}</div>
            </x-ui.card>
            @if ($this->summary['borrowers']->isNotEmpty())
                <x-ui.card class="px-4 py-3.5">
                    <x-ui.section-label class="mb-2.5">Borrowers</x-ui.section-label>
                    <div class="flex flex-col gap-2.5">
                        @foreach ($this->summary['borrowers'] as $borrower)
                            <div class="flex items-center gap-2.5">
                                <span class="flex size-7 items-center justify-center rounded-full bg-accent-soft text-[11px] font-bold text-accent-ink">
                                    {{ mb_substr($borrower['person'], 0, 1) }}
                                </span>
                                <span class="flex-1 text-[13.5px] font-semibold">{{ $borrower['person'] }}</span>
                                <span class="text-[13px] font-medium text-ink-3 tabular-nums">
                                    {{ $borrower['count'] }} {{ Str::plural('item', $borrower['count']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </aside>
    </div>

    {{-- Lend sheet --}}
    @if ($lendOpen)
        @php $pickedItem = $form->itemId ? $this->lendableItems->firstWhere('id', $form->itemId) : null; @endphp
        <x-ui.sheet title="Lend item" close="closeLend">
            @if (! $itemPickerOpen)
                {{-- Item selector --}}
                <button type="button" wire:click="$set('itemPickerOpen', true)"
                    class="mb-4 flex w-full cursor-pointer items-center gap-3 rounded-[14px] border border-line bg-surface px-3.5 py-3 text-left shadow-sm">
                    @if ($pickedItem)
                        <x-ui.ph class="size-12 rounded-xl" :icon="$pickedItem->category?->glyph ?? 'box'"
                            :tint="$pickedItem->category?->color" />
                    @else
                        <span class="flex size-12 items-center justify-center rounded-xl bg-fill text-ink-3">
                            <x-icon name="box" :size="22" />
                        </span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[15px] font-semibold">{{ $pickedItem?->name ?? 'Choose an item' }}</span>
                        <span class="block text-xs font-medium text-ink-3">
                            {{ $pickedItem ? ($pickedItem->place?->label ?? 'No location') : 'Tap to pick from your inventory' }}
                        </span>
                    </span>
                    <span class="text-[13.5px] font-bold text-accent">{{ $pickedItem ? 'Change' : 'Pick' }}</span>
                </button>
                @error('form.itemId')<p class="-mt-2 mb-3 text-[13px] font-semibold text-bad">{{ $message }}</p>@enderror

                <div class="flex flex-col gap-4">
                    <x-ui.field label="Lend to" name="form.person" icon="user" placeholder="Who's borrowing it?"
                        required wire:model="form.person" />

                    @if ($this->suggestions->isNotEmpty())
                        <div>
                            <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Suggestions</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->suggestions as $person)
                                    <x-ui.chip outline wire:key="sg-{{ $person }}"
                                        wire:click="$set('form.person', '{{ addslashes($person) }}')">
                                        <x-icon name="user" :size="13" /> {{ $person }}
                                    </x-ui.chip>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <x-ui.field label="Due back" name="form.dueDate" icon="calendar" type="date"
                            wire:model.live="form.dueDate" />
                        <p class="mt-2 text-xs font-medium text-ink-3">
                            {{ $form->dueDate !== '' ? 'Should be returned by '.\Illuminate\Support\Carbon::parse($form->dueDate)->format('M j') : 'Leave empty for no return date' }}
                        </p>
                    </div>

                    @if ($form->dueDate !== '')
                        <x-ui.card flat class="flex items-center gap-3 px-3.5 py-3">
                            <span class="flex size-[38px] items-center justify-center rounded-[11px] bg-fill text-ink-2">
                                <x-icon name="bell" :size="18" :stroke="1.8" />
                            </span>
                            <span class="flex-1">
                                <span class="block text-[13.5px] font-semibold">Remind me</span>
                                <span class="block text-xs font-medium text-ink-3">1 day before it's due</span>
                            </span>
                            <x-ui.switch :checked="$form->remind" wire:model="form.remind" />
                        </x-ui.card>
                    @endif
                </div>

                <x-ui.btn variant="primary"
                    class="mt-6 w-full {{ $form->itemId === null || trim($form->person) === '' ? 'opacity-50' : '' }}"
                    wire:click="save">
                    <x-icon name="hand" :size="18" /> Mark as lent
                </x-ui.btn>
            @else
                {{-- Item picker --}}
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-[15px] font-bold">Choose item</span>
                    <button type="button" wire:click="$set('itemPickerOpen', false)"
                        class="cursor-pointer text-[13.5px] font-bold text-accent">Back</button>
                </div>
                <div class="flex max-h-[50vh] flex-col divide-y divide-line overflow-y-auto rounded-[14px] border border-line">
                    @forelse ($this->lendableItems as $item)
                        <button type="button" wire:key="li-{{ $item->id }}" wire:click="pickItem({{ $item->id }})"
                            class="flex cursor-pointer items-center gap-3 px-3.5 py-2.5 text-left hover:bg-fill">
                            <x-ui.ph class="size-[38px] rounded-[10px]" :icon="$item->category?->glyph ?? 'box'"
                                :tint="$item->category?->color" :icon-size="16" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[14px] font-semibold">{{ $item->name }}</span>
                                <span class="block text-xs font-medium text-ink-3">{{ $item->place?->label ?? 'No location' }}</span>
                            </span>
                            @if ($form->itemId === $item->id)
                                <x-icon name="check" :size="18" class="text-accent" />
                            @endif
                        </button>
                    @empty
                        <div class="px-4 py-6 text-center text-[13.5px] font-medium text-ink-3">
                            Everything is already lent out — or you have no items yet.
                        </div>
                    @endforelse
                </div>
            @endif
        </x-ui.sheet>
    @endif
</div>
