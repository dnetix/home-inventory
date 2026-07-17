<?php

namespace App\Livewire\Forms;

use App\Enums\UpkeepKind;
use App\Models\Item;
use App\Models\UpkeepTask;
use App\Support\CurrentHome;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UpkeepTaskForm extends Form
{
    public ?UpkeepTask $upkeepTask = null;

    public ?int $itemId = null;

    /**
     * Free-text subject when the task isn't linked to an item ("Furnace").
     */
    public string $subject = '';

    public string $kind = 'maint';

    public string $task = '';

    public string $dueDate = '';

    /**
     * ISO-8601 duration or '' for one-time.
     */
    public string $every = '';

    public const array RECURRENCES = [
        '' => 'One-time',
        'P1W' => 'Weekly',
        'P1M' => 'Monthly',
        'P3M' => 'Every 3 months',
        'P6M' => 'Every 6 months',
        'P1Y' => 'Yearly',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'itemId' => ['nullable', Rule::exists('items', 'id')->where('home_id', $homeId)],
            'subject' => ['required_without:itemId', 'nullable', 'string', 'max:80'],
            'kind' => [Rule::enum(UpkeepKind::class)],
            'task' => ['required', 'string', 'max:120'],
            'dueDate' => ['required', 'date'],
            'every' => ['nullable', Rule::in(array_keys(self::RECURRENCES))],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'itemId' => 'item',
            'dueDate' => 'due date',
            'every' => 'recurrence',
        ];
    }

    public function setTask(UpkeepTask $upkeepTask): void
    {
        $this->upkeepTask = $upkeepTask;
        $this->itemId = $upkeepTask->item_id;
        $this->subject = $upkeepTask->item_id === null ? $upkeepTask->subject : '';
        $this->kind = $upkeepTask->kind->value;
        $this->task = $upkeepTask->task;
        $this->dueDate = $upkeepTask->due_date?->toDateString() ?? '';
        $this->every = (string) $upkeepTask->every;
    }

    public function save(): UpkeepTask
    {
        $this->validate();

        $item = $this->itemId === null ? null : Item::findOrFail($this->itemId);

        $attributes = [
            'item_id' => $item?->id,
            'subject' => $item?->name ?? trim($this->subject),
            'kind' => UpkeepKind::from($this->kind),
            'task' => trim($this->task),
            'due_date' => $this->dueDate,
            'every' => $this->every === '' ? null : $this->every,
        ];

        if ($this->upkeepTask !== null) {
            $this->upkeepTask->update($attributes);

            return $this->upkeepTask;
        }

        return UpkeepTask::create($attributes);
    }
}
