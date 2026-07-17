<?php

namespace Tests\Unit;

use App\Enums\Unit;
use App\Support\Dimensions;
use App\Support\UnitFormatter;
use PHPUnit\Framework\TestCase;

class UnitFormatterTest extends TestCase
{
    public function test_metric_dimensions_render_in_centimeters(): void
    {
        $formatter = UnitFormatter::for(Unit::Metric);

        $this->assertSame('25 × 22 × 9 cm', $formatter->dim(new Dimensions(250, 220, 90)));
        $this->assertSame('25.5 × 22 × 9 cm', $formatter->dim(new Dimensions(255, 220, 90)));
    }

    public function test_imperial_dimensions_render_in_inches(): void
    {
        $formatter = UnitFormatter::for(Unit::Imperial);

        $this->assertSame('9.8 × 8.7 × 3.5 in', $formatter->dim(new Dimensions(250, 220, 90)));
    }

    public function test_missing_dimensions_render_as_dash(): void
    {
        $this->assertSame('—', UnitFormatter::for(Unit::Metric)->dim(null));
        $this->assertSame('—', UnitFormatter::for(Unit::Metric)->volume(null));
    }

    public function test_metric_volumes_scale_from_litres_to_cubic_meters(): void
    {
        $formatter = UnitFormatter::for(Unit::Metric);

        $this->assertSame('5 L', $formatter->volume(4.95));
        $this->assertSame('6.3 L', $formatter->volume(6.27));
        $this->assertSame('648 L', $formatter->volume(648.0));
        $this->assertSame('1.50 m³', $formatter->volume(1500.0));
        $this->assertSame('12.0 m³', $formatter->volume(12000.0));
    }

    public function test_imperial_volumes_scale_from_cubic_inches_to_cubic_feet(): void
    {
        $formatter = UnitFormatter::for(Unit::Imperial);

        $this->assertSame('302 in³', $formatter->volume(4.95));
        $this->assertSame('1.4 ft³', $formatter->volume(40.0));
        $this->assertSame('23 ft³', $formatter->volume(648.0));
    }

    public function test_display_input_converts_back_to_millimeters(): void
    {
        $this->assertSame(255, UnitFormatter::for(Unit::Metric)->displayToMm(25.5));
        $this->assertSame(254, UnitFormatter::for(Unit::Imperial)->displayToMm(10));
    }

    public function test_millimeters_convert_to_display_units(): void
    {
        $this->assertSame(25.5, UnitFormatter::for(Unit::Metric)->mmToDisplay(255));
        $this->assertSame(9.8, UnitFormatter::for(Unit::Imperial)->mmToDisplay(250));
    }
}
