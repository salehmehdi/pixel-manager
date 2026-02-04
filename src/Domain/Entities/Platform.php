<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Entities;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Platform entity.
 *
 * Represents a marketing platform configuration with its supported events.
 */
final class Platform
{
    /**
     * @param PlatformType $type
     * @param array<EventType> $supportedEvents
     * @param bool $enabled
     */
    public function __construct(
        private readonly PlatformType $type,
        private array $supportedEvents,
        private bool $enabled = true
    ) {
    }

    /**
     * Get platform type.
     *
     * @return PlatformType
     */
    public function getType(): PlatformType
    {
        return $this->type;
    }

    /**
     * Get supported event types.
     *
     * @return array<EventType>
     */
    public function getSupportedEvents(): array
    {
        return $this->supportedEvents;
    }

    /**
     * Check if platform is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if platform supports a specific event type.
     *
     * @param EventType $eventType
     * @return bool
     */
    public function supportsEventType(EventType $eventType): bool
    {
        if (!$this->enabled) {
            return false;
        }

        foreach ($this->supportedEvents as $supportedEvent) {
            if ($supportedEvent === $eventType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable the platform.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable the platform.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Add supported event type.
     *
     * @param EventType $eventType
     * @return void
     */
    public function addSupportedEvent(EventType $eventType): void
    {
        if (!$this->supportsEventType($eventType)) {
            $this->supportedEvents[] = $eventType;
        }
    }
}
