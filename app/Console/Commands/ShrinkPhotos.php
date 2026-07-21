<?php

namespace App\Console\Commands;

use App\Models\Home;
use App\Models\Item;
use App\Support\PhotoShrinker;
use Illuminate\Console\Command;

class ShrinkPhotos extends Command
{
    protected $signature = 'photos:shrink {--dry-run : Report what would change without touching storage}';

    protected $description = 'Re-encode stored item photos whose longest edge exceeds the size cap';

    public function handle(PhotoShrinker $shrinker): int
    {
        $disk = Item::photoDisk();
        $shrunk = 0;
        $untouched = 0;

        foreach (Home::query()->get() as $home) {
            foreach (Item::forHome($home)->whereNotNull('photo_path')->get() as $item) {
                $bytes = $disk->get($item->photo_path);

                if ($bytes === null) {
                    $this->warn("{$item->name}: object {$item->photo_path} is missing, skipped.");

                    continue;
                }

                $smaller = $shrinker->shrink($bytes);

                if ($smaller === $bytes) {
                    $untouched++;

                    continue;
                }

                $this->line(sprintf('%s: %dKB → %dKB', $item->name, strlen($bytes) / 1024, strlen($smaller) / 1024));
                $shrunk++;

                if ($this->option('dry-run')) {
                    continue;
                }

                $newPath = pathinfo($item->photo_path, PATHINFO_DIRNAME).'/'.pathinfo($item->photo_path, PATHINFO_FILENAME).'.jpg';
                $disk->put($newPath, $smaller);

                if ($newPath !== $item->photo_path) {
                    $oldPath = $item->photo_path;
                    $item->update(['photo_path' => $newPath]);
                    $disk->delete($oldPath);
                }
            }
        }

        $this->info(sprintf(
            '%d photo(s) %s, %d already within the cap.',
            $shrunk,
            $this->option('dry-run') ? 'would be re-encoded' : 're-encoded',
            $untouched,
        ));

        return self::SUCCESS;
    }
}
