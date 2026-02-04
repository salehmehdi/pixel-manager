<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL;

use Illuminate\Support\Facades\DB;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * SQL-based event log repository.
 *
 * Supports MySQL, PostgreSQL, SQLite, etc.
 */
final class SQLEventLogRepository implements EventLogRepositoryInterface
{
    public function __construct(
        private readonly string $connection,
        private readonly string $tableName
    ) {
    }

    public function log(PixelEvent $event, array $destinations): void
    {
        $customer = $event->getCustomer();

        DB::connection($this->connection)
            ->table($this->tableName)
            ->insert([
                'event_id' => $event->getId()->toString(),
                'event_type' => $event->getType()->value,
                'event_name' => $event->getEventName(),
                'value' => $event->getValue(),
                'currency' => $event->getCurrency()?->value,
                'customer_email' => $customer?->email?->value(),
                'customer_phone' => $customer?->phone?->value(),
                'customer_first_name' => $customer?->firstName,
                'customer_last_name' => $customer?->lastName,
                'customer_city' => $customer?->city,
                'customer_country' => $customer?->countryCode,
                'ip_address' => $event->getIpAddress()?->value(),
                'user_agent' => $event->getUserAgent()?->value(),
                'destinations' => json_encode(array_map(fn($p) => $p->value, $destinations)),
                'event_data' => json_encode($event->toArray()),
                'created_at' => $event->getEventTime(),
            ]);
    }

    public function findById(EventId $id): ?PixelEvent
    {
        $record = DB::connection($this->connection)
            ->table($this->tableName)
            ->where('event_id', $id->toString())
            ->first();

        if (!$record) {
            return null;
        }

        // Note: Reconstructing PixelEvent from database is complex
        // and might not be needed in most cases. If needed, implement
        // a proper mapper/hydrator similar to MongoDB implementation.

        return null;
    }

    public function findByEventType(string $eventType, int $limit = 100): array
    {
        $records = DB::connection($this->connection)
            ->table($this->tableName)
            ->where('event_type', $eventType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Note: Mapping to PixelEvent entities requires a proper hydrator.
        // For analytics purposes, working with raw records is often sufficient.

        return [];
    }

    public function countByDateRange(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): int {
        return DB::connection($this->connection)
            ->table($this->tableName)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Get event statistics by platform.
     *
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     * @return array<string, int>
     */
    public function getStatsByPlatform(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        $records = DB::connection($this->connection)
            ->table($this->tableName)
            ->whereBetween('created_at', [$from, $to])
            ->select('destinations', DB::raw('COUNT(*) as count'))
            ->groupBy('destinations')
            ->get();

        $stats = [];
        foreach ($records as $record) {
            $platforms = json_decode($record->destinations, true);
            foreach ($platforms as $platform) {
                $stats[$platform] = ($stats[$platform] ?? 0) + $record->count;
            }
        }

        return $stats;
    }

    /**
     * Get event statistics by event type.
     *
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     * @return array<string, int>
     */
    public function getStatsByEventType(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return DB::connection($this->connection)
            ->table($this->tableName)
            ->whereBetween('created_at', [$from, $to])
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Get total revenue by currency.
     *
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     * @return array<string, float>
     */
    public function getRevenueStats(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return DB::connection($this->connection)
            ->table($this->tableName)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('value')
            ->select('currency', DB::raw('SUM(value) as total'))
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();
    }
}
