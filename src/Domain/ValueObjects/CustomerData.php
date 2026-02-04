<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * Customer data aggregate value object.
 *
 * Contains all customer information for event tracking.
 * All fields are optional as different platforms require different data.
 */
final readonly class CustomerData
{
    private function __construct(
        public ?Email $email,
        public ?Phone $phone,
        public ?IpAddress $ipAddress,
        public ?UserAgent $userAgent,
        public ?string $externalId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $gender,
        public ?DateTimeImmutable $dateOfBirth,
        public ?string $city,
        public ?string $state,
        public ?string $countryCode,
        public ?string $zipCode,
        public ?string $fbc,
        public ?string $fbp,
        public array $customProperties
    ) {
    }

    /**
     * Create CustomerData with fluent builder pattern.
     *
     * @return CustomerDataBuilder
     */
    public static function builder(): CustomerDataBuilder
    {
        return new CustomerDataBuilder();
    }

    /**
     * Create from array (for backward compatibility with old code).
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return self::builder()
            ->email($data['email'] ?? null)
            ->phone($data['phone'] ?? null, $data['phone_code'] ?? '')
            ->ipAddress($data['ip_address'] ?? null)
            ->userAgent($data['user_agent'] ?? null)
            ->externalId($data['external_id'] ?? null)
            ->firstName($data['first_name'] ?? null)
            ->lastName($data['last_name'] ?? null)
            ->gender($data['gender'] ?? null)
            ->dateOfBirth($data['date_of_birth'] ?? null)
            ->city($data['city'] ?? null)
            ->state($data['state'] ?? null)
            ->countryCode($data['country_code'] ?? null)
            ->zipCode($data['zip_code'] ?? null)
            ->fbc($data['fbc'] ?? null)
            ->fbp($data['fbp'] ?? null)
            ->customProperties($data['custom'] ?? [])
            ->build();
    }
}

/**
 * Builder for CustomerData.
 */
class CustomerDataBuilder
{
    private ?Email $email = null;
    private ?Phone $phone = null;
    private ?IpAddress $ipAddress = null;
    private ?UserAgent $userAgent = null;
    private ?string $externalId = null;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $gender = null;
    private ?DateTimeImmutable $dateOfBirth = null;
    private ?string $city = null;
    private ?string $state = null;
    private ?string $countryCode = null;
    private ?string $zipCode = null;
    private ?string $fbc = null;
    private ?string $fbp = null;
    private array $customProperties = [];

    public function email(?string $email): self
    {
        if ($email && !empty(trim($email))) {
            try {
                $this->email = Email::fromString($email);
            } catch (\Exception $e) {
                // Invalid email, ignore
                $this->email = null;
            }
        }
        return $this;
    }

    public function phone(?string $phone, string $countryCode = ''): self
    {
        if ($phone && !empty(trim($phone))) {
            try {
                $this->phone = Phone::fromParts($phone, $countryCode);
            } catch (\Exception $e) {
                // Invalid phone, ignore
                $this->phone = null;
            }
        }
        return $this;
    }

    public function ipAddress(?string $ip): self
    {
        if ($ip && !empty(trim($ip))) {
            try {
                $this->ipAddress = IpAddress::fromString($ip);
            } catch (\Exception $e) {
                // Invalid IP, ignore
                $this->ipAddress = null;
            }
        }
        return $this;
    }

    public function userAgent(?string $ua): self
    {
        if ($ua && !empty(trim($ua))) {
            $this->userAgent = UserAgent::fromString($ua);
        }
        return $this;
    }

    public function externalId(?string $id): self
    {
        $this->externalId = $id ? trim($id) : null;
        return $this;
    }

    public function firstName(?string $name): self
    {
        $this->firstName = $name ? trim($name) : null;
        return $this;
    }

    public function lastName(?string $name): self
    {
        $this->lastName = $name ? trim($name) : null;
        return $this;
    }

    public function gender(?string $gender): self
    {
        $this->gender = $gender ? trim($gender) : null;
        return $this;
    }

    public function dateOfBirth(string|DateTimeImmutable|null $dob): self
    {
        if ($dob instanceof DateTimeImmutable) {
            $this->dateOfBirth = $dob;
        } elseif (is_string($dob) && !empty($dob)) {
            try {
                $this->dateOfBirth = new DateTimeImmutable($dob);
            } catch (\Exception $e) {
                // Invalid date, ignore
                $this->dateOfBirth = null;
            }
        }
        return $this;
    }

    public function city(?string $city): self
    {
        $this->city = $city ? trim($city) : null;
        return $this;
    }

    public function state(?string $state): self
    {
        $this->state = $state ? trim($state) : null;
        return $this;
    }

    public function countryCode(?string $code): self
    {
        $this->countryCode = $code ? strtoupper(trim($code)) : null;
        return $this;
    }

    public function zipCode(?string $zip): self
    {
        $this->zipCode = $zip ? trim($zip) : null;
        return $this;
    }

    public function fbc(?string $fbc): self
    {
        $this->fbc = $fbc ? trim($fbc) : null;
        return $this;
    }

    public function fbp(?string $fbp): self
    {
        $this->fbp = $fbp ? trim($fbp) : null;
        return $this;
    }

    public function customProperties(array $properties): self
    {
        $this->customProperties = $properties;
        return $this;
    }

    public function build(): CustomerData
    {
        return new CustomerData(
            $this->email,
            $this->phone,
            $this->ipAddress,
            $this->userAgent,
            $this->externalId,
            $this->firstName,
            $this->lastName,
            $this->gender,
            $this->dateOfBirth,
            $this->city,
            $this->state,
            $this->countryCode,
            $this->zipCode,
            $this->fbc,
            $this->fbp,
            $this->customProperties
        );
    }
}
