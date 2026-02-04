<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\Services;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use MehdiyevSignal\PixelManager\Infrastructure\Queue\SendPixelEventJob;

/**
 * Event distributor service.
 *
 * Distributes events to appropriate platforms via queue.
 */
final class EventDistributor
{
    public function __construct(
        private readonly PlatformSelector $selector,
        private readonly CredentialsRepositoryInterface $credentialsRepo,
        private readonly EventLogRepositoryInterface $eventLogRepo,
        private readonly QueueFactory $queue,
        private readonly ConfigService $config
    ) {
    }

    /**
     * Distribute event to appropriate platforms.
     *
     * @param PixelEvent $event
     * @param int $appId
     * @return void
     */
    public function distribute(PixelEvent $event, int $appId): void
    {
        // Get credentials
        $credentials = $this->credentialsRepo->findByApplicationId($appId);

        if (!$credentials) {
            \Log::warning('Pixel Manager: No credentials found for app', ['app_id' => $appId]);
            return;
        }

        // Select platforms
        $platforms = $this->selector->selectPlatforms($event, $credentials);

        if (empty($platforms)) {
            \Log::info('Pixel Manager: No platforms selected for event', [
                'event_id' => $event->getId()->toString(),
                'event_type' => $event->getType()->value,
            ]);
            return;
        }

        // Log event if enabled
        if ($this->config->isLoggingEnabled()) {
            $this->eventLogRepo->log($event, $platforms);
        }

        // Dispatch jobs for each platform
        $queueName = $this->config->getQueue();

        foreach ($platforms as $platform) {
            $platformCreds = $credentials->getCredentialsFor($platform);

            if ($platformCreds && $platformCreds->isValid()) {
                // Create job
                $job = new SendPixelEventJob(
                    eventData: $this->serializeEvent($event),
                    platformType: $platform->value,
                    credentials: $this->serializeCredentials($platformCreds),
                    appId: $appId
                );

                // Dispatch to queue
                $this->queue->push($job, '', $queueName);
            }
        }
    }

    /**
     * Serialize event to array for queue.
     *
     * @param PixelEvent $event
     * @return array
     */
    private function serializeEvent(PixelEvent $event): array
    {
        return [
            'event_id' => $event->getId()->toString(),
            'event_type' => $event->getType()->value,
            'transaction_id' => $event->getTransactionId(),
            'order_id' => $event->getOrderId(),
            'value' => $event->getValue()?->amount,
            'currency' => $event->getValue()?->currencyCode(),
            'shipping' => $event->getShipping(),
            'search_term' => $event->getSearchTerm(),
            'page_url' => $event->getPageUrl()?->toString(),
            'items' => $event->getItems(),
            'custom_properties' => $event->getCustomProperties(),
            'customer' => $this->serializeCustomer($event),
        ];
    }

    /**
     * Serialize customer data.
     *
     * @param PixelEvent $event
     * @return array
     */
    private function serializeCustomer(PixelEvent $event): array
    {
        $customer = $event->getCustomer();

        return [
            'email' => $customer->email?->toString(),
            'phone' => $customer->phone?->toString(),
            'phone_code' => $customer->phone?->countryCode,
            'ip_address' => $customer->ipAddress?->toString(),
            'user_agent' => $customer->userAgent?->toString(),
            'external_id' => $customer->externalId,
            'first_name' => $customer->firstName,
            'last_name' => $customer->lastName,
            'gender' => $customer->gender,
            'date_of_birth' => $customer->dateOfBirth?->format('Y-m-d'),
            'city' => $customer->city,
            'state' => $customer->state,
            'country_code' => $customer->countryCode,
            'zip_code' => $customer->zipCode,
            'fbc' => $customer->fbc,
            'fbp' => $customer->fbp,
            'custom' => $customer->customProperties,
        ];
    }

    /**
     * Serialize credentials to array.
     *
     * @param mixed $credentials
     * @return array
     */
    private function serializeCredentials(mixed $credentials): array
    {
        // Use reflection to get public properties
        $reflection = new \ReflectionClass($credentials);
        $data = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $data[$name] = $property->getValue($credentials);
        }

        return $data;
    }
}
