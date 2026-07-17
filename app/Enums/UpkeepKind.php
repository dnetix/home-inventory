<?php

namespace App\Enums;

enum UpkeepKind: string
{
    case Maint = 'maint';
    case Expiry = 'expiry';

    public function label(): string
    {
        return match ($this) {
            self::Maint => 'Maintenance',
            self::Expiry => 'Expiry',
        };
    }
}
