<?php

namespace App\Livewire\Forms;

use App\Models\Tag;
use App\Support\CurrentHome;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TagForm extends Form
{
    public string $label = '';

    public string $color = '#c0564a';

    public const array COLORS = ['#c0564a', '#d99a2b', '#4e9b54', '#4f74e3', '#8a5cc0', '#7c8597'];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'label' => [
                'required', 'string', 'max:40',
                Rule::unique('tags', 'label')->where('home_id', $homeId),
            ],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['label' => 'tag name'];
    }

    public function save(): Tag
    {
        $this->validate();

        $tag = Tag::create([
            'label' => mb_strtolower(trim($this->label)),
            'color' => strtolower($this->color),
        ]);

        $this->reset();

        return $tag;
    }
}
