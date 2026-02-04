<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class CurrencyTest extends TestCase
{
    public function test_has_all_major_currencies(): void
    {
        $this->assertNotNull(Currency::USD);
        $this->assertNotNull(Currency::EUR);
        $this->assertNotNull(Currency::GBP);
        $this->assertNotNull(Currency::AZN);
    }

    public function test_can_get_currency_symbol(): void
    {
        $this->assertEquals('$', Currency::USD->symbol());
        $this->assertEquals('€', Currency::EUR->symbol());
        $this->assertEquals('£', Currency::GBP->symbol());
        $this->assertEquals('₼', Currency::AZN->symbol());
    }

    public function test_can_create_from_string(): void
    {
        $currency = Currency::from('USD');

        $this->assertEquals(Currency::USD, $currency);
    }

    public function test_throws_exception_for_invalid_currency(): void
    {
        $this->expectException(\ValueError::class);

        Currency::from('INVALID');
    }

    public function test_try_from_returns_null_for_invalid(): void
    {
        $currency = Currency::tryFrom('INVALID');

        $this->assertNull($currency);
    }

    public function test_can_get_all_values(): void
    {
        $values = Currency::values();

        $this->assertIsArray($values);
        $this->assertContains('USD', $values);
        $this->assertContains('EUR', $values);
        $this->assertContains('AZN', $values);
    }

    public function test_azn_currency_exists(): void
    {
        // Test that Azerbaijan Manat is supported
        $azn = Currency::AZN;

        $this->assertEquals('AZN', $azn->value);
        $this->assertEquals('₼', $azn->symbol());
    }
}
