<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Exceptions;

/**
 * Thrown when an invalid email address is provided.
 */
final class InvalidEmailException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("Invalid email address: {$email}");
    }
}
