<?php

namespace App\Livewire\Tags;

use App\Livewire\Forms\TagForm;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tags')]
class Index extends Component
{
    use AuthorizesRequests;

    public TagForm $form;

    public function save(): void
    {
        $editing = $this->form->tag !== null;

        $editing
            ? $this->authorize('update', $this->form->tag)
            : $this->authorize('create', Tag::class);

        $this->form->save();

        unset($this->tags);

        $this->dispatch('toast', message: $editing ? 'Tag updated' : 'Tag created');
    }

    public function startEdit(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->authorize('update', $tag);

        $this->form->setTag($tag);
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function delete(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->authorize('delete', $tag);

        $tag->delete();

        if ($this->form->tag?->id === $tagId) {
            $this->form->reset();
        }

        unset($this->tags);

        $this->dispatch('toast', message: 'Tag deleted');
    }

    /**
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return Tag::query()->withCount('items')->orderBy('label')->get();
    }

    public function render(): View
    {
        return view('livewire.tags.index');
    }
}
