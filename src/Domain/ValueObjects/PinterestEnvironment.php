<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

/**
 * Pinterest API environment modes.
 *
 * Pinterest supports both sandbox (for testing) and production environments.
 */
enum PinterestEnvironment: string
{
    case SANDBOX = 'sandbox';
    case PRODUCTION = 'production';

    /**
     * Check if this is a production environment.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this === self::PRODUCTION;
    }

    /**
     * Check if this is a sandbox environment.
     *
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this === self::SANDBOX;
    }

    /**
     * Get the base URL for this environment.
     *
     * @return string
     */
    public function baseUrl(): string
    {
        return match ($this) {
            self::SANDBOX => 'https://api-sandbox.pinterest.com',
            self::PRODUCTION => 'https://api.pinterest.com',
        };
    }
}
