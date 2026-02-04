<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidMoneyException;

/**
 * Money value object representing an amount with currency.
 *
 * Immutable value object for monetary values with validation.
 */
final readonly class Money
{
    private function __construct(
        public float $amount,
        public Currency $currency
    ) {
    }

    /**
     * Create Money from amount and currency.
     *
     * @param float $amount
     * @param Currency|string $currency
     * @return self
     * @throws InvalidMoneyException
     */
    public static function from(float $amount, Currency|string $currency): self
    {
        if ($amount < 0) {
            throw new InvalidMoneyException("Amount cannot be negative: {$amount}");
        }

        $curr = is_string($currency) ? Currency::fromString($currency) : $currency;

        return new self($amount, $curr);
    }

    /**
     * Create zero money.
     *
     * @param Currency|string $currency
     * @return self
     */
    public static function zero(Currency|string $currency): self
    {
        $curr = is_string($currency) ? Currency::fromString($currency) : $currency;
        return new self(0.0, $curr);
    }

    /**
     * Check if amount is zero.
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return abs($this->amount) < 0.01;
    }

    /**
     * Check if amount is positive.
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Format as string with currency symbol.
     *
     * @return string
     */
    public function formatted(): string
    {
        return $this->currency->symbol() . number_format($this->amount, 2);
    }

    /**
     * Get currency code.
     *
     * @return string
     */
    public function currencyCode(): string
    {
        return $this->currency->value;
    }
}
