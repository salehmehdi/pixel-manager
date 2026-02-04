<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\Phone;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\HashAlgorithm;
use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidPhoneException;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class PhoneTest extends TestCase
{
    public function test_can_create_phone_from_full_number(): void
    {
        $phone = Phone::fromFullNumber('+12345678900');

        $this->assertEquals('+12345678900', $phone->fullNumber());
    }

    public function test_can_create_phone_from_parts(): void
    {
        $phone = Phone::fromParts('5551234567', '+1');

        $this->assertEquals('+1', $phone->countryCode);
        $this->assertEquals('5551234567', $phone->number);
    }

    public function test_can_hash_phone_with_sha256(): void
    {
        $phone = Phone::fromFullNumber('+12345678900');
        $hashed = $phone->hash(HashAlgorithm::SHA256);

        $expectedHash = hash('sha256', '+12345678900');
        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_can_hash_phone_with_md5(): void
    {
        $phone = Phone::fromFullNumber('+12345678900');
        $hashed = $phone->hash(HashAlgorithm::MD5);

        $expectedHash = hash('md5', '+12345678900');
        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_throws_exception_for_too_short_phone(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('Phone number must be between 6-15 digits');

        Phone::fromParts('123', '+1');
    }

    public function test_throws_exception_for_too_long_phone(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('Phone number must be between 6-15 digits');

        Phone::fromParts('12345678901234567890', '+1');
    }

    public function test_throws_exception_for_empty_phone(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessage('No digits found');

        Phone::fromParts('', '+1');
    }

    public function test_accepts_valid_azerbaijani_phone(): void
    {
        $phone = Phone::fromParts('501234567', '+994');

        $this->assertEquals('+994', $phone->countryCode);
        $this->assertEquals('501234567', $phone->number);
    }

    public function test_formats_phone_correctly(): void
    {
        $phone = Phone::fromFullNumber('+12345678900');

        $this->assertEquals('+12345678900', $phone->formatted());
    }

    public function test_to_string_returns_full_number(): void
    {
        $phone = Phone::fromFullNumber('+12345678900');

        $this->assertEquals('+12345678900', $phone->toString());
    }

    public function test_can_create_from_us_number(): void
    {
        $phone = Phone::fromParts('5551234567', '+1');

        $this->assertEquals('+1', $phone->countryCode);
        $this->assertEquals('5551234567', $phone->number);
        $this->assertEquals('+15551234567', $phone->fullNumber());
    }

    public function test_removes_formatting_from_phone_number(): void
    {
        $phone = Phone::fromParts('(555) 123-4567', '+1');

        // Should extract only digits
        $this->assertEquals('5551234567', $phone->number);
        $this->assertEquals('+15551234567', $phone->fullNumber());
    }
}
