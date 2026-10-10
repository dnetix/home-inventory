<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Models\Home;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigratePhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        Storage::fake('local');
    }

    public function test_photos_and_thumbnails_of_every_home_are_copied_and_the_source_is_kept(): void
    {
        $homeA = Home::factory()->create();
        $homeB = Home::factory()->create();

        $a = $this->photoFor($homeA, 'drill', 'drill-bytes', 'drill-thumb');
        $b = $this->photoFor($homeB, 'lamp', 'lamp-bytes', 'lamp-thumb');
        $removed = $this->photoFor($homeA, 'old-tv', 'tv-bytes', 'tv-thumb', ItemStatus::Removed);

        $this->artisan('photos:migrate', ['--from' => 's3', '--to' => 'local'])
            ->expectsOutputToContain('6 object(s) copied, 0 already on "local", 0 missing on "s3", 0 failed.')
            ->assertSuccessful();

        foreach ([$a, $b, $removed] as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('local')->assertExists(Item::thumbPath($path));
            Storage::disk('s3')->assertExists($path);
            Storage::disk('s3')->assertExists(Item::thumbPath($path));
        }

        $this->assertSame('tv-bytes', Storage::disk('local')->get($removed));
        $this->assertSame('tv-thumb', Storage::disk('local')->get(Item::thumbPath($removed)));
    }

    public function test_rerunning_skips_objects_already_on_the_target_and_reports_missing_sources(): void
    {
        $home = Home::factory()->create();
        $done = $this->photoFor($home, 'drill', 'drill-bytes', 'drill-thumb');
        Storage::disk('local')->put($done, 'drill-bytes');
        Storage::disk('local')->put(Item::thumbPath($done), 'drill-thumb');

        $noThumb = $this->photoFor($home, 'lamp', 'lamp-bytes', null);

        $lost = 'items/'.$home->id.'/lost.jpg';
        Item::factory()->for($home)->create(['name' => 'Lost photo', 'photo_path' => $lost]);

        $this->artisan('photos:migrate', ['--from' => 's3', '--to' => 'local'])
            ->expectsOutputToContain('Lost photo: photo '.$lost.' is missing on the source disk.')
            ->expectsOutputToContain('1 object(s) copied, 2 already on "local", 3 missing on "s3", 0 failed.')
            ->assertSuccessful();

        Storage::disk('local')->assertExists($noThumb);
        Storage::disk('local')->assertMissing(Item::thumbPath($noThumb));
        Storage::disk('local')->assertMissing($lost);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $home = Home::factory()->create();
        $path = $this->photoFor($home, 'drill', 'drill-bytes', 'drill-thumb');

        $this->artisan('photos:migrate', ['--from' => 's3', '--to' => 'local', '--dry-run' => true])
            ->expectsOutputToContain('2 object(s) to copy')
            ->assertSuccessful();

        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertMissing(Item::thumbPath($path));
    }

    public function test_the_source_defaults_to_the_configured_photo_disk_and_must_differ_from_the_target(): void
    {
        config(['filesystems.photos' => 'local']);

        $this->artisan('photos:migrate', ['--to' => 'local'])->assertFailed();
    }

    private function photoFor(Home $home, string $name, string $bytes, ?string $thumbBytes, ItemStatus $status = ItemStatus::InPlace): string
    {
        $path = 'items/'.$home->id.'/'.$name.'.jpg';
        Storage::disk('s3')->put($path, $bytes);

        if ($thumbBytes !== null) {
            Storage::disk('s3')->put(Item::thumbPath($path), $thumbBytes);
        }

        Item::factory()->for($home)->create(['name' => ucfirst($name), 'photo_path' => $path, 'status' => $status]);

        return $path;
    }
}
