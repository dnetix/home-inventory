<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Home extends Model
{
    /** @use HasFactory<\Database\Factories\HomeFactory> */
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function lends(): HasMany
    {
        return $this->hasMany(Lend::class);
    }

    public function upkeepTasks(): HasMany
    {
        return $this->hasMany(UpkeepTask::class);
    }

    public function upkeepLogs(): HasMany
    {
        return $this->hasMany(UpkeepLog::class);
    }
}
