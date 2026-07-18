<?php

namespace App\Enums;

enum ItemStatus: string
{
    case InPlace = 'in_place';
    case Missing = 'missing';
    case Broken = 'broken';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::InPlace => 'In place',
            self::Missing => 'Missing',
            self::Broken => 'Broken',
            self::Removed => 'Removed',
        };
    }

    /**
     * x-ui.pill variant for states that deserve a pill; null renders plain text.
     */
    public function pillVariant(): ?string
    {
        return match ($this) {
            self::InPlace => null,
            self::Missing => 'warn',
            self::Broken => 'bad',
            self::Removed => 'default',
        };
    }
}
