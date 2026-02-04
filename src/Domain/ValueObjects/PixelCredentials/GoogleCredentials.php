<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Google Analytics 4 credentials.
 */
final readonly class GoogleCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $measurementId,
        public string $apiSecret
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::GOOGLE;
    }

    public function isValid(): bool
    {
        return !empty($this->measurementId) && !empty($this->apiSecret);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['google_measurement_id'] ?? '',
            $data['google_api_secret'] ?? ''
        );
    }
}
