<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Meta (Facebook) Pixel credentials.
 */
final readonly class MetaCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $pixelId,
        public string $accessToken
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::META;
    }

    public function isValid(): bool
    {
        return !empty($this->pixelId) && !empty($this->accessToken);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['meta_pixel_id'] ?? '',
            $data['meta_access_token'] ?? ''
        );
    }
}
