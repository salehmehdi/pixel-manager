<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

/**
 * User Agent string value object.
 *
 * Immutable value object representing a browser/client user agent string.
 */
final readonly class UserAgent
{
    private function __construct(
        public string $value
    ) {
    }

    /**
     * Create UserAgent from string.
     *
     * @param string $userAgent
     * @return self
     */
    public static function fromString(string $userAgent): self
    {
        return new self(trim($userAgent));
    }

    /**
     * Create UserAgent from current request.
     *
     * @return self|null
     */
    public static function fromRequest(): ?self
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return $ua ? new self($ua) : null;
    }

    /**
     * Check if user agent indicates a mobile device.
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipad', 'ipod',
            'blackberry', 'windows phone', 'opera mini'
        ];

        $lower = strtolower($this->value);

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user agent indicates a bot.
     *
     * @return bool
     */
    public function isBot(): bool
    {
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'facebookexternalhit', 'whatsapp', 'telegram'
        ];

        $lower = strtolower($this->value);

        foreach ($botPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
