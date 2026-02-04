<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\UseCases\TrackPixelEvent;

/**
 * Command to track a pixel event.
 */
final readonly class TrackPixelEventCommand
{
    public function __construct(
        public array $eventData,
        public int $applicationId
    ) {
    }
}
