<?php

namespace App\Support;

use App\Enums\FitStatus;

final class FitResult
{
    public function __construct(
        public readonly FitStatus $status,
        public readonly ?float $capacityLitres = null,
        public readonly ?float $usedLitres = null,
        public readonly ?float $neededLitres = null,
    ) {}

    public function remainingLitres(): ?float
    {
        if ($this->capacityLitres === null || $this->usedLitres === null) {
            return null;
        }

        return $this->capacityLitres - $this->usedLitres;
    }
}
