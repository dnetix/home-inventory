<?php

namespace Tests\Feature;

use App\Livewire\Items\Form;
use App\Livewire\Items\Index;
use App\Livewire\Items\Show;
use App\Models\Home;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ItemPhotoTest extends TestCase
{
    use RefreshDatabase;

    private Home $home;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->home = Home::factory()->create();
        $user = User::factory()->create(['current_home_id' => $this->home->id]);
        $this->home->users()->attach($user, ['role' => 'owner']);
        $this->actingAs($user);
    }

    public function test_a_photo_can_be_attached_when_creating_an_item(): void
    {
        Livewire::test(Form::class)
            ->set('form.name', 'Cordless drill')
            ->set('photo', UploadedFile::fake()->create('drill.jpg', 128, 'image/jpeg'))
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::forHome($this->home)->firstOrFail();

        $this->assertNotNull($item->photo_path);
        Storage::disk('s3')->assertExists($item->photo_path);
    }

    public function test_replacing_a_photo_deletes_the_old_object(): void
    {
        $oldPath = UploadedFile::fake()->create('old.jpg', 128, 'image/jpeg')->store('items/'.$this->home->id, 's3');
        $item = Item::factory()->for($this->home)->create(['photo_path' => $oldPath]);

        Livewire::test(Form::class, ['item' => $item])
            ->set('photo', UploadedFile::fake()->create('new.jpg', 128, 'image/jpeg'))
            ->call('save')
            ->assertHasNoErrors();

        $item->refresh();

        $this->assertNotSame($oldPath, $item->photo_path);
        Storage::disk('s3')->assertMissing($oldPath);
        Storage::disk('s3')->assertExists($item->photo_path);
    }

    public function test_a_photo_can_be_removed(): void
    {
        $path = UploadedFile::fake()->create('photo.jpg', 128, 'image/jpeg')->store('items/'.$this->home->id, 's3');
        $item = Item::factory()->for($this->home)->create(['photo_path' => $path]);

        Livewire::test(Form::class, ['item' => $item])
            ->call('clearPhoto')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($item->fresh()->photo_path);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_deleting_an_item_deletes_its_photo(): void
    {
        $path = UploadedFile::fake()->create('photo.jpg', 128, 'image/jpeg')->store('items/'.$this->home->id, 's3');
        $item = Item::factory()->for($this->home)->create(['photo_path' => $path]);

        Livewire::test(Show::class, ['item' => $item])
            ->call('delete');

        Storage::disk('s3')->assertMissing($path);
    }

    public function test_lists_show_the_photo_thumbnail_and_fall_back_to_the_glyph(): void
    {
        $path = UploadedFile::fake()->create('photo.jpg', 128, 'image/jpeg')->store('items/'.$this->home->id, 's3');
        Item::factory()->for($this->home)->create(['name' => 'Espresso machine', 'photo_path' => $path]);
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);

        Livewire::test(Index::class)
            ->assertSeeHtml('alt="Espresso machine"')
            ->assertDontSeeHtml('alt="Cordless drill"');
    }

    public function test_photos_follow_the_configured_disk(): void
    {
        config(['filesystems.photos' => 'local']);
        Storage::fake('local');

        Livewire::test(Form::class)
            ->set('form.name', 'Cordless drill')
            ->set('photo', UploadedFile::fake()->create('drill.jpg', 128, 'image/jpeg'))
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::forHome($this->home)->firstOrFail();

        Storage::disk('local')->assertExists($item->photo_path);
        Storage::disk('s3')->assertMissing($item->photo_path);

        Livewire::test(Show::class, ['item' => $item])->call('delete');

        Storage::disk('local')->assertMissing($item->photo_path);
    }

    public function test_a_failed_photo_write_aborts_the_save_with_a_generic_error(): void
    {
        config(['filesystems.photos' => 'broken-disk']);

        Livewire::test(Form::class)
            ->set('form.name', 'Cordless drill')
            ->set('photo', UploadedFile::fake()->create('drill.jpg', 128, 'image/jpeg'))
            ->call('save')
            ->assertHasErrors(['photo']);

        $this->assertSame(0, Item::forHome($this->home)->count());
    }

    public function test_non_images_are_rejected(): void
    {
        Livewire::test(Form::class)
            ->set('form.name', 'Thing')
            ->set('photo', UploadedFile::fake()->create('malware.exe', 100))
            ->call('save')
            ->assertHasErrors(['photo']);
    }
}
