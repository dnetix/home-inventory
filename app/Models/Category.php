<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * All categories in picker order: top-level groups alphabetical, each
     * followed by its children.
     *
     * @return Collection<int, self>
     */
    public static function pickerOrdered(): Collection
    {
        $all = static::query()->orderBy('label')->get();

        return $all
            ->whereNull('parent_id')
            ->flatMap(fn (self $top) => [$top, ...$all->where('parent_id', $top->id)])
            ->values();
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
