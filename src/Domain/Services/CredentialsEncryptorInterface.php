<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Services;

/**
 * Credentials encryption service interface.
 *
 * Encrypts and decrypts sensitive credential data.
 */
interface CredentialsEncryptorInterface
{
    /**
     * Encrypt credentials array.
     *
     * @param array $data
     * @return array
     */
    public function encryptArray(array $data): array;

    /**
     * Decrypt credentials array.
     *
     * @param array $data
     * @return array
     */
    public function decryptArray(array $data): array;

    /**
     * Encrypt a single value.
     *
     * @param string $value
     * @return string
     */
    public function encrypt(string $value): string;

    /**
     * Decrypt a single value.
     *
     * @param string $encryptedValue
     * @return string
     */
    public function decrypt(string $encryptedValue): string;
}
