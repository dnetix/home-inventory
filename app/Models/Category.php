<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'glyph', 'color', 'parent_id'])]
class Category extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

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
     * The top-level ancestor this category rolls up to (itself when top-level).
     */
    public function topLevel(): self
    {
        $category = $this;

        while ($category->parent !== null) {
            $category = $category->parent;
        }

        return $category;
    }
}
