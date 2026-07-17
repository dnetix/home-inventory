<?php

namespace App\Livewire\Forms;

use App\Models\Item;
use App\Support\CurrentHome;
use App\Support\Dimensions;
use App\Support\Money;
use App\Support\UnitFormatter;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ItemForm extends Form
{
    public ?Item $item = null;

    public string $name = '';

    public string $note = '';

    public ?int $categoryId = null;

    public ?int $placeId = null;

    public string $qty = '1';

    /**
     * Dollars as typed; converted to integer cents on save.
     */
    public string $value = '';

    /**
     * Dimensions in the user's display unit (cm or in); stored as mm.
     */
    public string $w = '';

    public string $h = '';

    public string $d = '';

    /** @var list<int> */
    public array $tagIds = [];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $homeId = app(CurrentHome::class)->id();

        return [
            'name' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
            'categoryId' => ['nullable', Rule::exists('categories', 'id')->where('home_id', $homeId)],
            'placeId' => ['nullable', Rule::exists('places', 'id')->where('home_id', $homeId)],
            'qty' => ['required', 'integer', 'min:1', 'max:99999'],
            'value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'w' => ['nullable', 'numeric', 'gt:0', 'required_with:h,d'],
            'h' => ['nullable', 'numeric', 'gt:0', 'required_with:w,d'],
            'd' => ['nullable', 'numeric', 'gt:0', 'required_with:w,h'],
            'tagIds' => ['array'],
            'tagIds.*' => [Rule::exists('tags', 'id')->where('home_id', $homeId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'categoryId' => 'category',
            'placeId' => 'location',
            'w' => 'width',
            'h' => 'height',
            'd' => 'depth',
        ];
    }

    public function setItem(Item $item): void
    {
        $units = app(UnitFormatter::class);

        $this->item = $item;
        $this->name = $item->name;
        $this->note = (string) $item->note;
        $this->categoryId = $item->category_id;
        $this->placeId = $item->place_id;
        $this->qty = (string) $item->qty;
        $this->value = $item->value === null ? '' : $this->dollarsInput($item->value);

        if ($item->dim !== null) {
            [$this->w, $this->h, $this->d] = array_map(
                fn (int $mm) => (string) $units->mmToDisplay($mm),
                $item->dim->toArray(),
            );
        }

        $this->tagIds = $item->tags->pluck('id')->all();
    }

    public function save(): Item
    {
        $this->validate();

        $attributes = [
            'name' => trim($this->name),
            'note' => trim($this->note) ?: null,
            'category_id' => $this->categoryId,
            'place_id' => $this->placeId,
            'qty' => (int) $this->qty,
            'value' => $this->value === '' ? null : Money::fromDollars((float) $this->value),
            'dim' => $this->dimensions(),
        ];

        if ($this->item !== null) {
            $this->item->update($attributes);
            $item = $this->item;
        } else {
            $item = Item::create($attributes);
        }

        $item->tags()->sync($this->tagIds);

        return $item;
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

    private function dollarsInput(Money $money): string
    {
        return $money->cents % 100 === 0
            ? (string) intdiv($money->cents, 100)
            : number_format($money->dollars(), 2, '.', '');
    }
}
