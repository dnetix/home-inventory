@php
    use App\Enums\UpkeepKind;
    use App\Enums\UpkeepStatus;
    use App\Livewire\Forms\UpkeepTaskForm;

    $cal = $this->calendar;
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col lg:mx-0 lg:max-w-none">
    {{-- Header (mobile — desktop heading + actions live in the top bar) --}}
    <div class="flex items-end justify-between gap-3 px-5 pt-8 lg:hidden">
        <div>
            <h1 class="text-[30px] font-extrabold tracking-[-0.4px]">Upkeep</h1>
            <p class="mt-[3px] text-[13.5px] font-medium text-ink-2">Maintenance &amp; expiry</p>
        </div>
        <x-ui.icon-btn icon="plus" accent wire:click="openCreate" />
    </div>

    @teleport('#topbar-page')
        <x-topbar-heading title="Upkeep" subtitle="Maintenance & expiry" />
    @endteleport

    @teleport('#topbar-actions')
        <x-ui.btn variant="tonal" size="sm" wire:click="openCreate">
            <x-icon name="plus" :size="16" /> Add task
        </x-ui.btn>
    @endteleport

    <div class="grid flex-1 items-start gap-[18px] px-5 pt-1 pb-6 lg:grid-cols-[3fr_1fr] lg:px-[30px] lg:pt-4 lg:pb-[30px]">
        {{-- Month calendar (mobile top; desktop right rail at 1/4 width) --}}
        <div class="min-w-0 lg:col-start-2 lg:row-start-1">
            <x-ui.card class="px-4 pt-3.5 pb-4">
                <div class="mb-3 flex items-center justify-between">
                    <button type="button" wire:click="previousMonth" class="cursor-pointer p-1 text-ink-3">
                        <x-icon name="chevron-left" :size="18" />
                    </button>
                    <span class="text-base font-semibold">{{ $cal['label'] }}</span>
                    <button type="button" wire:click="nextMonth" class="cursor-pointer p-1 text-ink-3">
                        <x-icon name="chevron-right" :size="18" />
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-[3px] text-center">
                    @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dow)
                        <div class="pb-1 text-xs font-semibold text-ink-3">{{ $dow }}</div>
                    @endforeach
                    @for ($blank = 0; $blank < $cal['blanks']; $blank++)
                        <div></div>
                    @endfor
                    @for ($day = 1; $day <= $cal['days']; $day++)
                        @php $isToday = $cal['isCurrentMonth'] && $day === today()->day; @endphp
                        <div class="flex aspect-square flex-col items-center justify-center rounded-[9px] text-[13px] lg:aspect-auto lg:h-[46px] {{ $isToday ? 'bg-accent font-bold text-on-accent' : 'font-medium' }}">
                            <span class="tabular-nums">{{ $day }}</span>
                            <span class="mt-0.5 size-[5px] rounded-full"
                                style="background: {{ isset($cal['dots'][$day]) ? ($isToday ? 'rgba(255,255,255,.9)' : 'var(--'.$cal['dots'][$day].')') : 'transparent' }}"></span>
                        </div>
                    @endfor
                </div>
                <div class="mt-2.5 flex items-center justify-center gap-4 text-[11px] font-semibold text-ink-3">
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-bad"></span> overdue</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-warn"></span> scheduled</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-good"></span> done</span>
                </div>
            </x-ui.card>
        </div>

        {{-- Upcoming + recently done (desktop main 3/4 column) --}}
        <div class="min-w-0 lg:col-start-1 lg:row-start-1">
            {{-- Upcoming agenda --}}
            <x-ui.section-label class="mb-3">Upcoming</x-ui.section-label>
            @if ($this->agenda->isEmpty())
                <x-ui.card class="mb-6 flex items-center gap-3 px-4 py-4">
                    <span class="flex size-[38px] items-center justify-center rounded-[11px] bg-good-soft text-good">
                        <x-icon name="check" :size="20" :stroke="2" />
                    </span>
                    <span>
                        <span class="block text-[15px] font-semibold">Nothing scheduled</span>
                        <span class="block text-[13.5px] font-medium text-ink-2">Add a task with the + button.</span>
                    </span>
                </x-ui.card>
            @else
                <div class="mb-6 flex flex-col gap-3">
                    @foreach ($this->agenda as $task)
                        @php
                            $status = $task->status();
                            $tone = match ($status) {
                                UpkeepStatus::Overdue => 'bad',
                                UpkeepStatus::Soon => 'warn',
                                default => 'accent',
                            };
                        @endphp
                        <x-ui.card wire:key="task-{{ $task->id }}" class="flex cursor-pointer items-center gap-[13px] px-3.5 py-3"
                            wire:click="openEdit({{ $task->id }})">
                            <span class="w-11 shrink-0 text-center">
                                <span class="block text-xs font-semibold text-ink-3">{{ $task->due_date->format('M') }}</span>
                                <span class="block text-[19px] leading-none font-bold tabular-nums">{{ $task->due_date->day }}</span>
                            </span>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                style="background: var(--{{ $tone }}-soft); color: var(--{{ $tone === 'accent' ? 'accent' : $tone }})">
                                <x-icon :name="$task->kind === UpkeepKind::Expiry ? 'shield' : 'wrench'" :size="19" :stroke="1.9" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[15px] font-semibold">{{ $task->task }}</span>
                                <span class="mt-0.5 block truncate text-xs font-medium text-ink-3">
                                    @if ($task->item_id)
                                        <a href="{{ route('items.show', $task->item_id) }}" wire:navigate x-data x-on:click.stop
                                            class="font-semibold text-accent transition hover:underline">{{ $task->subject }}</a>
                                    @else
                                        {{ $task->subject }}
                                    @endif
                                    {{ $task->every ? '· '.strtolower(UpkeepTaskForm::RECURRENCES[$task->every] ?? '') : '' }}
                                </span>
                            </span>
                            <button type="button" wire:click.stop="startCompleting({{ $task->id }})"
                                class="flex size-[38px] shrink-0 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line-2 text-ink-3 transition hover:border-good hover:text-good active:scale-90">
                                <x-icon name="check" :size="18" :stroke="2.2" />
                            </button>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif

            {{-- Recently done --}}
            @include('livewire.upkeep.partials.done-log')
        </div>
    </div>

    {{-- Task editor sheet --}}
    @if ($editorOpen)
        <x-ui.sheet :title="$form->upkeepTask !== null ? 'Edit task' : 'New task'" close="closeEditor">
            <div class="flex flex-col gap-4">
                @if ($form->itemId !== null)
                    {{-- Linked tasks are created from the item's own page; the link is fixed here --}}
                    <div>
                        <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Linked item</div>
                        <div class="flex min-h-[50px] items-center gap-2.5 rounded-btn border border-line-2 bg-fill px-3.5">
                            <x-icon name="box" :size="18" class="shrink-0 text-ink-3" />
                            <span class="flex-1 truncate text-[15px] font-semibold">{{ $form->upkeepTask?->subject }}</span>
                        </div>
                    </div>
                @else
                    <x-ui.field label="Subject" name="form.subject" icon="wrench" placeholder="e.g. Furnace" required
                        wire:model="form.subject" />
                @endif

                <div>
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Kind</div>
                    <x-ui.seg>
                        <x-ui.seg-btn :on="$form->kind === 'maint'" wire:click="$set('form.kind', 'maint')">
                            <x-icon name="wrench" :size="14" /> Maintenance
                        </x-ui.seg-btn>
                        <x-ui.seg-btn :on="$form->kind === 'expiry'" wire:click="$set('form.kind', 'expiry')">
                            <x-icon name="shield" :size="14" /> Expiry
                        </x-ui.seg-btn>
                    </x-ui.seg>
                </div>

                <x-ui.field label="Task" name="form.task" placeholder="e.g. Replace air filter" required
                    wire:model="form.task" />

                <x-ui.field label="Due date" name="form.dueDate" icon="calendar" type="date" required
                    wire:model="form.dueDate" />

                <div>
                    <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">Repeat</div>
                    <select wire:model="form.every"
                        class="min-h-[50px] w-full cursor-pointer rounded-btn border border-line-2 bg-surface px-3.5 text-[15.5px] font-medium text-ink outline-none focus:border-accent">
                        @foreach (UpkeepTaskForm::RECURRENCES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs font-medium text-ink-3">
                        {{ $form->every === '' ? 'One-time task — done once completed.' : 'Reschedules automatically each time you log it done.' }}
                    </p>
                </div>
            </div>

            <x-ui.btn variant="primary" class="mt-[22px] w-full {{ trim($form->task) === '' ? 'opacity-50' : '' }}"
                wire:click="save">
                {{ $form->upkeepTask !== null ? 'Save changes' : 'Add task' }}
            </x-ui.btn>

            @if ($form->upkeepTask !== null)
                <button type="button" wire:click="deleteTask" wire:confirm="Delete this task? Its history stays in the log."
                    class="mt-3 flex cursor-pointer items-center justify-center gap-2 py-2 text-[14px] font-bold text-bad">
                    <x-icon name="trash" :size="17" /> Delete task
                </button>
            @endif
        </x-ui.sheet>
    @endif

    {{-- Mark-as-done sheet --}}
    @if ($this->completingTask)
        <x-ui.sheet title="Mark as done" close="cancelCompleting">
            <div class="mb-[18px] flex items-center gap-3 rounded-[14px] bg-fill px-[13px] py-[11px]">
                <span class="flex size-[38px] items-center justify-center rounded-[11px] bg-warn-soft text-warn">
                    <x-icon :name="$this->completingTask->kind === UpkeepKind::Expiry ? 'shield' : 'wrench'" :size="18" :stroke="1.8" />
                </span>
                <span class="flex-1">
                    <span class="block text-[13.5px] font-semibold">{{ $this->completingTask->task }}</span>
                    <span class="block text-xs font-medium text-ink-3">
                        {{ $this->completingTask->subject }} · was due {{ $this->completingTask->due_date?->format('Y-m-d') }}
                    </span>
                </span>
            </div>

            <div class="mb-[7px] text-[12.5px] font-bold text-ink-2">When was it done?</div>
            <div class="mb-4 flex flex-wrap gap-2">
                <x-ui.chip :on="$completedOn === 'today'" :outline="$completedOn !== 'today'"
                    wire:click="$set('completedOn', 'today')">
                    <x-icon name="calendar" :size="13" /> Today
                </x-ui.chip>
                <x-ui.chip :on="$completedOn === 'yesterday'" :outline="$completedOn !== 'yesterday'"
                    wire:click="$set('completedOn', 'yesterday')">
                    <x-icon name="calendar" :size="13" /> Yesterday
                </x-ui.chip>
                <x-ui.chip :on="! in_array($completedOn, ['today', 'yesterday'], true)"
                    :outline="in_array($completedOn, ['today', 'yesterday'], true)"
                    wire:click="$set('completedOn', '{{ today()->toDateString() }}')">
                    <x-icon name="calendar" :size="13" /> Pick date…
                </x-ui.chip>
            </div>

            @if (! in_array($completedOn, ['today', 'yesterday'], true))
                <div class="mb-4">
                    <x-ui.field name="completedOn" icon="calendar" type="date" wire:model.live="completedOn" />
                </div>
            @endif

            @if ($this->completingTask->isRecurring())
                <div class="mb-1 flex items-center gap-2 text-[13.5px] font-medium text-ink-2">
                    <x-icon name="clock" :size="16" :stroke="1.8" />
                    Next service →
                    <b class="text-ink">{{ $this->completionDate()->add($this->completingTask->recurrence())->format('Y-m-d') }}</b>
                </div>
            @endif

            <x-ui.btn variant="primary" class="mt-3.5 w-full" wire:click="complete">
                <x-icon name="check" :size="18" /> Log as done
            </x-ui.btn>
        </x-ui.sheet>
    @endif
</div>
