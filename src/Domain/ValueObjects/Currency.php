<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidCurrencyException;

/**
 * ISO 4217 Currency codes.
 *
 * Supports major currencies used in e-commerce tracking.
 */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case TRY = 'TRY';
    case JPY = 'JPY';
    case CNY = 'CNY';
    case CAD = 'CAD';
    case AUD = 'AUD';
    case CHF = 'CHF';
    case SEK = 'SEK';
    case NOK = 'NOK';
    case DKK = 'DKK';
    case INR = 'INR';
    case BRL = 'BRL';
    case RUB = 'RUB';
    case MXN = 'MXN';
    case KRW = 'KRW';
    case SGD = 'SGD';
    case HKD = 'HKD';
    case NZD = 'NZD';
    case ZAR = 'ZAR';
    case AED = 'AED';
    case SAR = 'SAR';
    case PLN = 'PLN';
    case THB = 'THB';
    case MYR = 'MYR';
    case IDR = 'IDR';
    case PHP = 'PHP';
    case CZK = 'CZK';
    case HUF = 'HUF';
    case ILS = 'ILS';

    /**
     * Create Currency from string, throws exception if invalid.
     *
     * @param string $code
     * @return self
     * @throws InvalidCurrencyException
     */
    public static function fromString(string $code): self
    {
        $currency = self::tryFrom(strtoupper(trim($code)));

        if ($currency === null) {
            throw new InvalidCurrencyException("Invalid currency code: {$code}");
        }

        return $currency;
    }

    /**
     * Get currency symbol.
     *
     * @return string
     */
    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::TRY => '₺',
            self::JPY, self::CNY => '¥',
            self::INR => '₹',
            self::RUB => '₽',
            self::KRW => '₩',
            self::BRL => 'R$',
            self::AUD, self::CAD, self::NZD, self::HKD, self::SGD, self::MXN => '$',
            default => $this->value,
        };
    }
}
