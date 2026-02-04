<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use Psr\Log\LoggerInterface;

/**
 * Logging platform adapter decorator.
 *
 * Logs all platform adapter calls with timing information.
 */
final class LoggingPlatformAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly PlatformAdapterInterface $inner,
        private readonly LoggerInterface $logger
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
        $startTime = microtime(true);

        $response = $this->inner->sendEvent($event, $credentials);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($response->isSuccess()) {
            $this->logger->debug('Pixel Manager: Event sent successfully', [
                'platform' => $this->inner->getPlatformType()->value,
                'event_id' => $event->getId()->toString(),
                'event_type' => $event->getType()->value,
                'duration_ms' => $duration,
            ]);
        } else {
            $this->logger->warning('Pixel Manager: Event send failed', [
                'platform' => $this->inner->getPlatformType()->value,
                'event_id' => $event->getId()->toString(),
                'event_type' => $event->getType()->value,
                'error' => $response->errorMessage,
                'duration_ms' => $duration,
            ]);
        }

        return $response;
    }
}
