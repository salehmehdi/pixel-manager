<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\UseCases\TrackPixelEvent;

use MehdiyevSignal\PixelManager\Application\Services\ConfigService;
use MehdiyevSignal\PixelManager\Application\Services\EventDistributor;
use MehdiyevSignal\PixelManager\Application\Services\EventFactory;
use MehdiyevSignal\PixelManager\Domain\Exceptions\DomainException;
use MehdiyevSignal\PixelManager\Domain\Services\BotDetectorInterface;

/**
 * Handler for TrackPixelEvent use case.
 *
 * This is the main entry point for tracking events.
 */
final class TrackPixelEventHandler
{
    public function __construct(
        private readonly EventFactory $eventFactory,
        private readonly EventDistributor $eventDistributor,
        private readonly BotDetectorInterface $botDetector,
        private readonly ConfigService $config
    ) {
    }

    /**
     * Handle the command.
     *
     * @param TrackPixelEventCommand $command
     * @return void
     */
    public function handle(TrackPixelEventCommand $command): void
    {
        // Bot detection
        if ($this->config->isBotDetectionEnabled() && $this->botDetector->currentRequestIsBot()) {
            \Log::debug('Pixel Manager: Bot detected, skipping event tracking');
            return;
        }

        try {
            // Create domain event from raw data
            $event = $this->eventFactory->createFromArray($command->eventData);

            // Distribute to platforms
            $this->eventDistributor->distribute($event, $command->applicationId);

        } catch (DomainException $e) {
            \Log::error('Pixel Manager: Domain validation error', [
                'error' => $e->getMessage(),
                'data' => $command->eventData,
            ]);
        } catch (\Exception $e) {
            \Log::error('Pixel Manager: Unexpected error tracking event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
