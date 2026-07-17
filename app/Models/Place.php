<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use App\Support\Dimensions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'glyph', 'description', 'parent_id', 'dim'])]
class Place extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\PlaceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dim' => Dimensions::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Interior capacity in litres, when a size is set.
     */
    public function capacityLitres(): ?float
    {
        return $this->dim?->volumeLitres();
    }
}
