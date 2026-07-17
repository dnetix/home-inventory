<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Home;
use App\Models\Item;
use App\Models\Place;
use App\Models\Tag;
use App\Models\User;
use App\Support\SearchItems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchItemsTest extends TestCase
{
    use RefreshDatabase;

    private Home $home;

    private Place $garage;

    private Place $shelf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = Home::factory()->create();
        $user = User::factory()->create(['current_home_id' => $this->home->id]);
        $this->home->users()->attach($user, ['role' => 'owner']);
        $this->actingAs($user);

        $this->garage = Place::factory()->for($this->home)->create(['label' => 'Garage']);
        $this->shelf = Place::factory()->for($this->home)->childOf($this->garage)->create(['label' => 'Shelf B']);
    }

    private function search(string $term, ?int $withinPlaceId = null): array
    {
        return (new SearchItems)->query($term, $withinPlaceId)->pluck('name')->all();
    }

    public function test_matches_item_names_by_word_and_prefix(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);
        Item::factory()->for($this->home)->create(['name' => 'Drill bit set']);
        Item::factory()->for($this->home)->create(['name' => 'Espresso machine']);

        $this->assertSame(['Drill bit set', 'Cordless drill'], $this->search('drill'));
        $this->assertSame(['Espresso machine'], $this->search('espr'));
    }

    public function test_matches_notes(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill', 'note' => 'DeWalt 20V']);

        $this->assertSame(['Cordless drill'], $this->search('dewalt'));
    }

    public function test_matches_tag_labels(): void
    {
        $seasonal = Tag::factory()->for($this->home)->create(['label' => 'seasonal']);
        $jacket = Item::factory()->for($this->home)->create(['name' => 'Ski jacket']);
        $jacket->tags()->attach($seasonal);
        Item::factory()->for($this->home)->create(['name' => 'Passport']);

        $this->assertSame(['Ski jacket'], $this->search('seasonal'));
    }

    public function test_matches_category_labels(): void
    {
        $kitchen = Category::factory()->for($this->home)->create(['label' => 'Kitchen']);
        Item::factory()->for($this->home)->for($kitchen)->create(['name' => 'Stand mixer']);

        $this->assertSame(['Stand mixer'], $this->search('kitchen'));
    }

    public function test_place_matches_include_descendant_places(): void
    {
        Item::factory()->for($this->home)->for($this->shelf, 'place')->create(['name' => 'Cordless drill']);
        Item::factory()->for($this->home)->create(['name' => 'Passport']);

        $this->assertSame(['Cordless drill'], $this->search('garage'));
    }

    public function test_every_word_must_match_something(): void
    {
        Item::factory()->for($this->home)->for($this->shelf, 'place')->create(['name' => 'Cordless drill']);
        $kitchen = Place::factory()->for($this->home)->create(['label' => 'Kitchen']);
        Item::factory()->for($this->home)->for($kitchen, 'place')->create(['name' => 'Backup drill']);

        $this->assertSame(['Backup drill'], $this->search('kitchen drill'));
    }

    public function test_scopes_results_to_a_place_subtree(): void
    {
        Item::factory()->for($this->home)->for($this->shelf, 'place')->create(['name' => 'Cordless drill']);
        $office = Place::factory()->for($this->home)->create(['label' => 'Office']);
        Item::factory()->for($this->home)->for($office, 'place')->create(['name' => 'Office drill']);

        $this->assertSame(['Cordless drill'], $this->search('drill', $this->garage->id));
    }

    public function test_blank_query_returns_nothing(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);

        $this->assertSame([], $this->search('   '));
    }

    public function test_results_never_cross_homes(): void
    {
        $otherHome = Home::factory()->create();
        Item::factory()->for($otherHome)->create(['name' => 'Their drill']);
        Item::factory()->for($this->home)->create(['name' => 'My drill']);

        $this->assertSame(['My drill'], $this->search('drill'));
    }

    public function test_name_prefix_ranks_before_substring_matches(): void
    {
        Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);
        Item::factory()->for($this->home)->create(['name' => 'Drill press']);

        $this->assertSame(['Drill press', 'Cordless drill'], $this->search('drill'));
    }
}
