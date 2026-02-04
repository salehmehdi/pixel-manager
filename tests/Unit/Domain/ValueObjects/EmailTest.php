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
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->value());
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('invalid-email');
    }

    public function test_throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('');
    }

    public function test_can_hash_email_with_sha256(): void
    {
        $email = new Email('test@example.com');
        $hashed = $email->hashed(HashAlgorithm::SHA256);

        $expectedHash = hash('sha256', strtolower(trim('test@example.com')));

        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_can_hash_email_with_md5(): void
    {
        $email = new Email('test@example.com');
        $hashed = $email->hashed(HashAlgorithm::MD5);

        $expectedHash = hash('md5', strtolower(trim('test@example.com')));

        $this->assertEquals($expectedHash, $hashed);
    }

    public function test_normalizes_email_before_hashing(): void
    {
        $email1 = new Email('  TEST@EXAMPLE.COM  ');
        $email2 = new Email('test@example.com');

        $this->assertEquals($email2->hashed(), $email1->hashed());
    }

    public function test_two_emails_with_same_value_are_equal(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');

        $this->assertEquals($email1->value(), $email2->value());
    }

    public function test_can_create_from_nullable(): void
    {
        $email = Email::fromNullable('test@example.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals('test@example.com', $email->value());
    }

    public function test_returns_null_for_empty_string(): void
    {
        $email = Email::fromNullable('');

        $this->assertNull($email);
    }

    public function test_returns_null_for_null_value(): void
    {
        $email = Email::fromNullable(null);

        $this->assertNull($email);
    }
}
