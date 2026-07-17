<?php

namespace App\Livewire\Upkeep;

use App\Actions\CompleteUpkeepTask;
use App\Enums\UpkeepStatus;
use App\Livewire\Forms\UpkeepTaskForm;
use App\Models\Item;
use App\Models\UpkeepLog;
use App\Models\UpkeepTask;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Upkeep')]
class Index extends Component
{
    use AuthorizesRequests;

    public UpkeepTaskForm $form;

    public int $year;

    public int $month;

    public bool $editorOpen = false;

    /**
     * Task id pending completion (drives the "mark as done" sheet).
     */
    public ?int $completing = null;

    /**
     * 'today' | 'yesterday' | an ISO date from the picker.
     */
    public string $completedOn = 'today';

    public function mount(): void
    {
        $this->year = today()->year;
        $this->month = today()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month)->subMonth();
        [$this->year, $this->month] = [$date->year, $date->month];
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month)->addMonth();
        [$this->year, $this->month] = [$date->year, $date->month];
    }

    public function openCreate(): void
    {
        $this->authorize('create', UpkeepTask::class);

        $this->form->reset();
        $this->form->resetValidation();
        $this->editorOpen = true;
    }

    public function openEdit(int $taskId): void
    {
        $task = UpkeepTask::findOrFail($taskId);

        $this->authorize('update', $task);

        $this->form->setTask($task);
        $this->editorOpen = true;
    }

    public function closeEditor(): void
    {
        $this->editorOpen = false;
        $this->form->reset();
        $this->form->resetValidation();
    }

    public function save(): void
    {
        $editing = $this->form->upkeepTask !== null;

        $this->form->save();

        $this->closeEditor();
        unset($this->agenda, $this->calendar);

        $this->dispatch('toast', message: $editing ? 'Task saved' : 'Task added');
    }

    public function deleteTask(): void
    {
        $task = $this->form->upkeepTask;

        abort_if($task === null, 404);

        $this->authorize('delete', $task);

        $task->delete();

        $this->closeEditor();
        unset($this->agenda, $this->calendar);

        $this->dispatch('toast', message: 'Task deleted');
    }

    public function startCompleting(int $taskId): void
    {
        $this->completing = $taskId;
        $this->completedOn = 'today';
    }

    public function cancelCompleting(): void
    {
        $this->completing = null;
    }

    public function complete(): void
    {
        $task = UpkeepTask::findOrFail($this->completing);

        $this->authorize('update', $task);

        app(CompleteUpkeepTask::class)->handle($task, auth()->user(), $this->completionDate());

        $this->cancelCompleting();
        unset($this->agenda, $this->calendar, $this->doneLog);

        $this->dispatch('toast', message: 'Logged as done ✓');
    }

    #[Computed]
    public function completingTask(): ?UpkeepTask
    {
        return $this->completing === null ? null : UpkeepTask::find($this->completing);
    }

    public function completionDate(): Carbon
    {
        return match ($this->completedOn) {
            'today' => today(),
            'yesterday' => today()->subDay(),
            default => Carbon::parse($this->completedOn)->startOfDay(),
        };
    }

    /**
     * Open tasks sorted by due date.
     *
     * @return Collection<int, UpkeepTask>
     */
    #[Computed]
    public function agenda(): Collection
    {
        return UpkeepTask::query()
            ->with('item.category')
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * The month grid: leading blank count + a severity dot per day.
     *
     * @return array{blanks: int, days: int, dots: array<int, string>, label: string, isCurrentMonth: bool}
     */
    #[Computed]
    public function calendar(): array
    {
        $start = Carbon::create($this->year, $this->month);
        $end = $start->copy()->endOfMonth();

        $dots = [];

        foreach ($this->agenda as $task) {
            if ($task->due_date->betweenIncluded($start, $end)) {
                $severity = $task->status() === UpkeepStatus::Overdue ? 'bad' : 'warn';
                $day = $task->due_date->day;
                $dots[$day] = ($dots[$day] ?? '') === 'bad' ? 'bad' : $severity;
            }
        }

        UpkeepLog::query()
            ->whereBetween('completed_on', [$start, $end])
            ->pluck('completed_on')
            ->each(function (Carbon $date) use (&$dots) {
                $dots[$date->day] ??= 'good';
            });

        return [
            'blanks' => $start->dayOfWeek,
            'days' => $end->day,
            'dots' => $dots,
            'label' => $start->format('F Y'),
            'isCurrentMonth' => $start->isSameMonth(today()),
        ];
    }

    /**
     * @return Collection<int, UpkeepLog>
     */
    #[Computed]
    public function doneLog(): Collection
    {
        return UpkeepLog::query()
            ->with('upkeeper')
            ->latest('completed_on')
            ->latest('id')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, Item>
     */
    #[Computed]
    public function items(): Collection
    {
        return Item::query()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.upkeep.index');
    }
}
