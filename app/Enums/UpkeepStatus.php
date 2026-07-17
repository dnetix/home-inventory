<?php

namespace App\Enums;

enum UpkeepStatus: string
{
    case Overdue = 'overdue';
    case Soon = 'soon';
    case Upcoming = 'upcoming';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Overdue => 'Overdue',
            self::Soon => 'Due soon',
            self::Upcoming => 'Upcoming',
            self::Done => 'Done',
        };
    }
}
