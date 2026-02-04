<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * TikTok Pixel credentials.
 */
final readonly class TikTokCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $pixelCode,
        public string $accessToken
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::TIKTOK;
    }

    public function isValid(): bool
    {
        return !empty($this->pixelCode) && !empty($this->accessToken);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['tiktok_pixel_code'] ?? '',
            $data['tiktok_access_token'] ?? ''
        );
    }
}
