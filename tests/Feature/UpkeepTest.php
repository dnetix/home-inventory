<?php

namespace Tests\Feature;

use App\Livewire\Upkeep\Index;
use App\Models\Home;
use App\Models\Item;
use App\Models\UpkeepLog;
use App\Models\UpkeepTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpkeepTest extends TestCase
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

    public function test_index_renders_the_agenda_and_calendar(): void
    {
        UpkeepTask::factory()->for($this->home)->overdue()->create(['task' => 'Replace air filter']);

        $this->get(route('upkeep.index'))
            ->assertOk()
            ->assertSee('Replace air filter')
            ->assertSee(today()->format('F Y'));
    }

    public function test_a_task_linked_to_an_item_snapshots_its_name(): void
    {
        $item = Item::factory()->for($this->home)->create(['name' => 'Wi-Fi router']);

        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('form.itemId', $item->id)
            ->set('form.task', 'Restart & update')
            ->set('form.dueDate', today()->addWeek()->toDateString())
            ->set('form.every', 'P1M')
            ->call('save')
            ->assertHasNoErrors();

        $task = UpkeepTask::forHome($this->home)->firstOrFail();

        $this->assertSame('Wi-Fi router', $task->subject);
        $this->assertSame('P1M', $task->every);
    }

    public function test_a_free_subject_task_requires_a_subject(): void
    {
        Livewire::test(Index::class)
            ->call('openCreate')
            ->set('form.task', 'Clean gutters')
            ->set('form.dueDate', today()->addWeek()->toDateString())
            ->call('save')
            ->assertHasErrors(['form.subject']);
    }

    public function test_completing_a_recurring_task_rolls_the_due_date_forward(): void
    {
        $task = UpkeepTask::factory()->for($this->home)->overdue()->recurring('P3M')->create();

        Livewire::test(Index::class)
            ->call('startCompleting', $task->id)
            ->call('complete');

        $task->refresh();

        $this->assertTrue($task->due_date->equalTo(today()->addMonths(3)));

        $log = UpkeepLog::forHome($this->home)->firstOrFail();
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertTrue($log->completed_on->isToday());
    }

    public function test_completing_a_one_time_task_marks_it_done(): void
    {
        $task = UpkeepTask::factory()->for($this->home)->dueSoon()->create();

        Livewire::test(Index::class)
            ->call('startCompleting', $task->id)
            ->set('completedOn', 'yesterday')
            ->call('complete');

        $task->refresh();

        $this->assertNull($task->due_date);
        $this->assertTrue(UpkeepLog::forHome($this->home)->first()->completed_on->isYesterday());
    }

    public function test_deleting_a_task_keeps_its_log_entries(): void
    {
        $task = UpkeepTask::factory()->for($this->home)->dueSoon()->create();

        Livewire::test(Index::class)
            ->call('startCompleting', $task->id)
            ->call('complete')
            ->call('openEdit', $task->id)
            ->call('deleteTask');

        $this->assertDatabaseMissing('upkeep_tasks', ['id' => $task->id]);

        $log = UpkeepLog::forHome($this->home)->firstOrFail();
        $this->assertNull($log->upkeep_task_id);
        $this->assertSame($task->task, $log->task);
    }

    public function test_month_navigation_changes_the_calendar(): void
    {
        Livewire::test(Index::class)
            ->assertSee(today()->format('F Y'))
            ->call('nextMonth')
            ->assertSee(today()->addMonthNoOverflow()->startOfMonth()->format('F Y'));
    }
}
