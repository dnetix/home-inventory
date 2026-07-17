<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['upkeep_task_id', 'user_id', 'task', 'completed_on'])]
class UpkeepLog extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\UpkeepLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
        ];
    }

    public function upkeepTask(): BelongsTo
    {
        return $this->belongsTo(UpkeepTask::class);
    }

    /**
     * The member who logged the completion.
     */
    public function upkeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
