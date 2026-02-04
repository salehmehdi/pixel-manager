<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

/**
 * Supported marketing platforms.
 *
 * Each platform has its own API implementation and event format.
 */
enum PlatformType: string
{
    case META = 'meta';
    case GOOGLE = 'google';
    case TIKTOK = 'tiktok';
    case PINTEREST = 'pinterest';
    case SNAPCHAT = 'snapchat';
    case BREVO = 'brevo';

    /**
     * Get all platform types as an array of strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }

    /**
     * Try to create from string value, returns null if invalid.
     *
     * @param string $value
     * @return self|null
     */
    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Check if the given string is a valid platform type.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Get platform display name.
     *
     * @return string
     */
    public function displayName(): string
    {
        return match ($this) {
            self::META => 'Meta Pixel (Facebook)',
            self::GOOGLE => 'Google Analytics 4',
            self::TIKTOK => 'TikTok Pixel',
            self::PINTEREST => 'Pinterest Tag',
            self::SNAPCHAT => 'Snapchat Pixel',
            self::BREVO => 'Brevo (Sendinblue)',
        };
    }
}
