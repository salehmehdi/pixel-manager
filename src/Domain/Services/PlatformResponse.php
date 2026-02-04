<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Services;

use DateTimeImmutable;

/**
 * Response from a platform adapter after sending an event.
 *
 * Immutable value object representing the result of a platform API call.
 */
final readonly class PlatformResponse
{
    private function __construct(
        public bool $success,
        public ?string $errorMessage,
        public ?array $rawResponse,
        public DateTimeImmutable $sentAt
    ) {
    }

    /**
     * Create a successful response.
     *
     * @param array|null $rawResponse
     * @return self
     */
    public static function success(?array $rawResponse = null): self
    {
        return new self(
            true,
            null,
            $rawResponse,
            new DateTimeImmutable()
        );
    }

    /**
     * Create a failed response.
     *
     * @param string $errorMessage
     * @param array|null $rawResponse
     * @return self
     */
    public static function failure(string $errorMessage, ?array $rawResponse = null): self
    {
        return new self(
            false,
            $errorMessage,
            $rawResponse,
            new DateTimeImmutable()
        );
    }

    /**
     * Check if response is successful.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if response is a failure.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }
}
