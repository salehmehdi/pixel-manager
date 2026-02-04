<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidMoneyException;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class MoneyTest extends TestCase
{
    public function test_can_create_valid_money(): void
    {
        $money = new Money(99.99, Currency::USD);

        $this->assertEquals(99.99, $money->amount);
        $this->assertEquals(Currency::USD, $money->currency);
    }

    public function test_can_create_with_zero_amount(): void
    {
        $money = new Money(0, Currency::EUR);

        $this->assertEquals(0, $money->amount);
    }

    public function test_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Money amount cannot be negative');

        new Money(-10.00, Currency::USD);
    }

    public function test_can_create_from_array(): void
    {
        $money = Money::from(49.99, 'USD');

        $this->assertEquals(49.99, $money->amount);
        $this->assertEquals(Currency::USD, $money->currency);
    }

    public function test_can_format_money(): void
    {
        $money = new Money(1234.56, Currency::USD);
        $formatted = $money->format();

        $this->assertEquals('$1,234.56', $formatted);
    }

    public function test_can_format_azn_currency(): void
    {
        $money = new Money(500, Currency::AZN);
        $formatted = $money->format();

        $this->assertEquals('₼500.00', $formatted);
    }

    public function test_can_format_with_custom_decimals(): void
    {
        $money = new Money(99.999, Currency::EUR);
        $formatted = $money->format(3);

        $this->assertStringContainsString('99.999', $formatted);
    }

    public function test_can_add_money(): void
    {
        $money1 = new Money(50, Currency::USD);
        $money2 = new Money(25, Currency::USD);

        $result = $money1->add($money2);

        $this->assertEquals(75, $result->amount);
    }

    public function test_throws_exception_when_adding_different_currencies(): void
    {
        $this->expectException(InvalidMoneyException::class);

        $money1 = new Money(50, Currency::USD);
        $money2 = new Money(25, Currency::EUR);

        $money1->add($money2);
    }

    public function test_can_subtract_money(): void
    {
        $money1 = new Money(100, Currency::USD);
        $money2 = new Money(30, Currency::USD);

        $result = $money1->subtract($money2);

        $this->assertEquals(70, $result->amount);
    }

    public function test_can_multiply_money(): void
    {
        $money = new Money(10, Currency::USD);

        $result = $money->multiply(5);

        $this->assertEquals(50, $result->amount);
    }

    public function test_can_compare_money(): void
    {
        $money1 = new Money(100, Currency::USD);
        $money2 = new Money(50, Currency::USD);

        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertFalse($money1->isLessThan($money2));
    }

    public function test_can_check_equality(): void
    {
        $money1 = new Money(100, Currency::USD);
        $money2 = new Money(100, Currency::USD);
        $money3 = new Money(100, Currency::EUR);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }
}
