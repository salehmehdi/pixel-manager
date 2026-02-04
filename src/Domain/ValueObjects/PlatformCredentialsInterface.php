<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

/**
 * Interface for platform-specific credentials.
 *
 * Each platform implements this with its specific required fields.
 */
interface PlatformCredentialsInterface
{
    /**
     * Get the platform type these credentials belong to.
     *
     * @return PlatformType
     */
    public function getPlatformType(): PlatformType;

    /**
     * Check if credentials are valid (non-empty required fields).
     *
     * @return bool
     */
    public function isValid(): bool;
}
