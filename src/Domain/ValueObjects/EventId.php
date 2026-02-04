<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MongoDB\BSON\ObjectId;

/**
 * Event identifier value object.
 *
 * Immutable value object representing a unique event identifier.
 * Uses MongoDB ObjectId for generation.
 */
final readonly class EventId
{
    private function __construct(
        public string $value
    ) {
    }

    /**
     * Generate a new unique EventId.
     *
     * @return self
     */
    public static function generate(): self
    {
        return new self((string) new ObjectId());
    }

    /**
     * Create EventId from existing string.
     *
     * @param string $id
     * @return self
     */
    public static function fromString(string $id): self
    {
        return new self($id);
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

    /**
     * Check if this equals another EventId.
     *
     * @param EventId $other
     * @return bool
     */
    public function equals(EventId $other): bool
    {
        return $this->value === $other->value;
    }
}
