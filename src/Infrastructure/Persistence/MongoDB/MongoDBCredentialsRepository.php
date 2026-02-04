<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB;

use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\Services\CredentialsEncryptorInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Mappings\CredentialsMapper;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Models\ApplicationModel;

/**
 * MongoDB implementation of credentials repository.
 */
final class MongoDBCredentialsRepository implements CredentialsRepositoryInterface
{
    public function __construct(
        private readonly string $connection,
        private readonly string $collection,
        private readonly CredentialsMapper $mapper,
        private readonly CredentialsEncryptorInterface $encryptor
    ) {
    }

    /**
     * Find credentials by application ID.
     *
     * @param int $appId
     * @return ApplicationCredentials|null
     */
    public function findByApplicationId(int $appId): ?ApplicationCredentials
    {
        $model = ApplicationModel::on($this->connection)
            ->where('app_id', $appId)
            ->where('category', 'customer_event')
            ->first();

        if (!$model) {
            return null;
        }

        // Decrypt credentials
        $decryptedData = $this->encryptor->decryptArray($model->data ?? []);

        return $this->mapper->toDomain($model, $decryptedData);
    }

    /**
     * Find platform-specific credentials.
     *
     * @param int $appId
     * @param PlatformType $platform
     * @return PlatformCredentialsInterface|null
     */
    public function findPlatformCredentials(int $appId, PlatformType $platform): ?PlatformCredentialsInterface
    {
        $credentials = $this->findByApplicationId($appId);

        return $credentials?->getCredentialsFor($platform);
    }

    /**
     * Save application credentials.
     *
     * @param ApplicationCredentials $credentials
     * @return void
     */
    public function save(ApplicationCredentials $credentials): void
    {
        $data = $this->mapper->toArray($credentials);

        // Encrypt sensitive fields
        $encryptedData = $this->encryptor->encryptArray($data);

        ApplicationModel::on($this->connection)->updateOrCreate(
            [
                'app_id' => $credentials->getAppId(),
                'category' => 'customer_event'
            ],
            ['data' => $encryptedData]
        );
    }

    /**
     * Delete application credentials.
     *
     * @param int $appId
     * @return void
     */
    public function delete(int $appId): void
    {
        ApplicationModel::on($this->connection)
            ->where('app_id', $appId)
            ->where('category', 'customer_event')
            ->delete();
    }
}
