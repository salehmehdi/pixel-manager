<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL;

use Illuminate\Support\Facades\DB;
use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\Services\CredentialsEncryptorInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * SQL-based credentials repository.
 *
 * Supports MySQL, PostgreSQL, SQLite, etc.
 */
final class SQLCredentialsRepository implements CredentialsRepositoryInterface
{
    public function __construct(
        private readonly string $connection,
        private readonly string $tableName,
        private readonly ?CredentialsEncryptorInterface $encryptor = null
    ) {
    }

    public function findByApplicationId(int $appId): ?ApplicationCredentials
    {
        $record = DB::connection($this->connection)
            ->table($this->tableName)
            ->where('app_id', $appId)
            ->where('category', 'customer_event')
            ->first();

        if (!$record) {
            return null;
        }

        $data = json_decode($record->data, true);

        // Decrypt if encryptor is available
        if ($this->encryptor) {
            $data = $this->encryptor->decrypt($data);
        }

        return new ApplicationCredentials(
            appId: $record->app_id,
            category: $record->category,
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
        $data = $credentials->getData();

        // Encrypt if encryptor is available
        if ($this->encryptor) {
            $data = $this->encryptor->encrypt($data);
        }

        DB::connection($this->connection)
            ->table($this->tableName)
            ->updateOrInsert(
                [
                    'app_id' => $credentials->getAppId(),
                    'category' => $credentials->getCategory(),
                ],
                [
                    'data' => json_encode($data),
                    'updated_at' => now(),
                ]
            );
    }

    public function delete(int $appId): void
    {
        DB::connection($this->connection)
            ->table($this->tableName)
            ->where('app_id', $appId)
            ->where('category', 'customer_event')
            ->delete();
    }
}
