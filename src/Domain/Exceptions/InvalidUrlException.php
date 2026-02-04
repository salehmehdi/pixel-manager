<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Exceptions;

/**
 * Thrown when an invalid URL is provided.
 */
final class InvalidUrlException extends DomainException
{
    public function __construct(string $url)
    {
        parent::__construct("Invalid URL: {$url}");
    }
}
