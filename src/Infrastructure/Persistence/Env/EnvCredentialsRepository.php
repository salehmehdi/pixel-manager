<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\Env;

use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Environment-based credentials repository.
 *
 * Reads credentials directly from .env file instead of database.
 * Perfect for simple setups or single-environment applications.
 */
final class EnvCredentialsRepository implements CredentialsRepositoryInterface
{
    public function findByApplicationId(int $appId): ?ApplicationCredentials
    {
        // Read credentials from environment variables
        $data = [
            // Meta Pixel
            'meta_pixel_id' => env('PIXEL_META_PIXEL_ID'),
            'meta_access_token' => env('PIXEL_META_ACCESS_TOKEN'),

            // Google Analytics 4
            'google_measurement_id' => env('PIXEL_GOOGLE_MEASUREMENT_ID'),
            'google_api_secret' => env('PIXEL_GOOGLE_API_SECRET'),

            // TikTok
            'tiktok_pixel_code' => env('PIXEL_TIKTOK_PIXEL_CODE'),
            'tiktok_access_token' => env('PIXEL_TIKTOK_ACCESS_TOKEN'),

            // Pinterest
            'pinterest_account_id' => env('PIXEL_PINTEREST_ACCOUNT_ID'),
            'pinterest_access_token' => env('PIXEL_PINTEREST_ACCESS_TOKEN'),
            'pinterest_environment' => env('PIXEL_PINTEREST_ENVIRONMENT', 'production'),

            // Snapchat
            'snapchat_pixel_id' => env('PIXEL_SNAPCHAT_PIXEL_ID'),
            'snapchat_access_token' => env('PIXEL_SNAPCHAT_ACCESS_TOKEN'),

            // Brevo
            'brevo_api_key' => env('PIXEL_BREVO_API_KEY'),
        ];

        // Filter out empty values
        $data = array_filter($data, fn($value) => !empty($value));

        // If no credentials found, return null
        if (empty($data)) {
            return null;
        }

        return new ApplicationCredentials(
            appId: $appId,
            category: 'customer_event',
            data: $data
        );
    }

    public function findPlatformCredentials(
        int $appId,
        PlatformType $platform
    ): ?PlatformCredentialsInterface {
        $credentials = $this->findByApplicationId($appId);

        return $credentials?->getCredentialsFor($platform);
    }

    public function save(ApplicationCredentials $credentials): void
    {
        // Environment credentials are read-only
        throw new \RuntimeException(
            'Cannot save credentials to environment. ' .
            'Please update your .env file manually.'
        );
    }

    public function delete(int $appId): void
    {
        // Environment credentials are read-only
        throw new \RuntimeException(
            'Cannot delete credentials from environment. ' .
            'Please update your .env file manually.'
        );
    }
}
