<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use App\Support\Dimensions;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'category_id', 'place_id', 'value', 'qty', 'dim', 'note', 'photo_path'])]
class Item extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Item $item): void {
            if ($item->photo_path !== null) {
                Storage::disk('s3')->delete($item->photo_path);
            }
        });
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'qty' => 1,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dim' => Dimensions::class,
            'value' => Money::class,
            'qty' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function lends(): HasMany
    {
        return $this->hasMany(Lend::class);
    }

    public function activeLend(): HasOne
    {
        return $this->hasOne(Lend::class)->whereNull('returned_at')->latestOfMany('out_date');
    }

    public function upkeepTasks(): HasMany
    {
        return $this->hasMany(UpkeepTask::class);
    }

    /**
     * Volume of the whole stack (unit volume × qty) in litres, when a size is set.
     */
    public function totalVolumeLitres(): ?float
    {
        return $this->dim === null ? null : $this->dim->volumeLitres() * $this->qty;
    }

    /**
     * A short-lived presigned URL — the photo bucket stays private.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path === null
            ? null
            : Storage::disk('s3')->temporaryUrl($this->photo_path, now()->addMinutes(30));
    }
}
