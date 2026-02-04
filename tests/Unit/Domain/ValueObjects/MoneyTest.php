<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidMoneyException;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class MoneyTest extends TestCase
{
    public function test_can_create_money_with_amount_and_currency(): void
    {
        $money = Money::from(99.99, Currency::USD);

        $this->assertEquals(99.99, $money->amount);
        $this->assertEquals(Currency::USD, $money->currency);
    }

    public function test_can_create_money_with_string_currency(): void
    {
        $money = Money::from(99.99, 'USD');

        $this->assertEquals(99.99, $money->amount);
        $this->assertEquals(Currency::USD, $money->currency);
    }

    public function test_can_create_zero_money(): void
    {
        $money = Money::zero(Currency::USD);

        $this->assertEquals(0.0, $money->amount);
        $this->assertTrue($money->isZero());
    }

    public function test_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        Money::from(-10.00, Currency::USD);
    }

    public function test_is_zero_returns_true_for_zero_amount(): void
    {
        $money = Money::from(0.0, Currency::USD);

        $this->assertTrue($money->isZero());
    }

    public function test_is_zero_returns_false_for_non_zero_amount(): void
    {
        $money = Money::from(10.00, Currency::USD);

        $this->assertFalse($money->isZero());
    }

    public function test_is_positive_returns_true_for_positive_amount(): void
    {
        $money = Money::from(10.00, Currency::USD);

        $this->assertTrue($money->isPositive());
    }

    public function test_is_positive_returns_false_for_zero_amount(): void
    {
        $money = Money::from(0.0, Currency::USD);

        $this->assertFalse($money->isPositive());
    }

    public function test_can_format_money_with_currency_symbol(): void
    {
        $money = Money::from(99.99, Currency::USD);

        $formatted = $money->formatted();

        $this->assertStringContainsString('99.99', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    public function test_can_get_currency_code(): void
    {
        $money = Money::from(99.99, Currency::USD);

        $this->assertEquals('USD', $money->currencyCode());
    }

    public function test_supports_azerbaijani_manat_currency(): void
    {
        $money = Money::from(169.98, Currency::AZN);

        $this->assertEquals(169.98, $money->amount);
        $this->assertEquals(Currency::AZN, $money->currency);
        $this->assertEquals('AZN', $money->currencyCode());
    }

    public function test_formats_azerbaijani_manat_correctly(): void
    {
        $money = Money::from(169.98, Currency::AZN);

        $formatted = $money->formatted();

        $this->assertStringContainsString('169.98', $formatted);
    }

    public function test_different_currencies_create_different_money_objects(): void
    {
        $usd = Money::from(100.00, Currency::USD);
        $azn = Money::from(100.00, Currency::AZN);

        $this->assertNotEquals($usd->currency, $azn->currency);
    }

    public function test_handles_decimal_precision(): void
    {
        $money = Money::from(99.995, Currency::USD);

        // Should preserve decimal precision
        $this->assertEquals(99.995, $money->amount);
    }
}
