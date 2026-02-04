<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\DTOs;

/**
 * Customer Data Transfer Object.
 */
final readonly class CustomerDTO
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
        public ?string $phoneCode,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $externalId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $gender,
        public ?string $dateOfBirth,
        public ?string $city,
        public ?string $state,
        public ?string $countryCode,
        public ?string $zipCode,
        public ?string $fbc,
        public ?string $fbp,
        public array $custom
    ) {
    }

    /**
     * Create from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            phoneCode: $data['phone_code'] ?? '',
            ipAddress: $data['ip_address'] ?? $data['client_ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? $data['client_user_agent'] ?? null,
            externalId: $data['external_id'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            gender: $data['gender'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            countryCode: $data['country_code'] ?? null,
            zipCode: $data['zip_code'] ?? null,
            fbc: $data['fbc'] ?? null,
            fbp: $data['fbp'] ?? null,
            custom: $data['custom'] ?? []
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_code' => $this->phoneCode,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'external_id' => $this->externalId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'gender' => $this->gender,
            'date_of_birth' => $this->dateOfBirth,
            'city' => $this->city,
            'state' => $this->state,
            'country_code' => $this->countryCode,
            'zip_code' => $this->zipCode,
            'fbc' => $this->fbc,
            'fbp' => $this->fbp,
            'custom' => $this->custom,
        ];
    }
}
