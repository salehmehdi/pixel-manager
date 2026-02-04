<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Mappings;

use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\BrevoCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\GoogleCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\MetaCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\PinterestCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\SnapchatCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\TikTokCredentials;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Models\ApplicationModel;

/**
 * Maps between ApplicationCredentials domain entity and MongoDB model.
 */
final class CredentialsMapper
{
    /**
     * Map MongoDB model to domain entity.
     *
     * @param ApplicationModel $model
     * @param array $decryptedData Decrypted credentials data
     * @return ApplicationCredentials
     */
    public function toDomain(ApplicationModel $model, array $decryptedData): ApplicationCredentials
    {
        $credentials = new ApplicationCredentials($model->app_id);

        // Meta
        if (isset($decryptedData['meta_pixel_id'], $decryptedData['meta_access_token'])) {
            $credentials->setCredentials(MetaCredentials::fromArray($decryptedData));
        }

        // Google
        if (isset($decryptedData['google_measurement_id'], $decryptedData['google_api_secret'])) {
            $credentials->setCredentials(GoogleCredentials::fromArray($decryptedData));
        }

        // TikTok
        if (isset($decryptedData['tiktok_pixel_code'], $decryptedData['tiktok_access_token'])) {
            $credentials->setCredentials(TikTokCredentials::fromArray($decryptedData));
        }

        // Pinterest
        if (isset($decryptedData['pinterest_account_id'], $decryptedData['pinterest_access_token'])) {
            $credentials->setCredentials(PinterestCredentials::fromArray($decryptedData));
        }

        // Snapchat
        if (isset($decryptedData['snapchat_pixel_id'], $decryptedData['snapchat_access_token'])) {
            $credentials->setCredentials(SnapchatCredentials::fromArray($decryptedData));
        }

        // Brevo
        if (isset($decryptedData['brevo_api_key'])) {
            $credentials->setCredentials(BrevoCredentials::fromArray($decryptedData));
        }

        return $credentials;
    }

    /**
     * Map domain entity to array for MongoDB storage.
     *
     * @param ApplicationCredentials $credentials
     * @return array
     */
    public function toArray(ApplicationCredentials $credentials): array
    {
        $data = [];

        foreach (PlatformType::cases() as $platform) {
            $platformCreds = $credentials->getCredentialsFor($platform);

            if ($platformCreds) {
                // Use reflection to extract credentials
                $reflection = new \ReflectionClass($platformCreds);

                foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    $name = $property->getName();
                    $value = $property->getValue($platformCreds);

                    // Build field name (e.g., meta_pixel_id)
                    $fieldName = $platform->value . '_' . $this->camelToSnake($name);
                    $data[$fieldName] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Convert camelCase to snake_case.
     *
     * @param string $input
     * @return string
     */
    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
