<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Repositories;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Repository interface for event logging.
 *
 * Abstracts data access for event log storage (MongoDB, etc.).
 */
interface EventLogRepositoryInterface
{
    /**
     * Log a pixel event with its destinations.
     *
     * @param PixelEvent $event
     * @param array<PlatformType> $destinations
     * @return void
     */
    public function log(PixelEvent $event, array $destinations): void;

    /**
     * Find event by ID.
     *
     * @param EventId $id
     * @return PixelEvent|null
     */
    public function findById(EventId $id): ?PixelEvent;

    /**
     * Find events by event type.
     *
     * @param string $eventType
     * @param int $limit
     * @return array<PixelEvent>
     */
    public function findByEventType(string $eventType, int $limit = 100): array;

    /**
     * Count events by date range.
     *
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     * @return int
     */
    public function countByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): int;
}
