<?php

namespace Tests\Feature;

use App\Livewire\Items\Form;
use App\Livewire\Items\Index;
use App\Livewire\Items\Show;
use App\Models\Category;
use App\Models\Home;
use App\Models\Item;
use App\Models\Lend;
use App\Models\Place;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemsTest extends TestCase
{
    use RefreshDatabase;

    private Home $home;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = Home::factory()->create();
        $this->user = User::factory()->create(['current_home_id' => $this->home->id]);
        $this->home->users()->attach($this->user, ['role' => 'owner']);
        $this->actingAs($this->user);
    }

    public function test_index_lists_the_homes_items(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('Cordless drill');
    }

    public function test_index_search_narrows_results(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);
        Item::factory()->for($this->home)->create(['name' => 'Passport']);

        Livewire::test(Index::class)
            ->set('search', 'drill')
            ->assertSee('Cordless drill')
            ->assertDontSee('Passport');
    }

    public function test_index_filters_by_category_including_children(): void
    {
        $tools = Category::factory()->for($this->home)->create(['label' => 'Tools']);
        $power = Category::factory()->childOf($tools)->create(['label' => 'Power tools']);
        Item::factory()->for($this->home)->for($power)->create(['name' => 'Cordless drill']);
        Item::factory()->for($this->home)->create(['name' => 'Passport']);

        Livewire::test(Index::class)
            ->set('cat', (string) $tools->id)
            ->assertSee('Cordless drill')
            ->assertDontSee('Passport');
    }

    public function test_index_missing_data_filter(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Unpriced thing', 'value' => null]);
        Item::factory()->for($this->home)->valued()->create(['name' => 'Priced thing']);

        Livewire::test(Index::class)
            ->call('setMissing', 'value')
            ->assertSee('Unpriced thing')
            ->assertDontSee('Priced thing');
    }

    public function test_index_selection_shows_detail_pane(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Espresso machine', 'note' => 'Breville']);

        Livewire::test(Index::class)
            ->call('select', $item->id)
            ->assertSee('Item details')
            ->assertSee('Breville');
    }

    public function test_index_sorts_by_value(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cheap', 'value' => 1000]);
        Item::factory()->for($this->home)->create(['name' => 'Expensive', 'value' => 99000]);

        Livewire::test(Index::class)
            ->call('sortBy', 'value')
            ->assertSeeInOrder(['Cheap', 'Expensive'])
            ->call('sortBy', 'value')
            ->assertSeeInOrder(['Expensive', 'Cheap']);
    }

    public function test_form_creates_an_item_with_converted_units(): void
    {
        $category = Category::factory()->for($this->home)->create();
        $place = Place::factory()->for($this->home)->create();
        $tag = Tag::factory()->for($this->home)->create();

        Livewire::test(Form::class)
            ->set('form.name', 'Label maker')
            ->set('form.categoryId', $category->id)
            ->set('form.placeId', $place->id)
            ->set('form.qty', '2')
            ->set('form.value', '45.50')
            ->set('form.w', '18')
            ->set('form.h', '6')
            ->set('form.d', '9')
            ->call('toggleTag', $tag->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $item = Item::forHome($this->home)->where('name', 'Label maker')->firstOrFail();

        $this->assertSame(4550, $item->value->cents);
        $this->assertSame([180, 60, 90], $item->dim->toArray());
        $this->assertSame(2, $item->qty);
        $this->assertTrue($item->tags->contains($tag));
        $this->assertSame($this->home->id, $item->home_id);
    }

    public function test_form_remembers_the_last_used_place_and_category_for_the_next_item(): void
    {
        $category = Category::factory()->for($this->home)->create();
        $place = Place::factory()->for($this->home)->create();

        Livewire::test(Form::class)
            ->set('form.name', 'Socket wrench')
            ->set('form.categoryId', $category->id)
            ->set('form.placeId', $place->id)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(Form::class)
            ->assertSet('form.placeId', $place->id)
            ->assertSet('form.categoryId', $category->id);
    }

    public function test_form_does_not_prefill_from_another_homes_session_values(): void
    {
        $otherHome = Home::factory()->create();
        $otherPlace = Place::factory()->for($otherHome)->create();
        $otherCategory = Category::factory()->for($otherHome)->create();

        session([
            'items.last_place_id' => $otherPlace->id,
            'items.last_category_id' => $otherCategory->id,
        ]);

        Livewire::test(Form::class)
            ->assertSet('form.placeId', null)
            ->assertSet('form.categoryId', null);
    }

    public function test_form_requires_a_name_and_complete_dimensions(): void
    {
        Livewire::test(Form::class)
            ->set('form.name', '')
            ->set('form.w', '10')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.h', 'form.d']);
    }

    public function test_form_edits_an_existing_item(): void
    {
        $item = Item::factory()->for($this->home)->withDimensions(new \App\Support\Dimensions(250, 220, 90))->create(['name' => 'Old name']);

        Livewire::test(Form::class, ['item' => $item])
            ->assertSet('form.name', 'Old name')
            ->assertSet('form.w', '25')
            ->set('form.name', 'New name')
            ->call('save')
            ->assertRedirect(route('items.show', $item));

        $this->assertSame('New name', $item->fresh()->name);
    }

    public function test_show_renders_and_deletes(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Camping tent']);

        $this->get(route('items.show', $item))->assertOk()->assertSee('Camping tent');

        Livewire::test(Show::class, ['item' => $item])
            ->call('delete')
            ->assertRedirect(route('items.index'));

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_show_transfers_an_item_with_fit_check(): void
    {
        $item = Item::factory()->for($this->home)->withDimensions(new \App\Support\Dimensions(250, 220, 90))->create();
        $shelf = Place::factory()->for($this->home)->withDimensions(new \App\Support\Dimensions(900, 400, 1800))->create();

        Livewire::test(Show::class, ['item' => $item])
            ->call('startTransfer', $item->id)
            ->set('transferPlaceId', $shelf->id)
            ->assertSee('Fits')
            ->call('confirmTransfer');

        $this->assertSame($shelf->id, $item->fresh()->place_id);
    }

    public function test_show_marks_a_lend_returned(): void
    {
        $item = Item::factory()->for($this->home)->create();
        $lend = Lend::factory()->for($item)->create();

        Livewire::test(Show::class, ['item' => $item])
            ->call('returnLend', $lend->id);

        $this->assertNotNull($lend->fresh()->returned_at);
    }

    public function test_items_from_another_home_are_not_accessible(): void
    {
        $otherHome = Home::factory()->create();
        $foreign = Item::factory()->for($otherHome)->create();

        $this->get(route('items.show', $foreign))->assertNotFound();
    }
}
