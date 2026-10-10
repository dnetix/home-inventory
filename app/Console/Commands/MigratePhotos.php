<?php

namespace App\Console\Commands;

use App\Models\Home;
use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigratePhotos extends Command
{
    protected $signature = 'photos:migrate
        {--from= : Disk to copy from (defaults to the configured photo disk)}
        {--to=local : Disk to copy to}
        {--dry-run : Report what would be copied without writing anything}';

    protected $description = 'Copy every item photo (and thumbnail) from one disk to another without touching the source, so PHOTO_DISK can be switched afterwards';

    private int $copied = 0;

    private int $skipped = 0;

    private int $failed = 0;

    private int $missing = 0;

    public function handle(): int
    {
        $fromName = $this->option('from') ?? config('filesystems.photos');
        $toName = $this->option('to');

        if ($fromName === $toName) {
            $this->error("Source and target are the same disk ({$fromName}).");

            return self::INVALID;
        }

        $from = Storage::disk($fromName);
        $to = Storage::disk($toName);
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf('%s photos from "%s" to "%s"...', $dryRun ? 'Checking' : 'Copying', $fromName, $toName));

        foreach (Home::query()->cursor() as $home) {
            $items = Item::withRemoved()->forHome($home)->whereNotNull('photo_path')->cursor();

            foreach ($items as $item) {
                $this->copy($from, $to, $item->photo_path, $dryRun, "{$item->name}: photo");
                $this->copy($from, $to, Item::thumbPath($item->photo_path), $dryRun, "{$item->name}: thumbnail", thumbnail: true);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%d object(s) %s, %d already on "%s", %d missing on "%s", %d failed.',
            $this->copied,
            $dryRun ? 'to copy' : 'copied',
            $this->skipped,
            $toName,
            $this->missing,
            $fromName,
            $this->failed,
        ));

        if ($this->failed > 0) {
            $this->error('Some objects could not be copied. Fix the cause and re-run: finished objects are skipped.');

            return self::FAILURE;
        }

        if (! $dryRun) {
            $this->line("Source objects were left untouched. Set PHOTO_DISK={$toName}, reload the app and verify photos before removing \"{$fromName}\".");
        }

        return self::SUCCESS;
    }

    /**
     * Copy one object, streaming so large photos never sit in memory. A
     * target object of the same size counts as already migrated, which
     * makes re-runs after a failure cheap and safe.
     */
    private function copy(Filesystem $from, Filesystem $to, string $path, bool $dryRun, string $label, bool $thumbnail = false): void
    {
        if (! $from->exists($path)) {
            $this->missing++;

            // Missing thumbnails are routine (photos:shrink backfills them);
            // a missing original is worth a loud warning.
            $thumbnail
                ? $this->line("<comment>{$label} {$path} is missing (photos:shrink regenerates it).</comment>")
                : $this->warn("{$label} {$path} is missing on the source disk.");

            return;
        }

        $size = $from->size($path);

        if ($to->exists($path) && $to->size($path) === $size) {
            $this->skipped++;

            return;
        }

        if ($dryRun) {
            $this->copied++;
            $this->line("{$label} {$path} ({$this->kilobytes($size)})");

            return;
        }

        try {
            $stream = $from->readStream($path);
            $written = $stream !== null && $to->writeStream($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $written = $written && $to->size($path) === $size;
        } catch (Throwable $exception) {
            report($exception);
            $written = false;
        }

        if ($written) {
            $this->copied++;
            $this->line("{$label} {$path} ({$this->kilobytes($size)})");
        } else {
            $this->failed++;
            $this->error("{$label} {$path} could not be copied.");
        }
    }

    private function kilobytes(int $bytes): string
    {
        return sprintf('%dKB', max(1, intdiv($bytes, 1024)));
    }
}
