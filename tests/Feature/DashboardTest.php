<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Home;
use App\Models\Item;
use App\Models\Lend;
use App\Models\UpkeepTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_shows_inventory_stats(): void
    {
        Item::factory()->for($this->home)->create(['value' => 12000, 'qty' => 1]);
        Item::factory()->for($this->home)->create(['value' => 30000, 'qty' => 4]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your inventory')
            ->assertSee('$420');
    }

    public function test_category_bars_roll_up_to_top_level(): void
    {
        $tools = Category::factory()->for($this->home)->create(['label' => 'Tools']);
        $power = Category::factory()->childOf($tools)->create(['label' => 'Power tools']);
        Item::factory()->for($this->home)->for($power)->create();
        Item::factory()->for($this->home)->create();

        $this->get(route('dashboard'))
            ->assertSee('Tools')
            ->assertSee('Uncategorized');
    }

    public function test_upcoming_merges_upkeep_and_due_lends(): void
    {
        UpkeepTask::factory()->for($this->home)->overdue()->create(['subject' => 'Furnace']);

        $item = Item::factory()->for($this->home)->create(['name' => 'Camping tent']);
        Lend::factory()->for($item)->create(['due_date' => today()->addDays(4)]);

        $this->get(route('dashboard'))
            ->assertSee('Furnace')
            ->assertSee('Camping tent')
            ->assertSee('Overdue');
    }

    public function test_attention_badge_counts_overdue_and_soon(): void
    {
        UpkeepTask::factory()->for($this->home)->overdue()->create();
        UpkeepTask::factory()->for($this->home)->dueSoon()->create();

        $item = Item::factory()->for($this->home)->create();
        Lend::factory()->for($item)->overdue()->create();

        $this->get(route('dashboard'))->assertSee('Need attention');

        $component = \Livewire\Livewire::test(\App\Livewire\Dashboard::class);
        $this->assertSame(3, $component->instance()->attentionCount);
    }
}
