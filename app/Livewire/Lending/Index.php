<?php

namespace App\Livewire\Lending;

use App\Livewire\Forms\LendForm;
use App\Models\Item;
use App\Models\Lend;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Lending')]
class Index extends Component
{
    use AuthorizesRequests;

    public LendForm $form;

    #[Url]
    public string $filter = 'all';

    public bool $lendOpen = false;

    public bool $itemPickerOpen = false;

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'overdue', 'returned'], true) ? $filter : 'all';
    }

    public function openLend(): void
    {
        $this->authorize('create', Lend::class);

        $this->form->reset();
        $this->form->resetValidation();
        $this->lendOpen = true;
    }

    public function closeLend(): void
    {
        $this->lendOpen = false;
        $this->itemPickerOpen = false;
    }

    public function pickItem(int $itemId): void
    {
        $this->form->itemId = $itemId;
        $this->itemPickerOpen = false;
    }

    public function save(): void
    {
        $itemName = Item::find($this->form->itemId)?->name;
        $person = trim($this->form->person);

        $this->form->save();

        $this->closeLend();
        unset($this->lends, $this->summary);

        $this->dispatch('toast', message: "{$itemName} lent to {$person}");
    }

    public function returnLend(int $lendId): void
    {
        $lend = Lend::findOrFail($lendId);

        $this->authorize('update', $lend);

        $lend->update(['returned_at' => today()]);

        unset($this->lends, $this->summary);

        $this->dispatch('toast', message: 'Marked returned');
    }

    /**
     * @return Collection<int, Lend>
     */
    #[Computed]
    public function lends(): Collection
    {
        $query = Lend::query()->with('item.category');

        match ($this->filter) {
            'overdue' => $query->active()->whereNotNull('due_date')->where('due_date', '<', today()),
            'returned' => $query->whereNotNull('returned_at')->latest('returned_at'),
            default => $query->active(),
        };

        if ($this->filter !== 'returned') {
            $query->orderByRaw('due_date is null')->orderBy('due_date');
        }

        return $query->get();
    }

    /**
     * @return array{active: int, overdue: int, valueOnLoan: Money, borrowers: Collection<int, array{person: string, count: int}>}
     */
    #[Computed]
    public function summary(): array
    {
        $active = Lend::query()->active()->with('item')->get();

        return [
            'active' => $active->count(),
            'overdue' => $active->filter->isOverdue()->count(),
            'valueOnLoan' => new Money((int) $active->sum(fn (Lend $lend) => $lend->item->value?->cents ?? 0)),
            'borrowers' => $active
                ->groupBy('person')
                ->map(fn (Collection $lends, string $person) => ['person' => $person, 'count' => $lends->count()])
                ->values(),
        ];
    }

    /**
     * Items available to lend (not already out).
     *
     * @return Collection<int, Item>
     */
    #[Computed]
    public function lendableItems(): Collection
    {
        return Item::query()
            ->with(['category', 'place'])
            ->whereDoesntHave('lends', fn ($query) => $query->whereNull('returned_at'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Previous borrowers as quick suggestions.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function suggestions(): Collection
    {
        return Lend::query()
            ->latest()
            ->pluck('person')
            ->unique()
            ->take(4)
            ->values();
    }

    public function render(): View
    {
        return view('livewire.lending.index');
    }
}
