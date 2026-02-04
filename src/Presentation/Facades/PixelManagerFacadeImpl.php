<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Presentation\Facades;

use MehdiyevSignal\PixelManager\Application\Services\ConfigService;
use MehdiyevSignal\PixelManager\Application\UseCases\TrackPixelEvent\TrackPixelEventCommand;
use MehdiyevSignal\PixelManager\Application\UseCases\TrackPixelEvent\TrackPixelEventHandler;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;

/**
 * Pixel Manager facade implementation.
 *
 * Public API for the package.
 */
final class PixelManagerFacadeImpl
{
    public function __construct(
        private readonly TrackPixelEventHandler $trackHandler,
        private readonly ConfigService $config,
        private readonly CredentialsRepositoryInterface $credentialsRepo
    ) {
    }

    /**
     * Track a pixel event.
     *
     * @param array $eventData
     * @return void
     */
    public function track(array $eventData): void
    {
        $command = new TrackPixelEventCommand(
            eventData: $eventData['data'] ?? $eventData,
            applicationId: $this->config->getAppId()
        );

        $this->trackHandler->handle($command);
    }

    /**
     * Get all supported platforms.
     *
     * @return array<string>
     */
    public function platforms(): array
    {
        return [
            'meta',
            'google',
            'tiktok',
            'pinterest',
            'snapchat',
            'brevo',
        ];
    }

    /**
     * Check if a platform is enabled (has valid credentials).
     *
     * @param string $platform
     * @return bool
     */
    public function isPlatformEnabled(string $platform): bool
    {
        $credentials = $this->credentialsRepo->findByApplicationId(
            $this->config->getAppId()
        );

        if (!$credentials) {
            return false;
        }

        try {
            $platformType = \MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType::from($platform);
            return $credentials->hasCredentialsFor($platformType);
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Get platform credentials (for debugging - be careful with sensitive data).
     *
     * @param string $platform
     * @return array|null
     */
    public function getPlatformCredentials(string $platform): ?array
    {
        $credentials = $this->credentialsRepo->findByApplicationId(
            $this->config->getAppId()
        );

        if (!$credentials) {
            return null;
        }

        try {
            $platformType = \MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType::from($platform);
            $platformCreds = $credentials->getCredentialsFor($platformType);

            return $platformCreds ? ['configured' => true] : null;
        } catch (\ValueError $e) {
            return null;
        }
    }

    /**
     * Get event mappings for a specific event type.
     *
     * @param string|null $eventType
     * @return array
     */
    public function getEventMappings(?string $eventType = null): array
    {
        if ($eventType === null) {
            return $this->config->getAllEventMappings();
        }

        try {
            $type = \MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType::from($eventType);
            $platforms = $this->config->getEventMappings($type);

            return array_map(fn($p) => $p->value, $platforms);
        } catch (\ValueError $e) {
            return [];
        }
    }
}
