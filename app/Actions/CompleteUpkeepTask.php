<?php

namespace App\Actions;

use App\Models\UpkeepLog;
use App\Models\UpkeepTask;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Log a completion by the upkeeper and reschedule: recurring tasks roll
 * their due date forward from the completion date, one-time tasks are done.
 */
class CompleteUpkeepTask
{
    public function handle(UpkeepTask $task, User $upkeeper, Carbon $completedOn): UpkeepLog
    {
        return DB::transaction(function () use ($task, $upkeeper, $completedOn) {
            $log = UpkeepLog::create([
                'home_id' => $task->home_id,
                'upkeep_task_id' => $task->id,
                'user_id' => $upkeeper->id,
                'task' => $task->task,
                'completed_on' => $completedOn,
            ]);

            $task->update([
                'due_date' => $task->isRecurring()
                    ? $completedOn->copy()->add($task->recurrence())
                    : null,
            ]);

            return $log;
        });
    }
}
