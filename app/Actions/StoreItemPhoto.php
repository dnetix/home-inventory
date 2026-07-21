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

        return $path;
    }
}
