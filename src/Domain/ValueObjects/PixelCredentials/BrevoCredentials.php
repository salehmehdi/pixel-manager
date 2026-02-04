<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Brevo (Sendinblue) credentials.
 */
final readonly class BrevoCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $apiKey
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::BREVO;
    }

    public function isValid(): bool
    {
        return !empty($this->apiKey);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['brevo_api_key'] ?? ''
        );
    }
}
