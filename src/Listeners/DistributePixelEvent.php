<?php

namespace SalehSignal\PixelManager\Listeners;

use SalehSignal\PixelManager\Events\PixelEventCreated;
use SalehSignal\PixelManager\Jobs\BrevoEventJob;
use SalehSignal\PixelManager\Jobs\MetaEventJob;
use SalehSignal\PixelManager\Jobs\GoogleEventJob;
use SalehSignal\PixelManager\Jobs\TiktokEventJob;
use SalehSignal\PixelManager\Jobs\PinterestEventJob;
use SalehSignal\PixelManager\Jobs\SnapchatEventJob;
use SalehSignal\PixelManager\Models\Application;
use MongoDB\BSON\ObjectId;
use Illuminate\Support\Facades\Log;

class DistributePixelEvent
{
    /**
     * Handle the event.
     *
     * @param  \SalehSignal\PixelManager\Events\PixelEventCreated  $event
     * @return void
     */
    public function handle(PixelEventCreated $event)
    {
        $data = $event->data;

        // Get event type from data
        $eventType = $data['data']['event_type'] ?? null;

        if (!$eventType) {
            Log::warning('Pixel Manager: Event type not provided', ['data' => $data]);
            return;
        }

        // Get allowed destinations for this event type from config
        $allowedDestinations = config('pixel-manager.event_mappings.' . $eventType, []);

        // Get application configuration from MongoDB
        $appId = config('pixel-manager.app_id');
        $app = Application::where('app_id', $appId)
            ->where('category', 'customer_event')
            ->first();

        if (!$app) {
            Log::info('Pixel Manager: Application not found', ['app_id' => $appId]);
            return;
        }

        $appData = $app->data ?? [];

        // Determine which destinations are configured
        $destinations = $this->setDestination($appData, $allowedDestinations);

        // Generate unique event ID if not provided
        if (!isset($data['data']['event_id'])) {
            $data['data']['event_id'] = (string) new ObjectId();
        }

        // Log event if logging is enabled
        if (config('pixel-manager.logging', true)) {
            $this->logEvent($destinations, $data);
        }

        // Get queue connection from config
        $queueConnection = config('pixel-manager.queue', 'default');

        // Dispatch jobs for each destination
        foreach ($destinations as $destination) {
            $this->dispatchJob($destination, $data, $appData, $queueConnection);
        }
    }

    /**
     * Determine which destinations are configured.
     *
     * @param  array  $appData
     * @param  array  $allowedDestinations
     * @return array
     */
    protected function setDestination(array $appData, array $allowedDestinations): array
    {
        $prefixes = [];

        foreach ($appData as $key => $value) {
            if ($value !== null) {
                $prefix = explode('_', $key)[0];
                $prefixes[] = $prefix;
            }
        }

        $uniquePrefixes = array_unique($prefixes);

        return array_values(array_intersect($allowedDestinations, $uniquePrefixes));
    }

    /**
     * Log the event to MongoDB.
     *
     * @param  array  $destinations
     * @param  array  $data
     * @return void
     */
    protected function logEvent(array $destinations, array $data): void
    {
        try {
            \SalehSignal\PixelManager\Models\CustomerEvent::create([
                'event_id' => $data['data']['event_id'] ?? null,
                'event_name' => $data['data']['event_type'] ?? null,
                'destination' => $destinations,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Pixel Manager: Failed to log event', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Dispatch job for a specific destination.
     *
     * @param  string  $destination
     * @param  array  $data
     * @param  array  $appData
     * @param  string  $queueConnection
     * @return void
     */
    protected function dispatchJob(string $destination, array $data, array $appData, string $queueConnection): void
    {
        $job = match ($destination) {
            'meta' => new MetaEventJob($data, $appData),
            'brevo' => new BrevoEventJob($data, $appData),
            'google' => new GoogleEventJob($data, $appData),
            'tiktok' => new TiktokEventJob($data, $appData),
            'pinterest' => new PinterestEventJob($data, $appData),
            'snapchat' => new SnapchatEventJob($data, $appData),
            default => null,
        };

        if ($job) {
            dispatch($job)->onQueue($queueConnection);
        }
    }
}
