<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\Services;

use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Platform selector service.
 *
 * Determines which platforms should receive an event.
 */
final class PlatformSelector
{
    public function __construct(
        private readonly ConfigService $config
    ) {
    }

    /**
     * Select platforms that should receive this event.
     *
     * @param PixelEvent $event
     * @param ApplicationCredentials $credentials
     * @return array<PlatformType>
     */
    public function selectPlatforms(PixelEvent $event, ApplicationCredentials $credentials): array
    {
        // Get allowed platforms from event mappings
        $allowedPlatforms = $this->config->getEventMappings($event->getType());

        // Get configured platforms from credentials
        $configuredPlatforms = $credentials->getConfiguredPlatforms();

        // Intersect: only platforms that are both allowed and configured
        $selected = [];

        foreach ($allowedPlatforms as $platform) {
            if ($credentials->hasCredentialsFor($platform)) {
                $selected[] = $platform;
            }
        }

        return $selected;
    }

    /**
     * Check if a platform should receive this event.
     *
     * @param PixelEvent $event
     * @param PlatformType $platform
     * @param ApplicationCredentials $credentials
     * @return bool
     */
    public function shouldSendTo(PixelEvent $event, PlatformType $platform, ApplicationCredentials $credentials): bool
    {
        $selected = $this->selectPlatforms($event, $credentials);

        foreach ($selected as $selectedPlatform) {
            if ($selectedPlatform === $platform) {
                return true;
            }
        }

        return false;
    }
}
