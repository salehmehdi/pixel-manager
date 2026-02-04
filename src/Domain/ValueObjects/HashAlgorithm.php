<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

/**
 * Hash algorithms used for sensitive data hashing (email, phone, etc.).
 *
 * Different platforms may require different hash algorithms.
 */
enum HashAlgorithm: string
{
    case SHA256 = 'sha256';
    case MD5 = 'md5';

    /**
     * Hash a string value using this algorithm.
     *
     * @param string $value
     * @return string
     */
    public function hash(string $value): string
    {
        return hash($this->value, $value);
    }
}
