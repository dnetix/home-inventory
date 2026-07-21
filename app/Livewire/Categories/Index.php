<?php

namespace App\Livewire\Categories;

use App\Livewire\Concerns\SearchesGlyphs;
use App\Livewire\Forms\CategoryForm;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Categories')]
class Index extends Component
{
    use AuthorizesRequests, SearchesGlyphs;

    public CategoryForm $form;

    /** @var list<int> */
    public array $open = [];

    public function toggle(int $categoryId): void
    {
        $this->open = in_array($categoryId, $this->open, true)
            ? array_values(array_diff($this->open, [$categoryId]))
            : [...$this->open, $categoryId];
    }

    public function startEdit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->authorize('update', $category);

        $this->form->setCategory($category);
        $this->reset('glyphSearch');
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->form->reset();
        $this->reset('glyphSearch');
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $editing = $this->form->category !== null;

        $editing
            ? $this->authorize('update', $this->form->category)
            : $this->authorize('create', Category::class);

        $this->form->save();

        $this->form->reset();
        $this->reset('glyphSearch');
        unset($this->categories, $this->counts);

        $this->dispatch('toast', message: $editing ? 'Category saved' : 'Category added');
    }

    /**
     * @return list<string>
     */
    protected function defaultGlyphs(): array
    {
        return CategoryForm::GLYPHS;
    }

    protected function currentGlyph(): string
    {
        return $this->form->glyph;
    }

    public function delete(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->authorize('delete', $category);

        $category->delete();

        if ($this->form->category?->id === $categoryId) {
            $this->form->reset();
            $this->resetErrorBag();
        }

        unset($this->categories, $this->counts);

        $this->dispatch('toast', message: 'Category deleted — items are now uncategorized');
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('label')->get();
    }

    /**
     * Item count per category id, descendants included for top-level ones.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function counts(): array
    {
        $direct = Item::query()
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $counts = [];

        foreach ($this->categories as $category) {
            $counts[$category->id] = (int) $direct->get($category->id, 0);
        }

        foreach ($this->categories->whereNotNull('parent_id') as $child) {
            $counts[$child->parent_id] = ($counts[$child->parent_id] ?? 0) + $counts[$child->id];
        }

        return $counts;
    }

    public function render(): View
    {
        return view('livewire.categories.index');
    }
}
