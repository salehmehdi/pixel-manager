<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MehdiyevSignal\PixelManager\Application\Services\EventFactory;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\BrevoCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\GoogleCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\MetaCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\PinterestCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\SnapchatCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\TikTokCredentials;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Factories\PlatformAdapterFactory;
use Psr\Log\LoggerInterface;

/**
 * Unified job for sending pixel events to any platform.
 *
 * This replaces the 6 platform-specific jobs with a single generic job.
 */
final class SendPixelEventJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Timeout for the job.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Maximum number of exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 2;

    public function __construct(
        private readonly array $eventData,
        private readonly string $platformType,
        private readonly array $credentials,
        private readonly int $appId
    ) {
    }

    /**
     * Execute the job.
     *
     * @param PlatformAdapterFactory $factory
     * @param EventFactory $eventFactory
     * @param LoggerInterface $logger
     * @return void
     */
    public function handle(
        PlatformAdapterFactory $factory,
        EventFactory $eventFactory,
        LoggerInterface $logger
    ): void {
        try {
            // Get platform type
            $platform = PlatformType::from($this->platformType);

            // Get platform adapter (with decorators applied)
            $adapter = $factory->create($platform);

            // Recreate domain event
            $event = $eventFactory->createFromArray($this->eventData);

            // Recreate credentials
            $creds = $this->createCredentials($platform, $this->credentials);

            // Send event
            $response = $adapter->sendEvent($event, $creds);

            if ($response->isFailure()) {
                $logger->error('Pixel Manager: Platform event send failed', [
                    'platform' => $this->platformType,
                    'event_id' => $this->eventData['event_id'] ?? null,
                    'error' => $response->errorMessage,
                ]);

                // Throw exception to trigger retry
                throw new \RuntimeException(
                    "Failed to send event to {$this->platformType}: {$response->errorMessage}"
                );
            }

            $logger->debug('Pixel Manager: Event sent successfully', [
                'platform' => $this->platformType,
                'event_id' => $this->eventData['event_id'] ?? null,
            ]);

        } catch (\Exception $e) {
            $logger->error('Pixel Manager: Job execution failed', [
                'platform' => $this->platformType,
                'app_id' => $this->appId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Pixel Manager: Job permanently failed', [
            'platform' => $this->platformType,
            'app_id' => $this->appId,
            'event_id' => $this->eventData['event_id'] ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Create credentials object from array.
     *
     * @param PlatformType $platform
     * @param array $data
     * @return mixed
     */
    private function createCredentials(PlatformType $platform, array $data): mixed
    {
        return match ($platform) {
            PlatformType::META => MetaCredentials::fromArray($data),
            PlatformType::GOOGLE => GoogleCredentials::fromArray($data),
            PlatformType::TIKTOK => TikTokCredentials::fromArray($data),
            PlatformType::PINTEREST => PinterestCredentials::fromArray($data),
            PlatformType::SNAPCHAT => SnapchatCredentials::fromArray($data),
            PlatformType::BREVO => BrevoCredentials::fromArray($data),
        };
    }
}
