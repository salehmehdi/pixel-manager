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
 * Rate limiting platform adapter decorator.
 *
 * Limits requests per minute to prevent API rate limit violations.
 */
final class RateLimitingPlatformAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly PlatformAdapterInterface $inner,
        private readonly CacheRepository $cache,
        private readonly int $maxRequestsPerMinute = 100
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
        $rateLimitKey = $this->getRateLimitKey();

        $currentCount = (int) $this->cache->get($rateLimitKey, 0);

        if ($currentCount >= $this->maxRequestsPerMinute) {
            return PlatformResponse::failure(
                "Rate limit exceeded for {$this->inner->getPlatformType()->value} ({$this->maxRequestsPerMinute}/min)"
            );
        }

        // Increment counter
        $this->cache->put($rateLimitKey, $currentCount + 1, 60);

        return $this->inner->sendEvent($event, $credentials);
    }

    private function getRateLimitKey(): string
    {
        return "rate_limit:{$this->inner->getPlatformType()->value}";
    }
}
