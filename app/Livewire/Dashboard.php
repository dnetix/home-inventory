<?php

namespace App\Livewire;

use App\Enums\UpkeepKind;
use App\Enums\UpkeepStatus;
use App\Models\Item;
use App\Models\Lend;
use App\Models\Place;
use App\Models\UpkeepTask;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home')]
class Dashboard extends Component
{
    /**
     * @return array{items: int, units: int, value: Money, places: int, rooms: int, attention: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'items' => Item::query()->count(),
            'units' => (int) Item::query()->sum('qty'),
            'value' => new Money((int) Item::query()->sum('value')),
            'places' => Place::query()->count(),
            'rooms' => Place::query()->whereNull('parent_id')->count(),
            'attention' => $this->attentionCount,
        ];
    }

    #[Computed]
    public function attentionCount(): int
    {
        $upkeep = $this->openTasks
            ->filter(fn (UpkeepTask $task) => in_array($task->status(), [UpkeepStatus::Overdue, UpkeepStatus::Soon], true))
            ->count();

        return $upkeep + $this->activeLends->filter->isOverdue()->count();
    }

    /**
     * Item counts rolled up to top-level categories, largest first.
     *
     * @return Collection<int, array{label: string, color: ?string, count: int}>
     */
    #[Computed]
    public function categoryBars(): Collection
    {
        return Item::query()
            ->with('category.parent')
            ->get()
            ->groupBy(fn (Item $item) => $item->category?->topLevel()->label ?? 'Uncategorized')
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'color' => $items->first()->category?->topLevel()->color,
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Open upkeep + due lends merged into one date-sorted timeline.
     *
     * @return Collection<int, array{icon: string, tone: string, title: string, meta: string, late: bool, route: string}>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        $upkeep = $this->openTasks->map(function (UpkeepTask $task) {
            $status = $task->status();
            $late = $status === UpkeepStatus::Overdue;

            return [
                'icon' => $task->kind === UpkeepKind::Expiry ? 'shield' : 'wrench',
                'tone' => $late ? 'bad' : ($status === UpkeepStatus::Soon ? 'warn' : 'neutral'),
                'title' => $task->subject,
                'meta' => $late ? 'Overdue' : $task->due_date->format('Y-m-d'),
                'late' => $late,
                'date' => $task->due_date,
                'route' => route('upkeep.index'),
            ];
        });

        $lends = $this->activeLends
            ->whereNotNull('due_date')
            ->map(fn (Lend $lend) => [
                'icon' => 'hand',
                'tone' => $lend->isOverdue() ? 'bad' : 'neutral',
                'title' => $lend->item->name,
                'meta' => $lend->isOverdue() ? 'Overdue' : 'back '.$lend->due_date->format('Y-m-d'),
                'late' => $lend->isOverdue(),
                'date' => $lend->due_date,
                'route' => route('lending.index'),
            ]);

        return $upkeep->concat($lends)->sortBy('date')->values()->take(8);
    }

    /**
     * @return Collection<int, Item>
     */
    #[Computed]
    public function recent(): Collection
    {
        return Item::query()->with(['category', 'place'])->latest()->latest('id')->limit(6)->get();
    }

    /**
     * @return Collection<int, Lend>
     */
    #[Computed]
    public function activeLends(): Collection
    {
        return Lend::query()->active()->with('item.category')->get();
    }

    /**
     * @return Collection<int, UpkeepTask>
     */
    #[Computed]
    public function openTasks(): Collection
    {
        return UpkeepTask::query()->whereNotNull('due_date')->orderBy('due_date')->get();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
