<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['label', 'color'])]
class Tag extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class);
    }
}
