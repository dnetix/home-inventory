<?php

namespace App\Models\Concerns;

use App\Models\Home;
use App\Support\CurrentHome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenancy: scopes every query to the current home and fills home_id on create.
 *
 * Queries throw MissingCurrentHomeException when no home is resolvable —
 * console commands, jobs, and seeders must use CurrentHome::override()
 * or the explicit forHome() scope instead of relying on the global scope.
 */
trait BelongsToHome
{
    protected static function bootBelongsToHome(): void
    {
        static::addGlobalScope('home', function (Builder $builder) {
            $builder->where($builder->qualifyColumn('home_id'), app(CurrentHome::class)->idOrFail());
        });

        static::creating(function (Model $model) {
            $model->home_id ??= app(CurrentHome::class)->idOrFail();
        });
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function scopeForHome(Builder $query, Home $home): Builder
    {
        return $query->withoutGlobalScope('home')->where($query->qualifyColumn('home_id'), $home->id);
    }
}
