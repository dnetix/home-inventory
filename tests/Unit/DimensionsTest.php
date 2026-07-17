<?php

namespace Tests\Unit;

use App\Support\Dimensions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DimensionsTest extends TestCase
{
    public function test_computes_volume_in_cubic_millimeters_and_litres(): void
    {
        $drill = new Dimensions(250, 220, 90);

        $this->assertSame(4_950_000, $drill->volumeMm3());
        $this->assertSame(4.95, $drill->volumeLitres());
    }

    public function test_sorts_sides_longest_first(): void
    {
        $this->assertSame([1800, 400, 90], (new Dimensions(400, 90, 1800))->sortedDesc());
    }

    public function test_fits_within_allows_rotation(): void
    {
        $longThinItem = new Dimensions(1800, 100, 100);
        $tallContainer = new Dimensions(200, 200, 2000);

        $this->assertTrue($longThinItem->fitsWithin($tallContainer));
    }

    public function test_does_not_fit_when_any_sorted_side_exceeds_container(): void
    {
        $item = new Dimensions(1000, 100, 100);
        $bin = new Dimensions(600, 420, 400);

        $this->assertFalse($item->fitsWithin($bin));
    }

    public function test_round_trips_through_arrays(): void
    {
        $dim = Dimensions::fromArray([250, 220, 90]);

        $this->assertSame([250, 220, 90], $dim->toArray());
    }

    public function test_rejects_non_positive_sides(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Dimensions(0, 100, 100);
    }
}
