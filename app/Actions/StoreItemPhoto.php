<?php

namespace App\Actions;

use App\Models\Item;
use App\Support\PhotoShrinker;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreItemPhoto
{
    /**
     * Shrink and store an uploaded item photo, returning its object path.
     * Fails loudly: the real cause is reported to the log while the user
     * gets a generic validation error on $errorKey — nothing is saved.
     */
    public function store(UploadedFile $photo, int $homeId, string $errorKey = 'photo'): string
    {
        $original = $photo->get();
        $shrunk = (new PhotoShrinker)->shrink($original);
        $name = $shrunk === $original
            ? $photo->hashName()
            : pathinfo($photo->hashName(), PATHINFO_FILENAME).'.jpg';

        $path = 'items/'.$homeId.'/'.$name;

        try {
            $written = Item::photoDisk()->put($path, $shrunk) !== false;
        } catch (Throwable $exception) {
            report($exception);
            $written = false;
        }

        if (! $written) {
            throw ValidationException::withMessages([
                $errorKey => 'The photo could not be saved. Please try again.',
            ]);
        }

        // Best-effort: lists fall back to the original while a thumbnail is
        // missing, so a failed write only costs bandwidth, not the save.
        try {
            Item::photoDisk()->put(Item::thumbPath($path), PhotoShrinker::thumbnail()->shrink($shrunk));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $path;
    }
}
