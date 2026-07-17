<?php

namespace App\Enums;

enum FitStatus: string
{
    case Fit = 'fit';
    case Tight = 'tight';
    case Full = 'full';
    case TooBig = 'toobig';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Fit => 'Fits',
            self::Tight => 'Tight fit',
            self::Full => 'Not enough space',
            self::TooBig => 'Too big',
            self::Unknown => 'Unknown',
        };
    }
}
