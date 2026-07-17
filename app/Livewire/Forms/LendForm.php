<?php

namespace App\Livewire\Forms;

use App\Models\Lend;
use App\Support\CurrentHome;
use Illuminate\Validation\Rule;
use Livewire\Form;

class LendForm extends Form
{
    public ?int $itemId = null;

    public string $person = '';

    /**
     * ISO date; empty = no due date.
     */
    public string $dueDate = '';

    public bool $remind = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'itemId' => [
                'required',
                Rule::exists('items', 'id')->where('home_id', $homeId),
                Rule::unique('lends', 'item_id')->where('home_id', $homeId)->whereNull('returned_at'),
            ],
            'person' => ['required', 'string', 'max:60'],
            'dueDate' => ['nullable', 'date', 'after_or_equal:today'],
            'remind' => ['boolean'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function messages(): array
    {
        return [
            'itemId.required' => 'Pick an item to lend.',
            'itemId.unique' => 'This item is already lent out.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'itemId' => 'item',
            'person' => 'borrower',
            'dueDate' => 'due date',
        ];
    }

    public function save(): Lend
    {
        $this->validate();

        $lend = Lend::create([
            'item_id' => $this->itemId,
            'person' => trim($this->person),
            'out_date' => today(),
            'due_date' => $this->dueDate === '' ? null : $this->dueDate,
            'remind' => $this->dueDate !== '' && $this->remind,
        ]);

        $this->reset();

        return $lend;
    }
}
