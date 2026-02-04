<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Exceptions;

use Exception;

/**
 * Base exception for all domain-layer exceptions.
 *
 * This exception should be extended by all domain-specific exceptions
 * to provide a clear hierarchy and type-safe catch handling.
 */
abstract class DomainException extends Exception
{
}
