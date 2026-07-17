<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['item_id', 'person', 'out_date', 'due_date', 'remind', 'returned_at'])]
class Lend extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\LendFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'out_date' => 'date',
            'due_date' => 'date',
            'returned_at' => 'date',
            'remind' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    public function isOverdue(): bool
    {
        return $this->isActive() && $this->due_date !== null && $this->due_date->isPast();
    }
}
