<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Services;

/**
 * Bot detection service interface.
 *
 * Detects if a request comes from a bot/crawler.
 */
interface BotDetectorInterface
{
    /**
     * Check if the given user agent indicates a bot.
     *
     * @param string|null $userAgent If null, uses current request's user agent
     * @return bool
     */
    public function isBot(?string $userAgent = null): bool;

    /**
     * Check if the current request is from a bot.
     *
     * @return bool
     */
    public function currentRequestIsBot(): bool;
}
