<?php

namespace App\Models;

use App\Enums\UpkeepKind;
use App\Enums\UpkeepStatus;
use App\Models\Concerns\BelongsToHome;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['item_id', 'subject', 'kind', 'task', 'due_date', 'every'])]
class UpkeepTask extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\UpkeepTaskFactory> */
    use HasFactory;

    /**
     * Days ahead of the due date a task counts as "due soon".
     */
    public const int SOON_DAYS = 7;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => UpkeepKind::class,
            'due_date' => 'date',
        ];
    }

    /**
     * Includes removed items — existing tasks must keep naming them.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withoutGlobalScope('notRemoved');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UpkeepLog::class)->latest('completed_on');
    }

    public function status(): UpkeepStatus
    {
        return match (true) {
            $this->due_date === null => UpkeepStatus::Done,
            $this->due_date->isPast() && ! $this->due_date->isToday() => UpkeepStatus::Overdue,
            $this->due_date->lte(today()->addDays(self::SOON_DAYS)) => UpkeepStatus::Soon,
            default => UpkeepStatus::Upcoming,
        };
    }

    public function isRecurring(): bool
    {
        return $this->every !== null;
    }

    public function recurrence(): ?CarbonInterval
    {
        return $this->every === null ? null : new CarbonInterval($this->every);
    }
}
