<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidUrlException;

/**
 * URL value object with validation.
 *
 * Immutable value object representing a validated URL.
 */
final readonly class Url
{
    private function __construct(
        public string $value
    ) {
    }

    /**
     * Create Url from string.
     *
     * @param string $url
     * @return self
     * @throws InvalidUrlException
     */
    public static function fromString(string $url): self
    {
        $clean = trim($url);

        if (!filter_var($clean, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException($url);
        }

        // Ensure it's http or https
        if (!str_starts_with($clean, 'http://') && !str_starts_with($clean, 'https://')) {
            throw new InvalidUrlException($url);
        }

        return new self($clean);
    }

    /**
     * Get the domain from URL.
     *
     * @return string
     */
    public function domain(): string
    {
        return parse_url($this->value, PHP_URL_HOST) ?? '';
    }

    /**
     * Get the path from URL.
     *
     * @return string
     */
    public function path(): string
    {
        return parse_url($this->value, PHP_URL_PATH) ?? '/';
    }

    /**
     * Get the scheme (http/https).
     *
     * @return string
     */
    public function scheme(): string
    {
        return parse_url($this->value, PHP_URL_SCHEME) ?? '';
    }

    /**
     * Check if URL is HTTPS.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->scheme() === 'https';
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
