<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Tags\Index as TagsIndex;
use App\Models\Category;
use App\Models\Home;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizeTest extends TestCase
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

    public function test_categories_screen_shows_the_tree_with_rolled_up_counts(): void
    {
        $tools = Category::factory()->for($this->home)->create(['label' => 'Tools']);
        $power = Category::factory()->childOf($tools)->create(['label' => 'Power tools']);
        Item::factory()->for($this->home)->for($power)->count(2)->create();

        Livewire::test(CategoriesIndex::class)
            ->assertSee('Tools')
            ->assertSet('open', [])
            ->call('toggle', $tools->id)
            ->assertSee('Power tools')
            ->assertSeeHtml('>2<');
    }

    public function test_a_category_can_be_created_nested(): void
    {
        $tools = Category::factory()->for($this->home)->create(['label' => 'Tools']);

        Livewire::test(CategoriesIndex::class)
            ->set('form.label', 'Hand tools')
            ->set('form.color', '#4f74e3')
            ->set('form.parentId', $tools->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(
            Category::forHome($this->home)->where('label', 'Hand tools')->where('parent_id', $tools->id)->exists()
        );
    }

    public function test_a_parent_category_cannot_be_nested(): void
    {
        $tools = Category::factory()->for($this->home)->create(['label' => 'Tools']);
        Category::factory()->childOf($tools)->create(['label' => 'Power tools']);
        $other = Category::factory()->for($this->home)->create(['label' => 'Kitchen']);

        Livewire::test(CategoriesIndex::class)
            ->call('startEdit', $tools->id)
            ->set('form.parentId', $other->id)
            ->call('save')
            ->assertHasErrors(['form.parentId']);
    }

    public function test_a_category_accepts_any_lucide_glyph(): void
    {
        Livewire::test(CategoriesIndex::class)
            ->set('form.label', 'Workshop')
            ->set('form.glyph', 'hammer')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Category::forHome($this->home)->where('glyph', 'hammer')->exists());
    }

    public function test_unknown_glyph_names_are_rejected(): void
    {
        Livewire::test(CategoriesIndex::class)
            ->set('form.label', 'Workshop')
            ->set('form.glyph', 'definitely-not-an-icon')
            ->call('save')
            ->assertHasErrors(['form.glyph']);
    }

    public function test_the_glyph_picker_search_shows_matching_icons(): void
    {
        Livewire::test(CategoriesIndex::class)
            ->set('glyphSearch', 'guitar')
            ->assertSeeHtml('title="guitar"');
    }

    public function test_a_category_can_be_renamed_in_place(): void
    {
        $category = Category::factory()->for($this->home)->create(['label' => 'Tools']);

        Livewire::test(CategoriesIndex::class)
            ->call('startEdit', $category->id)
            ->assertSet('form.label', 'Tools')
            ->set('form.label', 'Hardware')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.category', null);

        $this->assertSame('Hardware', $category->fresh()->label);
    }

    public function test_categories_from_another_home_cannot_be_edited(): void
    {
        $otherHome = Home::factory()->create();
        $category = Category::factory()->for($otherHome)->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(CategoriesIndex::class)->call('startEdit', $category->id);
    }

    public function test_deleting_a_category_leaves_items_uncategorized(): void
    {
        $category = Category::factory()->for($this->home)->create();
        $item = Item::factory()->for($this->home)->for($category)->create();

        Livewire::test(CategoriesIndex::class)
            ->call('delete', $category->id);

        $this->assertNull($item->fresh()->category_id);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_a_tag_can_be_created_and_is_lowercased(): void
    {
        Livewire::test(TagsIndex::class)
            ->set('form.label', 'Heirloom')
            ->set('form.color', '#8a5cc0')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Tag::forHome($this->home)->where('label', 'heirloom')->exists());
    }

    public function test_duplicate_tag_labels_are_rejected_within_a_home(): void
    {
        Tag::factory()->for($this->home)->create(['label' => 'seasonal']);

        Livewire::test(TagsIndex::class)
            ->set('form.label', 'seasonal')
            ->call('save')
            ->assertHasErrors(['form.label']);
    }

    public function test_a_tag_can_be_renamed_with_notes(): void
    {
        $tag = Tag::factory()->for($this->home)->create(['label' => 'seasonal']);

        Livewire::test(TagsIndex::class)
            ->call('startEdit', $tag->id)
            ->assertSet('form.label', 'seasonal')
            ->set('form.label', 'Holiday')
            ->set('form.description', 'Only used part of the year')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.tag', null);

        $tag->refresh();
        $this->assertSame('holiday', $tag->label);
        $this->assertSame('Only used part of the year', $tag->description);
    }

    public function test_editing_a_tag_keeps_its_own_label_valid(): void
    {
        $tag = Tag::factory()->for($this->home)->create(['label' => 'seasonal']);

        Livewire::test(TagsIndex::class)
            ->call('startEdit', $tag->id)
            ->set('form.description', 'Winter stuff')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Winter stuff', $tag->fresh()->description);
    }

    public function test_tag_notes_show_on_the_list(): void
    {
        Tag::factory()->for($this->home)->create(['label' => 'fragile', 'description' => 'Handle with care when moving']);

        Livewire::test(TagsIndex::class)->assertSee('Handle with care when moving');
    }

    public function test_tags_from_another_home_cannot_be_edited(): void
    {
        $otherHome = Home::factory()->create();
        $tag = Tag::factory()->for($otherHome)->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(TagsIndex::class)->call('startEdit', $tag->id);
    }

    public function test_deleting_a_tag_detaches_it_from_items(): void
    {
        $tag = Tag::factory()->for($this->home)->create();
        $item = Item::factory()->for($this->home)->create();
        $item->tags()->attach($tag);

        Livewire::test(TagsIndex::class)
            ->call('delete', $tag->id);

        $this->assertSame(0, $item->tags()->count());
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
