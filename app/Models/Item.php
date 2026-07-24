<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Models\Concerns\BelongsToHome;
use App\Support\Dimensions;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'category_id', 'place_id', 'value', 'qty', 'dim', 'note', 'warranty_until', 'photo_path', 'status'])]
class Item extends Model
{
    use BelongsToHome;

    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Removed items behave like soft-deleted ones: excluded from every
        // list, search, stat and picker unless a query opts in via withRemoved().
        static::addGlobalScope('notRemoved', function (Builder $query): void {
            $query->where('status', '!=', ItemStatus::Removed);
        });

        static::deleting(function (Item $item): void {
            if ($item->photo_path !== null) {
                self::deletePhotoObjects($item->photo_path);
            }
        });
    }

    /**
     * The disk item photos live on — s3/MinIO by default, switchable to any
     * temporaryUrl-capable disk via PHOTO_DISK for object-store-less installs.
     */
    public static function photoDisk(): FilesystemAdapter
    {
        return Storage::disk(config('filesystems.photos'));
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'qty' => 1,
        'status' => 'in_place',
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
            'status' => ItemStatus::class,
            'warranty_until' => 'date',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithRemoved(Builder $query): Builder
    {
        return $query->withoutGlobalScope('notRemoved');
    }

    /**
     * Detail and edit pages must keep working for removed items — that is the
     * path to restoring them.
     *
     * @param  Builder<self>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Builder<self>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query->withoutGlobalScope('notRemoved'), $value, $field);
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
     * Object path of the list thumbnail that lives next to a photo
     * (items/1/abc.jpg → items/1/abc_thumb.jpg).
     */
    public static function thumbPath(string $photoPath): string
    {
        return pathinfo($photoPath, PATHINFO_DIRNAME).'/'.pathinfo($photoPath, PATHINFO_FILENAME).'_thumb.jpg';
    }

    /**
     * Delete a photo object together with its thumbnail.
     */
    public static function deletePhotoObjects(string $photoPath): void
    {
        self::photoDisk()->delete([$photoPath, self::thumbPath($photoPath)]);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path === null ? null : $this->signedPhotoUrl($this->photo_path);
    }

    /**
     * Small variant for lists and grids; the object may be missing for photos
     * stored before thumbnails existed, so <x-item-thumb> falls back to the
     * original client-side (photos:shrink backfills them).
     */
    public function photoThumbUrl(): ?string
    {
        return $this->photo_path === null ? null : $this->signedPhotoUrl(self::thumbPath($this->photo_path));
    }

    /**
     * A short-lived presigned URL — the photo bucket stays private. Cached
     * shorter than its validity so re-renders keep the same URL: a fresh
     * signature per render would make the browser re-download every photo
     * on every Livewire update.
     */
    private function signedPhotoUrl(string $path): string
    {
        return Cache::remember(
            'photo-url:'.config('filesystems.photos').':'.$path,
            now()->addMinutes(20),
            fn (): string => self::photoDisk()->temporaryUrl($path, now()->addMinutes(30)),
        );
    }
}
