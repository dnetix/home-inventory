<?php

namespace App\Support;

use App\Enums\Unit;

/**
 * Formats stored millimeters/litres for display in the user's unit system,
 * and converts user input back to millimeters. Storage never leaves mm.
 */
final class UnitFormatter
{
    private const float MM_PER_INCH = 25.4;

    private const float CUBIC_FEET_PER_LITRE = 0.0353147;

    private const float CUBIC_INCHES_PER_LITRE = 61.0237;

    public function __construct(public readonly Unit $unit) {}

    public static function for(Unit $unit): self
    {
        return new self($unit);
    }

    public function lengthUnitLabel(): string
    {
        return $this->unit === Unit::Imperial ? 'in' : 'cm';
    }

    /**
     * "25 × 22 × 9 cm" / "9.8 × 8.7 × 3.5 in", or "—" when no size is set.
     */
    public function dim(?Dimensions $dim): string
    {
        if ($dim === null) {
            return '—';
        }

        $parts = array_map(
            fn (int $mm): string => $this->trimZeros($this->mmToDisplay($mm)),
            $dim->toArray(),
        );

        return implode(' × ', $parts).' '.$this->lengthUnitLabel();
    }

    /**
     * Litres rendered per the design's rules (L/m³ or ft³/in³), or "—".
     */
    public function volume(?float $litres): string
    {
        if ($litres === null) {
            return '—';
        }

        if ($this->unit === Unit::Imperial) {
            $cubicFeet = $litres * self::CUBIC_FEET_PER_LITRE;

            if ($cubicFeet >= 1) {
                return ($cubicFeet >= 10 ? (string) round($cubicFeet) : number_format($cubicFeet, 1)).' ft³';
            }

            return round($litres * self::CUBIC_INCHES_PER_LITRE).' in³';
        }

        if ($litres >= 1000) {
            return number_format($litres / 1000, $litres >= 10000 ? 1 : 2).' m³';
        }

        if ($litres >= 10) {
            return round($litres).' L';
        }

        return $this->trimZeros(round($litres, 1)).' L';
    }

    /**
     * A stored millimeter length in the display unit (cm or in).
     */
    public function mmToDisplay(int $mm): float
    {
        return $this->unit === Unit::Imperial
            ? round($mm / self::MM_PER_INCH, 1)
            : round($mm / 10, 1);
    }

    /**
     * User input in the display unit (cm or in) back to integer millimeters.
     */
    public function displayToMm(float $value): int
    {
        return $this->unit === Unit::Imperial
            ? (int) round($value * self::MM_PER_INCH)
            : (int) round($value * 10);
    }

    private function trimZeros(float $value): string
    {
        $formatted = number_format($value, 1, '.', '');

        return str_ends_with($formatted, '.0') ? substr($formatted, 0, -2) : $formatted;
    }
}
