<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Circuit breaker platform adapter decorator.
 *
 * Prevents cascading failures by opening circuit after threshold.
 */
final class CircuitBreakerPlatformAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly PlatformAdapterInterface $inner,
        private readonly CacheRepository $cache,
        private readonly int $failureThreshold = 5,
        private readonly int $timeoutSeconds = 60
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return $this->inner->getPlatformType();
    }

    public function supports(EventType $eventType): bool
    {
        return $this->inner->supports($eventType);
    }

    public function mapEventName(EventType $type): ?string
    {
        return $this->inner->mapEventName($type);
    }

    public function sendEvent(PixelEvent $event, PlatformCredentialsInterface $credentials): PlatformResponse
    {
        $circuitKey = $this->getCircuitKey();

        // Check if circuit is open
        if ($this->isCircuitOpen($circuitKey)) {
            return PlatformResponse::failure(
                "Circuit breaker is open for {$this->inner->getPlatformType()->value}"
            );
        }

        // Try to send
        $response = $this->inner->sendEvent($event, $credentials);

        if ($response->isSuccess()) {
            $this->recordSuccess($circuitKey);
        } else {
            $this->recordFailure($circuitKey);
        }

        return $response;
    }

    private function getCircuitKey(): string
    {
        return "circuit_breaker:{$this->inner->getPlatformType()->value}";
    }

    private function isCircuitOpen(string $key): bool
    {
        $failures = $this->cache->get("{$key}:failures", 0);
        return $failures >= $this->failureThreshold;
    }

    private function recordFailure(string $key): void
    {
        $failures = (int) $this->cache->get("{$key}:failures", 0);
        $this->cache->put("{$key}:failures", $failures + 1, $this->timeoutSeconds);
    }

    private function recordSuccess(string $key): void
    {
        $this->cache->forget("{$key}:failures");
    }
}
