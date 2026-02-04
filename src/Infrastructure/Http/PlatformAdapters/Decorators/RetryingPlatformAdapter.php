<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Retrying platform adapter decorator.
 *
 * Implements exponential backoff retry logic.
 */
final class RetryingPlatformAdapter implements PlatformAdapterInterface
{
    private const INITIAL_DELAY_MS = 100;

    public function __construct(
        private readonly PlatformAdapterInterface $inner,
        private readonly int $maxAttempts = 3
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
        $attempt = 1;
        $lastResponse = null;

        while ($attempt <= $this->maxAttempts) {
            $lastResponse = $this->inner->sendEvent($event, $credentials);

            if ($lastResponse->isSuccess()) {
                return $lastResponse;
            }

            // Don't retry on last attempt
            if ($attempt < $this->maxAttempts) {
                $this->exponentialBackoff($attempt);
            }

            $attempt++;
        }

        return $lastResponse;
    }

    /**
     * Exponential backoff delay.
     */
    private function exponentialBackoff(int $attempt): void
    {
        $delayMs = self::INITIAL_DELAY_MS * (2 ** ($attempt - 1));
        usleep($delayMs * 1000);
    }
}
