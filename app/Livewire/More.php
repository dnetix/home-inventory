<?php

namespace App\Livewire;

use App\Enums\UpkeepStatus;
use App\Models\Category;
use App\Models\Lend;
use App\Models\Tag;
use App\Models\UpkeepTask;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('More')]
class More extends Component
{
    #[Computed]
    public function counts(): array
    {
        $attention = UpkeepTask::query()
            ->whereNotNull('due_date')
            ->get()
            ->filter(fn (UpkeepTask $task) => in_array($task->status(), [UpkeepStatus::Overdue, UpkeepStatus::Soon], true))
            ->count();

        $attention += Lend::query()->active()->get()->filter->isOverdue()->count();

        return [
            'categories' => Category::query()->whereNull('parent_id')->count(),
            'tags' => Tag::query()->count(),
            'attention' => $attention,
        ];
    }

    public function render(): View
    {
        return view('livewire.more');
    }
}
