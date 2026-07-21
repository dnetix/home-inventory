<?php

namespace App\Livewire\Forms;

use App\Models\Category;
use App\Support\CurrentHome;
use App\Support\IconLibrary;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CategoryForm extends Form
{
    public ?Category $category = null;

    public string $label = '';

    public string $color = '#4f74e3';

    public string $glyph = 'box';

    public ?int $parentId = null;

    public const array GLYPHS = ['box', 'wrench', 'drill', 'bolt', 'utensil', 'globe', 'doc', 'shirt', 'tag', 'star'];

    public const array COLORS = ['#4f74e3', '#1f9d8f', '#df8f3c', '#4e9b54', '#7c8597', '#a866c8', '#c0564a', '#d99a2b'];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'label' => ['required', 'string', 'max:60'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'glyph' => [
                'required', 'string',
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! in_array($value, self::GLYPHS, true) && ! IconLibrary::has($value)) {
                        $fail('Pick an icon from the list.');
                    }
                },
            ],
            'parentId' => [
                'nullable',
                Rule::exists('categories', 'id')->where('home_id', $homeId)->whereNull('parent_id'),
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($this->category !== null && (int) $value === $this->category->id) {
                        $fail('A category cannot be nested under itself.');
                    }

                    if ($this->category !== null && $value !== null && $this->category->children()->exists()) {
                        $fail('A category with sub-categories must stay top-level.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'label' => 'name',
            'parentId' => 'parent category',
        ];
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
        $this->label = $category->label;
        $this->color = $category->color;
        $this->glyph = $category->glyph;
        $this->parentId = $category->parent_id;
    }

    public function save(): Category
    {
        $this->validate();

        $attributes = [
            'label' => trim($this->label),
            'color' => strtolower($this->color),
            'glyph' => $this->glyph,
            'parent_id' => $this->parentId,
        ];

        if ($this->category !== null) {
            $this->category->update($attributes);

            return $this->category;
        }

        return Category::create($attributes);
    }
}
