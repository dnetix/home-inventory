<?php

namespace App\Livewire\Forms;

use App\Models\Tag;
use App\Support\CurrentHome;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TagForm extends Form
{
    public ?Tag $tag = null;

    public string $label = '';

    public string $color = '#c0564a';

    public string $description = '';

    public const array COLORS = ['#c0564a', '#d99a2b', '#4e9b54', '#4f74e3', '#8a5cc0', '#7c8597'];

    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
        $this->label = $tag->label;
        $this->color = $tag->color;
        $this->description = $tag->description ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'label' => [
                'required', 'string', 'max:40',
                Rule::unique('tags', 'label')->where('home_id', $homeId)->ignore($this->tag?->id),
            ],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['label' => 'tag name', 'description' => 'notes'];
    }

    public function save(): Tag
    {
        $this->validate();

        $attributes = [
            'label' => mb_strtolower(trim($this->label)),
            'color' => strtolower($this->color),
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
        ];

        if ($this->tag !== null) {
            $this->tag->update($attributes);
            $tag = $this->tag;
        } else {
            $tag = Tag::create($attributes);
        }

        $this->reset();

        return $tag;
    }
}
