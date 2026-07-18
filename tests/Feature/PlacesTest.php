<?php

namespace Tests\Feature;

use App\Livewire\Places\Index;
use App\Livewire\Places\Show;
use App\Models\Home;
use App\Models\Item;
use App\Models\Place;
use App\Models\User;
use App\Support\Dimensions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use RefreshDatabase;

    private Home $home;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = Home::factory()->create();
        $user = User::factory()->create(['current_home_id' => $this->home->id]);
        $this->home->users()->attach($user, ['role' => 'owner']);
        $this->actingAs($user);
    }

    public function test_index_renders_the_tree_with_fill(): void
    {
        $garage = Place::factory()->for($this->home)->create(['label' => 'Garage']);
        $shelf = Place::factory()->for($this->home)->childOf($garage)
            ->withDimensions(new Dimensions(900, 400, 1800))->create(['label' => 'Shelf B']);
        Item::factory()->for($this->home)->for($shelf, 'place')
            ->withDimensions(new Dimensions(250, 220, 90))->create();

        $this->get(route('places.index'))
            ->assertOk()
            ->assertSee('Garage')
            ->assertSee('Shelf B')
            ->assertSee('648 L')
            ->assertSee('1% full');
    }

    public function test_a_place_can_be_created_with_dimensions(): void
    {
        Livewire::test(Index::class)
            ->call('openEditor')
            ->set('form.label', 'Attic')
            ->set('form.glyph', 'home')
            ->set('form.w', '300')
            ->set('form.h', '200')
            ->set('form.d', '400')
            ->call('save')
            ->assertHasNoErrors();

        $place = Place::forHome($this->home)->where('label', 'Attic')->firstOrFail();

        $this->assertSame([3000, 2000, 4000], $place->dim->toArray());
        $this->assertNull($place->parent_id);
    }

    public function test_show_renders_capacity_and_items(): void
    {
        $shelf = Place::factory()->for($this->home)
            ->withDimensions(new Dimensions(900, 400, 1800))->create(['label' => 'Shelf B']);
        Item::factory()->for($this->home)->for($shelf, 'place')->create(['name' => 'Cordless drill']);

        $this->get(route('places.show', $shelf))
            ->assertOk()
            ->assertSee('Shelf B')
            ->assertSee('Space')
            ->assertSee('Cordless drill');
    }

    public function test_items_can_be_batch_moved_from_the_place_screen(): void
    {
        $shelf = Place::factory()->for($this->home)->create(['label' => 'Shelf B']);
        $bin = Place::factory()->for($this->home)->create(['label' => 'Bin 1']);
        $item = Item::factory()->for($this->home)->for($shelf, 'place')->create();

        Livewire::test(Show::class, ['place' => $shelf])
            ->call('toggleSelecting')
            ->call('toggleSelected', $item->id)
            ->call('openBatch', 'move')
            ->set('batchPlaceId', $bin->id)
            ->call('confirmBatchMove')
            ->assertSet('selecting', false);

        $this->assertSame($bin->id, $item->fresh()->place_id);
    }

    public function test_a_place_can_be_edited(): void
    {
        $place = Place::factory()->for($this->home)->create(['label' => 'Old label']);

        Livewire::test(Show::class, ['place' => $place])
            ->call('openEdit')
            ->assertSet('form.label', 'Old label')
            ->set('form.label', 'New label')
            ->set('form.description', 'By the door')
            ->call('save')
            ->assertHasNoErrors();

        $place->refresh();
        $this->assertSame('New label', $place->label);
        $this->assertSame('By the door', $place->description);
    }

    public function test_a_sub_location_can_be_added_from_the_detail_screen(): void
    {
        $garage = Place::factory()->for($this->home)->create(['label' => 'Garage']);

        Livewire::test(Show::class, ['place' => $garage])
            ->call('openAddChild')
            ->assertSet('form.parentId', $garage->id)
            ->set('form.label', 'Top shelf')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(
            Place::forHome($this->home)->where('label', 'Top shelf')->where('parent_id', $garage->id)->exists()
        );
    }

    public function test_a_place_cannot_be_moved_inside_itself(): void
    {
        $garage = Place::factory()->for($this->home)->create(['label' => 'Garage']);
        $shelf = Place::factory()->for($this->home)->childOf($garage)->create(['label' => 'Shelf B']);

        Livewire::test(Show::class, ['place' => $garage])
            ->call('openEdit')
            ->set('form.parentId', $shelf->id)
            ->call('save')
            ->assertHasErrors(['form.parentId']);
    }

    public function test_deleting_is_blocked_while_the_place_has_contents(): void
    {
        $place = Place::factory()->for($this->home)->create();
        Item::factory()->for($this->home)->for($place, 'place')->create();

        Livewire::test(Show::class, ['place' => $place])
            ->call('deletePlace');

        $this->assertDatabaseHas('places', ['id' => $place->id]);
    }

    public function test_an_empty_place_can_be_deleted(): void
    {
        $place = Place::factory()->for($this->home)->create();

        Livewire::test(Show::class, ['place' => $place])
            ->call('deletePlace')
            ->assertRedirect(route('places.index'));

        $this->assertDatabaseMissing('places', ['id' => $place->id]);
    }

    public function test_places_from_another_home_are_not_accessible(): void
    {
        $otherHome = Home::factory()->create();
        $foreign = Place::factory()->for($otherHome)->create();

        $this->get(route('places.show', $foreign))->assertNotFound();
    }
}
