<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PinterestEnvironment;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Pinterest Tag credentials.
 */
final readonly class PinterestCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $accountId,
        public string $accessToken,
        public PinterestEnvironment $environment = PinterestEnvironment::PRODUCTION
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::PINTEREST;
    }

    public function isValid(): bool
    {
        return !empty($this->accountId) && !empty($this->accessToken);
    }

    public static function fromArray(array $data): self
    {
        $env = isset($data['pinterest_environment'])
            ? PinterestEnvironment::from($data['pinterest_environment'])
            : PinterestEnvironment::PRODUCTION;

        return new self(
            $data['pinterest_account_id'] ?? '',
            $data['pinterest_access_token'] ?? '',
            $env
        );
    }
}
