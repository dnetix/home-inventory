<?php

namespace App\Livewire\Categories;

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
    use AuthorizesRequests;

    public CategoryForm $form;

    /** @var list<int> */
    public array $open = [];

    public bool $editorOpen = false;

    public function toggle(int $categoryId): void
    {
        $this->open = in_array($categoryId, $this->open, true)
            ? array_values(array_diff($this->open, [$categoryId]))
            : [...$this->open, $categoryId];
    }

    public function openCreate(): void
    {
        $this->authorize('create', Category::class);

        $this->form->reset();
        $this->form->resetValidation();
        $this->editorOpen = true;
    }

    public function openEdit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->authorize('update', $category);

        $this->form->setCategory($category);
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
        $editing = $this->form->category !== null;

        $this->form->save();

        $this->closeEditor();
        unset($this->categories, $this->counts);

        $this->dispatch('toast', message: $editing ? 'Category saved' : 'Category added');
    }

    public function delete(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->authorize('delete', $category);

        $category->delete();

        $this->closeEditor();
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
