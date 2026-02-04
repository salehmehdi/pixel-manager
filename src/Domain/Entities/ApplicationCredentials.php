<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Entities;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * ApplicationCredentials aggregate root.
 *
 * Contains all platform credentials for a specific application.
 */
final class ApplicationCredentials
{
    /**
     * @param int $appId
     * @param array<PlatformType, PlatformCredentialsInterface> $credentials
     */
    public function __construct(
        private readonly int $appId,
        private array $credentials = []
    ) {
    }

    /**
     * Get application ID.
     *
     * @return int
     */
    public function getAppId(): int
    {
        return $this->appId;
    }

    /**
     * Get credentials for a specific platform.
     *
     * @param PlatformType $platform
     * @return PlatformCredentialsInterface|null
     */
    public function getCredentialsFor(PlatformType $platform): ?PlatformCredentialsInterface
    {
        return $this->credentials[$platform->value] ?? null;
    }

    /**
     * Check if credentials exist for a platform.
     *
     * @param PlatformType $platform
     * @return bool
     */
    public function hasCredentialsFor(PlatformType $platform): bool
    {
        return isset($this->credentials[$platform->value])
            && $this->credentials[$platform->value]->isValid();
    }

    /**
     * Set credentials for a platform.
     *
     * @param PlatformCredentialsInterface $credentials
     * @return void
     */
    public function setCredentials(PlatformCredentialsInterface $credentials): void
    {
        $this->credentials[$credentials->getPlatformType()->value] = $credentials;
    }

    /**
     * Get all configured platforms.
     *
     * @return array<PlatformType>
     */
    public function getConfiguredPlatforms(): array
    {
        $platforms = [];

        foreach ($this->credentials as $platformKey => $creds) {
            if ($creds->isValid()) {
                $platforms[] = PlatformType::from($platformKey);
            }
        }

        return $platforms;
    }

    /**
     * Remove credentials for a platform.
     *
     * @param PlatformType $platform
     * @return void
     */
    public function removeCredentials(PlatformType $platform): void
    {
        unset($this->credentials[$platform->value]);
    }
}
