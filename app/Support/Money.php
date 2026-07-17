<?php

namespace App\Support;

use App\Casts\MoneyCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;

/**
 * A monetary value stored as integer minor units (cents). Single currency for now.
 */
final class Money implements Castable
{
    public function __construct(public readonly int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function fromDollars(float|int $dollars): self
    {
        return new self((int) round($dollars * 100));
    }

    public function dollars(): float
    {
        return $this->cents / 100;
    }

    public function format(): string
    {
        if ($this->cents % 100 === 0) {
            return '$'.number_format(intdiv($this->cents, 100));
        }

        return '$'.number_format($this->dollars(), 2);
    }

    public static function castUsing(array $arguments): string
    {
        return MoneyCast::class;
    }
}
