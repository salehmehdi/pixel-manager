<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\Phone;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\HashAlgorithm;
use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidPhoneException;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class PhoneTest extends TestCase
{
    public function test_can_create_valid_phone_with_country_code(): void
    {
        $phone = new Phone('+1234567890', 'US');

        $this->assertEquals('+1234567890', $phone->value());
        $this->assertEquals('US', $phone->countryCode());
    }

    public function test_can_create_phone_without_country_code(): void
    {
        $phone = new Phone('+1234567890');

        $this->assertEquals('+1234567890', $phone->value());
        $this->assertNull($phone->countryCode());
    }

    public function test_normalizes_phone_number(): void
    {
        $phone = new Phone('+1 (234) 567-8900', 'US');

        // Normalized should strip non-digits except leading +
        $this->assertEquals('+12345678900', $phone->normalized());
    }

    public function test_can_hash_phone_with_sha256(): void
    {
        $phone = new Phone('+1234567890', 'US');
        $hashed = $phone->hashed(HashAlgorithm::SHA256);

        $expectedHash = hash('sha256', '+1234567890');
        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_can_hash_phone_with_md5(): void
    {
        $phone = new Phone('+1234567890', 'US');
        $hashed = $phone->hashed(HashAlgorithm::MD5);

        $expectedHash = hash('md5', '+1234567890');
        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_hashes_normalized_value(): void
    {
        $phone1 = new Phone('+1234567890', 'US');
        $phone2 = new Phone('+1 (234) 567-8900', 'US');

        // Both should hash to the same value after normalization
        $this->assertEquals(
            $phone1->hashed(HashAlgorithm::SHA256),
            $phone2->hashed(HashAlgorithm::SHA256)
        );
    }

    public function test_throws_exception_for_empty_phone(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('Phone number cannot be empty');

        new Phone('');
    }

    public function test_throws_exception_for_phone_without_plus(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('Phone number must start with +');

        new Phone('1234567890');
    }

    public function test_throws_exception_for_too_short_phone(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('Phone number must be at least 8 digits');

        new Phone('+123');
    }

    public function test_throws_exception_for_too_long_phone(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('Phone number must be at most 15 digits');

        new Phone('+1234567890123456');
    }

    public function test_accepts_valid_azerbaijani_phone(): void
    {
        $phone = new Phone('+994501234567', 'AZ');

        $this->assertEquals('+994501234567', $phone->value());
        $this->assertEquals('AZ', $phone->countryCode());
    }

    public function test_phone_equality(): void
    {
        $phone1 = new Phone('+1234567890', 'US');
        $phone2 = new Phone('+1234567890', 'US');
        $phone3 = new Phone('+9876543210', 'AZ');

        $this->assertTrue($phone1->equals($phone2));
        $this->assertFalse($phone1->equals($phone3));
    }

    public function test_to_string_returns_value(): void
    {
        $phone = new Phone('+1234567890', 'US');

        $this->assertEquals('+1234567890', (string) $phone);
    }
}
