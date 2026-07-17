<?php

namespace Tests\Feature;

use App\Livewire\Lending\Index;
use App\Models\Home;
use App\Models\Item;
use App\Models\Lend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LendingTest extends TestCase
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

    public function test_index_lists_active_lends(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Cordless drill']);
        Lend::factory()->for($item)->create(['person' => 'Marco']);

        $this->get(route('lending.index'))
            ->assertOk()
            ->assertSee('Cordless drill')
            ->assertSee('Marco');
    }

    public function test_filters_show_overdue_and_returned_lends(): void
    {
        $overdue = Item::factory()->for($this->home)->create(['name' => 'Overdue thing']);
        Lend::factory()->for($overdue)->overdue()->create();

        $returned = Item::factory()->for($this->home)->create(['name' => 'Returned thing']);
        Lend::factory()->for($returned)->returned()->create();

        Livewire::test(Index::class)
            ->call('setFilter', 'overdue')
            ->assertSee('Overdue thing')
            ->assertDontSee('Returned thing')
            ->call('setFilter', 'returned')
            ->assertSee('Returned thing')
            ->assertDontSee('Overdue thing');
    }

    public function test_an_item_can_be_lent(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Camping tent']);

        Livewire::test(Index::class)
            ->call('openLend')
            ->call('pickItem', $item->id)
            ->set('form.person', 'Aunt Rosa')
            ->set('form.dueDate', today()->addDays(10)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $lend = Lend::forHome($this->home)->where('person', 'Aunt Rosa')->firstOrFail();

        $this->assertSame($item->id, $lend->item_id);
        $this->assertTrue($lend->out_date->isToday());
        $this->assertTrue($lend->remind);
        $this->assertNull($lend->returned_at);
    }

    public function test_an_already_lent_item_cannot_be_lent_again(): void
    {
        $item = Item::factory()->for($this->home)->create();
        Lend::factory()->for($item)->create();

        Livewire::test(Index::class)
            ->call('openLend')
            ->call('pickItem', $item->id)
            ->set('form.person', 'Dan')
            ->call('save')
            ->assertHasErrors(['form.itemId']);
    }

    public function test_a_lend_can_be_marked_returned(): void
    {
        $item = Item::factory()->for($this->home)->create();
        $lend = Lend::factory()->for($item)->create();

        Livewire::test(Index::class)
            ->call('returnLend', $lend->id);

        $this->assertTrue($lend->fresh()->returned_at->isToday());
    }

    public function test_summary_totals_value_on_loan(): void
    {
        $drill = Item::factory()->for($this->home)->create(['value' => 12000]);
        $tent = Item::factory()->for($this->home)->create(['value' => 30000]);
        Lend::factory()->for($drill)->create();
        Lend::factory()->for($tent)->create();

        Livewire::test(Index::class)->assertSee('$420');
    }
}
