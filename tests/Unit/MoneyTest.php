<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_whole_dollars_without_decimals(): void
    {
        $this->assertSame('$120', (new Money(12000))->format());
        $this->assertSame('$1,200', (new Money(120000))->format());
    }

    public function test_formats_fractional_dollars_with_two_decimals(): void
    {
        $this->assertSame('$35.50', (new Money(3550))->format());
    }

    public function test_converts_from_dollars(): void
    {
        $this->assertSame(4550, Money::fromDollars(45.5)->cents);
        $this->assertSame(120.0, Money::fromDollars(120)->dollars());
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(-1);
    }
}
