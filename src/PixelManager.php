<?php

namespace SalehSignal\PixelManager;

use SalehSignal\PixelManager\Events\PixelEventCreated;
use SalehSignal\PixelManager\Models\Application;

class PixelManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Create a new PixelManager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Track a pixel event by firing the PixelEventCreated event.
     *
     * @param  array  $eventData
     * @return void
     */
    public function track(array $eventData): void
    {
        event(new PixelEventCreated($eventData));
    }

    /**
     * Get all supported platforms.
     *
     * @return array
     */
    public function platforms(): array
    {
        return array_keys(config('pixel-manager.platforms', []));
    }

    /**
     * Check if a specific platform is enabled.
     *
     * @param  string  $platform
     * @return bool
     */
    public function isPlatformEnabled(string $platform): bool
    {
        $application = $this->getApplication();

        // Check if any of the platform's required fields are present
        return isset($application["{$platform}_pixel_id"])
            || isset($application["{$platform}_api_key"])
            || isset($application["{$platform}_measurement_id"])
            || isset($application["{$platform}_pixel_code"])
            || isset($application["{$platform}_account_id"])
            || isset($application["{$platform}_access_token"]);
    }

    /**
     * Get the application configuration from MongoDB.
     *
     * @return array
     */
    protected function getApplication(): array
    {
        $appId = config('pixel-manager.app_id');

        $application = Application::where('app_id', $appId)
            ->where('category', 'customer_event')
            ->first();

        return $application?->data ?? [];
    }

    /**
     * Get application data for a specific platform.
     *
     * @param  string  $platform
     * @return array
     */
    public function getPlatformCredentials(string $platform): array
    {
        $application = $this->getApplication();
        $credentials = [];

        foreach ($application as $key => $value) {
            if (str_starts_with($key, $platform . '_')) {
                $credentials[$key] = $value;
            }
        }

        return $credentials;
    }

    /**
     * Get the event mappings configuration.
     *
     * @param  string|null  $eventType
     * @return array
     */
    public function getEventMappings(?string $eventType = null): array
    {
        $mappings = config('pixel-manager.event_mappings', []);

        if ($eventType !== null) {
            return $mappings[$eventType] ?? [];
        }

        return $mappings;
    }
}
