<?php

namespace App\Support;

use App\Casts\DimensionsCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * A W×H×D triple in integer millimeters.
 */
final class Dimensions implements Arrayable, Castable
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly int $depth,
    ) {
        if ($width <= 0 || $height <= 0 || $depth <= 0) {
            throw new InvalidArgumentException('Dimensions must be positive millimeters.');
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $dims
     */
    public static function fromArray(array $dims): self
    {
        return new self((int) $dims[0], (int) $dims[1], (int) $dims[2]);
    }

    public function volumeMm3(): int
    {
        return $this->width * $this->height * $this->depth;
    }

    public function volumeLitres(): float
    {
        return $this->volumeMm3() / 1_000_000;
    }

    /**
     * @return array{0: int, 1: int, 2: int} the triple sorted longest-first
     */
    public function sortedDesc(): array
    {
        $dims = [$this->width, $this->height, $this->depth];
        rsort($dims);

        return $dims;
    }

    /**
     * Bounding-box check allowing any axis rotation (heuristic, not true 3-D packing).
     */
    public function fitsWithin(self $container): bool
    {
        [$a, $b] = [$this->sortedDesc(), $container->sortedDesc()];

        return $a[0] <= $b[0] && $a[1] <= $b[1] && $a[2] <= $b[2];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function toArray(): array
    {
        return [$this->width, $this->height, $this->depth];
    }

    public static function castUsing(array $arguments): string
    {
        return DimensionsCast::class;
    }
}
