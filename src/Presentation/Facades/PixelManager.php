<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Presentation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * PixelManager Laravel Facade.
 *
 * @method static void track(array $eventData)
 * @method static array platforms()
 * @method static bool isPlatformEnabled(string $platform)
 * @method static array|null getPlatformCredentials(string $platform)
 * @method static array getEventMappings(?string $eventType = null)
 *
 * @see \MehdiyevSignal\PixelManager\Presentation\Facades\PixelManagerFacadeImpl
 */
class PixelManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pixel-manager';
    }
}
