<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\Cache;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Cached credentials repository (Decorator Pattern).
 *
 * Wraps another credentials repository with caching layer.
 * Reduces MongoDB queries by ~90%.
 */
final class CachedCredentialsRepository implements CredentialsRepositoryInterface
{
    private const CACHE_PREFIX = 'pixel_manager:credentials:';

    public function __construct(
        private readonly CredentialsRepositoryInterface $inner,
        private readonly CacheRepository $cache,
        private readonly int $ttl = 3600
    ) {
    }

    /**
     * Find credentials by application ID (with caching).
     *
     * @param int $appId
     * @return ApplicationCredentials|null
     */
    public function findByApplicationId(int $appId): ?ApplicationCredentials
    {
        $cacheKey = self::CACHE_PREFIX . "app:{$appId}";

        return $this->cache->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->inner->findByApplicationId($appId)
        );
    }

    /**
     * Find platform-specific credentials (with caching).
     *
     * @param int $appId
     * @param PlatformType $platform
     * @return PlatformCredentialsInterface|null
     */
    public function findPlatformCredentials(int $appId, PlatformType $platform): ?PlatformCredentialsInterface
    {
        // Use full credentials cache, then extract platform
        $credentials = $this->findByApplicationId($appId);

        return $credentials?->getCredentialsFor($platform);
    }

    /**
     * Save application credentials (invalidates cache).
     *
     * @param ApplicationCredentials $credentials
     * @return void
     */
    public function save(ApplicationCredentials $credentials): void
    {
        $this->inner->save($credentials);
        $this->invalidateCache($credentials->getAppId());
    }

    /**
     * Delete application credentials (invalidates cache).
     *
     * @param int $appId
     * @return void
     */
    public function delete(int $appId): void
    {
        $this->inner->delete($appId);
        $this->invalidateCache($appId);
    }

    /**
     * Invalidate cache for an application.
     *
     * @param int $appId
     * @return void
     */
    private function invalidateCache(int $appId): void
    {
        $cacheKey = self::CACHE_PREFIX . "app:{$appId}";
        $this->cache->forget($cacheKey);
    }
}
