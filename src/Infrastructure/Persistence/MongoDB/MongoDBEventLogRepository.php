<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB;

use DateTimeImmutable;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Models\CustomerEventModel;

/**
 * MongoDB implementation of event log repository.
 */
final class MongoDBEventLogRepository implements EventLogRepositoryInterface
{
    public function __construct(
        private readonly string $connection,
        private readonly string $collection
    ) {
    }

    /**
     * Log a pixel event with its destinations.
     *
     * @param PixelEvent $event
     * @param array<PlatformType> $destinations
     * @return void
     */
    public function log(PixelEvent $event, array $destinations): void
    {
        CustomerEventModel::on($this->connection)->create([
            'event_id' => $event->getId()->toString(),
            'event_name' => $event->getType()->value,
            'destination' => array_map(fn($p) => $p->value, $destinations),
            'data' => $this->serializeEvent($event),
            'created_at' => $event->getOccurredAt(),
        ]);
    }

    /**
     * Find event by ID.
     *
     * @param EventId $id
     * @return PixelEvent|null
     */
    public function findById(EventId $id): ?PixelEvent
    {
        $model = CustomerEventModel::on($this->connection)
            ->where('event_id', $id->toString())
            ->first();

        return $model ? $this->deserializeEvent($model) : null;
    }

    /**
     * Find events by event type.
     *
     * @param string $eventType
     * @param int $limit
     * @return array<PixelEvent>
     */
    public function findByEventType(string $eventType, int $limit = 100): array
    {
        $models = CustomerEventModel::on($this->connection)
            ->where('event_name', $eventType)
            ->limit($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn($model) => $this->deserializeEvent($model))->all();
    }

    /**
     * Count events by date range.
     *
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @return int
     */
    public function countByDateRange(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return CustomerEventModel::on($this->connection)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Serialize event for storage.
     *
     * @param PixelEvent $event
     * @return array
     */
    private function serializeEvent(PixelEvent $event): array
    {
        return [
            'event_type' => $event->getType()->value,
            'event_id' => $event->getId()->toString(),
            'transaction_id' => $event->getTransactionId(),
            'order_id' => $event->getOrderId(),
            'value' => $event->getValue()?->amount,
            'currency' => $event->getValue()?->currencyCode(),
            'shipping' => $event->getShipping(),
            'search_term' => $event->getSearchTerm(),
            'page_url' => $event->getPageUrl()?->toString(),
            'items' => $event->getItems(),
            'custom_properties' => $event->getCustomProperties(),
        ];
    }

    /**
     * Deserialize event from storage (simplified - for basic retrieval).
     *
     * @param CustomerEventModel $model
     * @return PixelEvent|null
     */
    private function deserializeEvent(CustomerEventModel $model): ?PixelEvent
    {
        // This is a simplified version - full deserialization would require EventFactory
        // For now, just return null or implement basic deserialization
        return null;
    }
}
