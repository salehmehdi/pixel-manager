<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidIpAddressException;

/**
 * IP Address value object supporting both IPv4 and IPv6.
 *
 * Immutable value object representing a validated IP address.
 */
final readonly class IpAddress
{
    private function __construct(
        public string $value,
        public bool $isIpv6
    ) {
    }

    /**
     * Create IpAddress from string.
     *
     * @param string $ip
     * @return self
     * @throws InvalidIpAddressException
     */
    public static function fromString(string $ip): self
    {
        $clean = trim($ip);

        // Check IPv4
        if (filter_var($clean, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return new self($clean, false);
        }

        // Check IPv6
        if (filter_var($clean, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return new self($clean, true);
        }

        throw new InvalidIpAddressException($ip);
    }

    /**
     * Check if this is an IPv4 address.
     *
     * @return bool
     */
    public function isIpv4(): bool
    {
        return !$this->isIpv6;
    }

    /**
     * Check if this is a private IP address.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return !filter_var(
            $this->value,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Get string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
