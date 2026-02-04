<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Security;

use MehdiyevSignal\PixelManager\Domain\Services\BotDetectorInterface;

/**
 * Bot detector implementation.
 *
 * Detects bots and crawlers by user agent pattern matching.
 * This fixes the "botDetected() undefined" bug in original code.
 */
final class BotDetector implements BotDetectorInterface
{
    /**
     * Common bot patterns to detect.
     */
    private const BOT_PATTERNS = [
        'bot',
        'crawl',
        'spider',
        'slurp',
        'mediapartners',
        'facebookexternalhit',
        'whatsapp',
        'telegram',
        'linkedinbot',
        'twitterbot',
        'pinterest',
        'slackbot',
        'discordbot',
        'googlebot',
        'bingbot',
        'yandexbot',
        'baiduspider',
        'duckduckbot',
        'applebot',
    ];

    /**
     * Check if the given user agent indicates a bot.
     *
     * @param string|null $userAgent If null, uses current request's user agent
     * @return bool
     */
    public function isBot(?string $userAgent = null): bool
    {
        $ua = $userAgent ?? $this->getCurrentUserAgent();

        if (empty($ua)) {
            return false;
        }

        $ua = strtolower($ua);

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current request is from a bot.
     *
     * @return bool
     */
    public function currentRequestIsBot(): bool
    {
        return $this->isBot($this->getCurrentUserAgent());
    }

    /**
     * Get current request's user agent.
     *
     * @return string|null
     */
    private function getCurrentUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
