<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Repositories;

use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Repository interface for application credentials.
 *
 * Abstracts data access for credentials storage (MongoDB, etc.).
 */
interface CredentialsRepositoryInterface
{
    /**
     * Find credentials by application ID.
     *
     * @param int $appId
     * @return ApplicationCredentials|null
     */
    public function findByApplicationId(int $appId): ?ApplicationCredentials;

    /**
     * Find platform-specific credentials.
     *
     * @param int $appId
     * @param PlatformType $platform
     * @return PlatformCredentialsInterface|null
     */
    public function findPlatformCredentials(int $appId, PlatformType $platform): ?PlatformCredentialsInterface;

    /**
     * Save application credentials.
     *
     * @param ApplicationCredentials $credentials
     * @return void
     */
    public function save(ApplicationCredentials $credentials): void;

    /**
     * Delete application credentials.
     *
     * @param int $appId
     * @return void
     */
    public function delete(int $appId): void;
}
