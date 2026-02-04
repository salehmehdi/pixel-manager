<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Services;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Platform adapter interface (Strategy Pattern).
 *
 * Each platform implements this interface to handle event sending.
 */
interface PlatformAdapterInterface
{
    /**
     * Get the platform type this adapter handles.
     *
     * @return PlatformType
     */
    public function getPlatformType(): PlatformType;

    /**
     * Check if this platform supports a specific event type.
     *
     * @param EventType $eventType
     * @return bool
     */
    public function supports(EventType $eventType): bool;

    /**
     * Send event to platform.
     *
     * @param PixelEvent $event
     * @param PlatformCredentialsInterface $credentials
     * @return PlatformResponse
     */
    public function sendEvent(PixelEvent $event, PlatformCredentialsInterface $credentials): PlatformResponse;

    /**
     * Map internal event type to platform-specific event name.
     *
     * @param EventType $type
     * @return string|null Returns null if event type is not supported
     */
    public function mapEventName(EventType $type): ?string;
}
