<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Theme;
use App\Enums\Unit;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'current_home_id', 'unit', 'theme', 'notifications'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mirrors the database defaults so unsaved/in-memory models are complete.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unit' => 'metric',
        'theme' => 'system',
        'notifications' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'unit' => Unit::class,
            'theme' => Theme::class,
            'notifications' => 'boolean',
        ];
    }

    public function homes(): BelongsToMany
    {
        return $this->belongsToMany(Home::class)->withPivot('role')->withTimestamps();
    }

    public function currentHome(): BelongsTo
    {
        return $this->belongsTo(Home::class, 'current_home_id');
    }
}
