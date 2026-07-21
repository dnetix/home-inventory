<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Livewire\Items\Find;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            ->set('cat', $tools->id)
            ->assertSee('Cordless drill')
            ->assertDontSee('Passport')
            ->set('cat', $power->id)
            ->assertSee('Cordless drill')
            ->assertDontSee('Passport')
            ->set('cat', null)
            ->assertSee('Passport');
    }

    public function test_index_filters_by_tag(): void
    {
        $tag = Tag::factory()->for($this->home)->create(['label' => 'fragile']);
        $tagged = Item::factory()->for($this->home)->create(['name' => 'Glass vase']);
        $tagged->tags()->attach($tag);
        Item::factory()->for($this->home)->create(['name' => 'Anvil']);

        Livewire::test(Index::class)
            ->call('setTagFilter', $tag->id)
            ->assertSee('Glass vase')
            ->assertDontSee('Anvil')
            ->call('clearFilters')
            ->assertSee('Anvil');
    }

    public function test_index_tag_filter_ignores_other_homes_tags(): void
    {
        $otherHome = Home::factory()->create();
        $foreignTag = Tag::factory()->for($otherHome)->create();
        Item::factory()->for($this->home)->create(['name' => 'Glass vase']);

        Livewire::test(Index::class)
            ->call('setTagFilter', $foreignTag->id)
            ->assertSet('tag', null)
            ->assertSee('Glass vase');
    }

    public function test_index_shows_tags_only_when_the_toggle_is_on(): void
    {
        $tag = Tag::factory()->for($this->home)->create(['label' => 'heirloom']);
        $item = Item::factory()->for($this->home)->create(['name' => 'Pocket watch']);
        $item->tags()->attach($tag);

        Livewire::test(Index::class)
            ->assertDontSee('heirloom')
            ->set('showTags', true)
            ->assertSee('heirloom');
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

    public function test_form_suggests_possible_duplicates_while_typing_a_name(): void
    {
        $place = Place::factory()->for($this->home)->create(['label' => 'Garage']);
        $drill = Item::factory()->for($this->home)->for($place)->create(['name' => 'Cordless drill']);
        Item::factory()->for($this->home)->create(['name' => 'Passport']);

        Livewire::test(Form::class)
            ->set('form.name', 'drill')
            ->assertSee('Already in your inventory?')
            ->assertSee('Cordless drill')
            ->assertSee(route('items.show', $drill))
            ->assertSee('Garage')
            ->assertDontSee('Passport');
    }

    public function test_form_duplicate_hints_are_not_shown_when_editing(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);
        $item = Item::factory()->for($this->home)->create(['name' => 'Hammer']);

        Livewire::test(Form::class, ['item' => $item])
            ->set('form.name', 'Cordless drill')
            ->assertDontSee('Already in your inventory?');
    }

    public function test_form_duplicate_hints_exclude_other_homes(): void
    {
        $otherHome = Home::factory()->create();
        Item::factory()->for($otherHome)->create(['name' => 'Cordless drill']);

        Livewire::test(Form::class)
            ->set('form.name', 'drill')
            ->assertDontSee('Already in your inventory?')
            ->assertDontSee('Cordless drill');
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

    public function test_form_saves_a_warranty_date_shown_active_on_the_detail(): void
    {
        Livewire::test(Form::class)
            ->set('form.name', 'Espresso machine')
            ->set('form.warrantyUntil', today()->addYear()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::forHome($this->home)->firstOrFail();

        $this->assertSame(today()->addYear()->toDateString(), $item->warranty_until->toDateString());

        Livewire::test(Show::class, ['item' => $item])
            ->assertSee(today()->addYear()->format('Y-m-d'))
            ->assertSee('active');
    }

    public function test_an_expired_warranty_shows_as_expired(): void
    {
        $item = Item::factory()->for($this->home)->create(['warranty_until' => today()->subMonth()]);

        Livewire::test(Show::class, ['item' => $item])
            ->assertSee(today()->subMonth()->format('Y-m-d'))
            ->assertSee('expired');
    }

    public function test_an_upkeep_task_can_be_created_from_the_item_view(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Wi-Fi router']);

        Livewire::test(Show::class, ['item' => $item])
            ->call('startUpkeep', $item->id)
            ->set('upkeepForm.task', 'Restart & update')
            ->set('upkeepForm.dueDate', today()->addWeek()->toDateString())
            ->set('upkeepForm.every', 'P1M')
            ->call('saveUpkeep')
            ->assertHasNoErrors()
            ->assertSet('upkeepItemId', null);

        $task = \App\Models\UpkeepTask::forHome($this->home)->firstOrFail();

        $this->assertSame($item->id, $task->item_id);
        $this->assertSame('Wi-Fi router', $task->subject);
        $this->assertSame('P1M', $task->every);
    }

    public function test_the_item_upkeep_sheet_can_be_cancelled_without_saving(): void
    {
        $item = Item::factory()->for($this->home)->create();

        Livewire::test(Show::class, ['item' => $item])
            ->call('startUpkeep', $item->id)
            ->set('upkeepForm.task', 'Never mind')
            ->call('cancelUpkeep')
            ->assertSet('upkeepItemId', null);

        $this->assertSame(0, \App\Models\UpkeepTask::forHome($this->home)->count());
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

    public function test_item_status_can_be_changed_and_shows_a_pill(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Garden hose']);

        Livewire::test(Show::class, ['item' => $item])
            ->call('setStatus', $item->id, 'missing');

        $this->assertSame(ItemStatus::Missing, $item->fresh()->status);

        Livewire::test(Index::class)->assertSee('missing');
    }

    public function test_index_filters_by_status(): void
    {
        Item::factory()->for($this->home)->missing()->create(['name' => 'Lost keys']);
        Item::factory()->for($this->home)->create(['name' => 'Passport']);

        Livewire::test(Index::class)
            ->call('setStatusFilter', 'missing')
            ->assertSee('Lost keys')
            ->assertDontSee('Passport');
    }

    public function test_removed_items_act_like_soft_deleted_until_restored(): void
    {
        $removed = Item::factory()->for($this->home)->removed()->create(['name' => 'Old toaster']);

        Livewire::test(Index::class)
            ->assertDontSee('Old toaster')
            ->set('search', 'toaster')
            ->assertDontSee('Old toaster');

        Livewire::test(Index::class)
            ->call('setStatusFilter', 'removed')
            ->assertSee('Old toaster');

        $this->get(route('items.show', $removed))
            ->assertOk()
            ->assertSee('Removed from inventory');

        Livewire::test(Show::class, ['item' => $removed])
            ->call('setStatus', $removed->id, 'in_place');

        $this->assertSame(ItemStatus::InPlace, $removed->fresh()->status);
    }

    public function test_status_cannot_be_changed_for_another_homes_item(): void
    {
        $otherHome = Home::factory()->create();
        $foreign = Item::factory()->for($otherHome)->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Index::class)->call('setStatus', $foreign->id, 'missing');
    }

    public function test_batch_move_relocates_only_the_selected_items(): void
    {
        $shelf = Place::factory()->for($this->home)->create(['label' => 'Shelf A']);
        $a = Item::factory()->for($this->home)->create(['name' => 'Hammer']);
        $b = Item::factory()->for($this->home)->create(['name' => 'Saw']);
        $untouched = Item::factory()->for($this->home)->create(['name' => 'Tape']);

        Livewire::test(Index::class)
            ->call('toggleSelecting')
            ->call('toggleSelected', $a->id)
            ->call('toggleSelected', $b->id)
            ->call('openBatch', 'move')
            ->set('batchPlaceId', $shelf->id)
            ->call('confirmBatchMove')
            ->assertSet('selecting', false);

        $this->assertSame($shelf->id, $a->fresh()->place_id);
        $this->assertSame($shelf->id, $b->fresh()->place_id);
        $this->assertNull($untouched->fresh()->place_id);
    }

    public function test_batch_status_change_applies_to_all_selected_items(): void
    {
        $a = Item::factory()->for($this->home)->create();
        $b = Item::factory()->for($this->home)->create();

        Livewire::test(Index::class)
            ->call('toggleSelecting')
            ->call('selectMany', [$a->id, $b->id])
            ->call('openBatch', 'status')
            ->call('batchSetStatus', 'missing');

        $this->assertSame(ItemStatus::Missing, $a->fresh()->status);
        $this->assertSame(ItemStatus::Missing, $b->fresh()->status);
    }

    public function test_batch_remove_hides_items_from_the_list(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Rusty rake']);

        Livewire::test(Index::class)
            ->call('toggleSelecting')
            ->call('toggleSelected', $item->id)
            ->call('openBatch', 'status')
            ->call('batchSetStatus', 'removed')
            ->assertDontSee('Rusty rake');

        $this->assertSame(ItemStatus::Removed, $item->fresh()->status);
    }

    public function test_batch_operations_ignore_items_from_other_homes(): void
    {
        $otherHome = Home::factory()->create();
        $foreign = Item::factory()->for($otherHome)->create();
        $shelf = Place::factory()->for($this->home)->create();

        Livewire::test(Index::class)
            ->call('toggleSelecting')
            ->call('selectMany', [$foreign->id])
            ->call('openBatch', 'move')
            ->set('batchPlaceId', $shelf->id)
            ->call('confirmBatchMove');

        $this->assertNull($foreign->fresh()->place_id);
    }

    public function test_batch_status_works_from_the_find_screen(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);

        Livewire::test(Find::class)
            ->set('search', 'drill')
            ->call('toggleSelecting')
            ->call('toggleSelected', $item->id)
            ->call('openBatch', 'status')
            ->call('batchSetStatus', 'broken');

        $this->assertSame(ItemStatus::Broken, $item->fresh()->status);
    }

    public function test_items_from_another_home_are_not_accessible(): void
    {
        $otherHome = Home::factory()->create();
        $foreign = Item::factory()->for($otherHome)->create();

        $this->get(route('items.show', $foreign))->assertNotFound();
    }
}
