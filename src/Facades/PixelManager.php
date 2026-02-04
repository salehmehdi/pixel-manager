<?php

namespace SalehSignal\PixelManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void track(array $eventData)
 * @method static array platforms()
 * @method static bool isPlatformEnabled(string $platform)
 * @method static array getPlatformCredentials(string $platform)
 * @method static array getEventMappings(?string $eventType = null)
 *
 * @see \SalehSignal\PixelManager\PixelManager
 */
class PixelManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pixel-manager';
    }
}
