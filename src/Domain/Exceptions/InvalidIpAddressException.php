<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Exceptions;

/**
 * Thrown when an invalid IP address is provided.
 */
final class InvalidIpAddressException extends DomainException
{
    public function __construct(string $ip)
    {
        parent::__construct("Invalid IP address: {$ip}");
    }
}
