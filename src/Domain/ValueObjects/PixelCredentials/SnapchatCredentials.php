<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Snapchat Pixel credentials.
 */
final readonly class SnapchatCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $pixelId,
        public string $accessToken
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::SNAPCHAT;
    }

    public function isValid(): bool
    {
        return !empty($this->pixelId) && !empty($this->accessToken);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['snapchat_pixel_id'] ?? '',
            $data['snapchat_access_token'] ?? ''
        );
    }
}
