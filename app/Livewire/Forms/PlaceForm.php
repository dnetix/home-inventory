<?php

namespace App\Livewire\Forms;

use App\Models\Item;
use App\Models\Place;
use App\Support\CurrentHome;
use App\Support\Dimensions;
use App\Support\PlaceTree;
use App\Support\UnitFormatter;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PlaceForm extends Form
{
    public ?Place $place = null;

    public string $label = '';

    public string $description = '';

    public ?int $parentId = null;

    public string $glyph = 'box';

    /**
     * Interior size in the user's display unit (cm or in); stored as mm.
     */
    public string $w = '';

    public string $h = '';

    public string $d = '';

    public const array GLYPHS = ['home', 'box', 'wrench'];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'label' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'glyph' => [Rule::in(self::GLYPHS)],
            'parentId' => [
                'nullable',
                Rule::exists('places', 'id')->where('home_id', $homeId),
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($this->place !== null && in_array((int) $value, $this->descendantIdsOfPlace(), true)) {
                        $fail('A location cannot be moved inside itself.');
                    }
                },
            ],
            'w' => ['nullable', 'numeric', 'gt:0', 'required_with:h,d'],
            'h' => ['nullable', 'numeric', 'gt:0', 'required_with:w,d'],
            'd' => ['nullable', 'numeric', 'gt:0', 'required_with:w,h'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'label' => 'name',
            'parentId' => 'parent location',
            'w' => 'width',
            'h' => 'height',
            'd' => 'depth',
        ];
    }

    public function setPlace(Place $place): void
    {
        $units = app(UnitFormatter::class);

        $this->place = $place;
        $this->label = $place->label;
        $this->description = (string) $place->description;
        $this->parentId = $place->parent_id;
        $this->glyph = $place->glyph;

        if ($place->dim !== null) {
            [$this->w, $this->h, $this->d] = array_map(
                fn (int $mm) => (string) $units->mmToDisplay($mm),
                $place->dim->toArray(),
            );
        }
    }

    public function save(): Place
    {
        $this->validate();

        $attributes = [
            'label' => trim($this->label),
            'description' => trim($this->description) ?: null,
            'parent_id' => $this->parentId,
            'glyph' => $this->glyph,
            'dim' => $this->dimensions(),
        ];

        if ($this->place !== null) {
            $this->place->update($attributes);

            return $this->place;
        }

        return Place::create($attributes);
    }

    private function dimensions(): ?Dimensions
    {
        if ($this->w === '' || $this->h === '' || $this->d === '') {
            return null;
        }

        $units = app(UnitFormatter::class);

        return new Dimensions(
            $units->displayToMm((float) $this->w),
            $units->displayToMm((float) $this->h),
            $units->displayToMm((float) $this->d),
        );
    }

    /**
     * The edited place and everything under it — invalid reparent targets.
     *
     * @return list<int>
     */
    private function descendantIdsOfPlace(): array
    {
        $tree = new PlaceTree(Place::query()->get(), new Collection([]));

        return $tree->descendantIds($this->place->id);
    }
}
