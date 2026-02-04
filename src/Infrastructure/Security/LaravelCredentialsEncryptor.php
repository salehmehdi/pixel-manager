<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Security;

use Illuminate\Contracts\Encryption\Encrypter;
use MehdiyevSignal\PixelManager\Domain\Services\CredentialsEncryptorInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel-based credentials encryptor.
 *
 * Encrypts sensitive credential fields using Laravel's encryption service.
 */
final class LaravelCredentialsEncryptor implements CredentialsEncryptorInterface
{
    /**
     * Fields that should be encrypted.
     */
    private const ENCRYPTED_FIELDS = [
        'meta_access_token',
        'google_api_secret',
        'tiktok_access_token',
        'pinterest_access_token',
        'snapchat_access_token',
        'brevo_api_key',
    ];

    public function __construct(
        private readonly Encrypter $encrypter,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Encrypt credentials array.
     *
     * @param array $data
     * @return array
     */
    public function encryptArray(array $data): array
    {
        $encrypted = $data;

        foreach (self::ENCRYPTED_FIELDS as $field) {
            if (isset($encrypted[$field]) && !empty($encrypted[$field])) {
                try {
                    $encrypted[$field] = $this->encrypt($encrypted[$field]);
                } catch (\Exception $e) {
                    $this->logger->error('Failed to encrypt credential field', [
                        'field' => $field,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $encrypted;
    }

    /**
     * Decrypt credentials array.
     *
     * @param array $data
     * @return array
     */
    public function decryptArray(array $data): array
    {
        $decrypted = $data;

        foreach (self::ENCRYPTED_FIELDS as $field) {
            if (isset($decrypted[$field]) && !empty($decrypted[$field])) {
                try {
                    $decrypted[$field] = $this->decrypt($decrypted[$field]);
                } catch (\Exception $e) {
                    // If decryption fails, assume it's not encrypted (migration scenario)
                    $this->logger->warning('Failed to decrypt credential field, using as-is', [
                        'field' => $field,
                        'error' => $e->getMessage(),
                    ]);
                    // Keep original value for backward compatibility
                }
            }
        }

        return $decrypted;
    }

    /**
     * Encrypt a single value.
     *
     * @param string $value
     * @return string
     */
    public function encrypt(string $value): string
    {
        return $this->encrypter->encrypt($value);
    }

    /**
     * Decrypt a single value.
     *
     * @param string $encryptedValue
     * @return string
     */
    public function decrypt(string $encryptedValue): string
    {
        return $this->encrypter->decrypt($encryptedValue);
    }
}
