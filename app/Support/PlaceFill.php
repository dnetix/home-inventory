<?php

namespace App\Support;

/**
 * How full a place is: capacity vs. the summed volume of every item stored
 * in it or any of its descendants.
 */
final class PlaceFill
{
    public function __construct(
        public readonly ?float $capacityLitres,
        public readonly float $usedLitres,
        public readonly int $measuredCount,
        public readonly int $totalCount,
    ) {}

    /**
     * Fill percentage capped at 100, or null when the place has no size set.
     */
    public function percent(): ?float
    {
        if ($this->capacityLitres === null || $this->capacityLitres <= 0.0) {
            return null;
        }

        return min(100.0, $this->usedLitres / $this->capacityLitres * 100);
    }

    public function isOverCapacity(): bool
    {
        return $this->capacityLitres !== null && $this->usedLitres > $this->capacityLitres;
    }

    public function remainingLitres(): ?float
    {
        return $this->capacityLitres === null ? null : $this->capacityLitres - $this->usedLitres;
    }
}
