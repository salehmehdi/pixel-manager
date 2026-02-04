<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidEmailException;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Email;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\HashAlgorithm;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class EmailTest extends TestCase
{
    public function test_can_create_valid_email(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertEquals('test@example.com', $email->value);
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        Email::fromString('invalid-email');
    }

    public function test_throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        Email::fromString('');
    }

    public function test_normalizes_email_to_lowercase(): void
    {
        $email = Email::fromString('TEST@EXAMPLE.COM');

        $this->assertEquals('test@example.com', $email->value);
    }

    public function test_trims_whitespace(): void
    {
        $email = Email::fromString('  test@example.com  ');

        $this->assertEquals('test@example.com', $email->value);
    }

    public function test_can_hash_email_with_sha256(): void
    {
        $email = Email::fromString('test@example.com');
        $hashed = $email->hash(HashAlgorithm::SHA256);

        $expectedHash = hash('sha256', 'test@example.com');
        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_can_hash_email_with_md5(): void
    {
        $email = Email::fromString('test@example.com');
        $hashed = $email->hash(HashAlgorithm::MD5);

        $expectedHash = hash('md5', 'test@example.com');
        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_hashes_normalized_email(): void
    {
        $email1 = Email::fromString('  TEST@EXAMPLE.COM  ');
        $email2 = Email::fromString('test@example.com');

        $this->assertEquals($email2->hash(), $email1->hash());
    }

    public function test_can_get_domain(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertEquals('example.com', $email->domain());
    }

    public function test_can_get_local_part(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertEquals('test', $email->localPart());
    }

    public function test_to_string_returns_value(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertEquals('test@example.com', $email->toString());
    }
}
