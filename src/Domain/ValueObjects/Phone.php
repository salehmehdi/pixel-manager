<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidPhoneException;

/**
 * Phone number value object with country code support.
 *
 * Immutable value object representing a phone number with validation and hashing.
 */
final readonly class Phone
{
    private function __construct(
        public string $number,
        public string $countryCode
    ) {
    }

    /**
     * Create Phone from number and country code.
     *
     * @param string $number Phone number (digits only or with formatting)
     * @param string $countryCode Country code with + (e.g., +1, +90, +994)
     * @return self
     * @throws InvalidPhoneException
     */
    public static function fromParts(string $number, string $countryCode = ''): self
    {
        // Remove all non-digit characters from number
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);

        if (empty($cleanNumber)) {
            throw new InvalidPhoneException($number, 'No digits found');
        }

        // Clean country code
        $cleanCountryCode = trim($countryCode);
        if ($cleanCountryCode && !str_starts_with($cleanCountryCode, '+')) {
            $cleanCountryCode = '+' . ltrim($cleanCountryCode, '+');
        }

        // Basic validation: phone should be between 6-15 digits
        if (strlen($cleanNumber) < 6 || strlen($cleanNumber) > 15) {
            throw new InvalidPhoneException($number, 'Phone number must be between 6-15 digits');
        }

        return new self($cleanNumber, $cleanCountryCode);
    }

    /**
     * Create Phone from full international format (e.g., +905551234567).
     *
     * @param string $fullNumber
     * @return self
     * @throws InvalidPhoneException
     */
    public static function fromFullNumber(string $fullNumber): self
    {
        $clean = trim($fullNumber);

        // If starts with +, extract country code
        if (str_starts_with($clean, '+')) {
            // Extract up to 4 digits as country code
            preg_match('/^\+(\d{1,4})(\d+)$/', preg_replace('/[^0-9+]/', '', $clean), $matches);

            if (!$matches) {
                throw new InvalidPhoneException($fullNumber, 'Invalid format');
            }

            return new self($matches[2], '+' . $matches[1]);
        }

        // No country code, just the number
        return self::fromParts($clean);
    }

    /**
     * Hash the phone number using specified algorithm.
     *
     * @param HashAlgorithm $algorithm
     * @return string
     */
    public function hash(HashAlgorithm $algorithm = HashAlgorithm::SHA256): string
    {
        return $algorithm->hash($this->fullNumber());
    }

    /**
     * Get full phone number with country code.
     *
     * @return string
     */
    public function fullNumber(): string
    {
        return $this->countryCode . $this->number;
    }

    /**
     * Get formatted phone for display.
     *
     * @return string
     */
    public function formatted(): string
    {
        return $this->fullNumber();
    }

    /**
     * Get string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->fullNumber();
    }
}
