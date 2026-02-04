<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Exceptions;

/**
 * Thrown when an invalid phone number is provided.
 */
final class InvalidPhoneException extends DomainException
{
    public function __construct(string $phone, string $reason = 'Invalid format')
    {
        parent::__construct("Invalid phone number '{$phone}': {$reason}");
    }
}
