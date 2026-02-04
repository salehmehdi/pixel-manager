<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidEmailException;

/**
 * Email address value object with validation and hashing capabilities.
 *
 * Immutable value object representing a validated email address.
 * Provides hashing for privacy-sensitive integrations.
 */
final readonly class Email
{
    private function __construct(
        public string $value
    ) {
    }

    /**
     * Create Email from string.
     *
     * @param string $email
     * @return self
     * @throws InvalidEmailException
     */
    public static function fromString(string $email): self
    {
        $normalized = strtolower(trim($email));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($email);
        }

        return new self($normalized);
    }

    /**
     * Hash the email using specified algorithm.
     *
     * @param HashAlgorithm $algorithm
     * @return string
     */
    public function hash(HashAlgorithm $algorithm = HashAlgorithm::SHA256): string
    {
        return $algorithm->hash($this->value);
    }

    /**
     * Get the string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get domain part of email.
     *
     * @return string
     */
    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    /**
     * Get local part of email (before @).
     *
     * @return string
     */
    public function localPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }
}
