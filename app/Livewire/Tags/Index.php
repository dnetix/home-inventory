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
        $this->authorize('create', Tag::class);

        $this->form->save();

        unset($this->tags);

        $this->dispatch('toast', message: 'Tag created');
    }

    public function delete(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->authorize('delete', $tag);

        $tag->delete();

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
