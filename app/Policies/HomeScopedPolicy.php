<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared policy for every home-scoped model: membership in the model's home
 * is the second enforcement layer behind the BelongsToHome global scope.
 */
class HomeScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_home_id !== null;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->belongsToUsersHome($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->current_home_id !== null;
    }

    public function update(User $user, Model $model): bool
    {
        return $this->belongsToUsersHome($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->belongsToUsersHome($user, $model);
    }

    protected function belongsToUsersHome(User $user, Model $model): bool
    {
        return $user->homes()->whereKey($model->getAttribute('home_id'))->exists();
    }
}
