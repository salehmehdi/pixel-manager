<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\Entities;

use DateTimeImmutable;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\CustomerData;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Url;

/**
 * PixelEvent aggregate root.
 *
 * Represents a tracking event with all associated data.
 * This is the core domain entity that flows through the system.
 */
final class PixelEvent
{
    private function __construct(
        private EventId $id,
        private EventType $type,
        private CustomerData $customer,
        private array $items,
        private ?Money $value,
        private ?Url $pageUrl,
        private DateTimeImmutable $occurredAt,
        private array $customProperties,
        private ?string $transactionId = null,
        private ?string $orderId = null,
        private ?float $shipping = null,
        private ?string $searchTerm = null
    ) {
    }

    /**
     * Create a new PixelEvent.
     *
     * @param EventType $type
     * @param CustomerData $customer
     * @param array $items
     * @param Money|null $value
     * @param Url|null $pageUrl
     * @param array $customProperties
     * @param string|null $transactionId
     * @param string|null $orderId
     * @param float|null $shipping
     * @param string|null $searchTerm
     * @return self
     */
    public static function create(
        EventType $type,
        CustomerData $customer,
        array $items = [],
        ?Money $value = null,
        ?Url $pageUrl = null,
        array $customProperties = [],
        ?string $transactionId = null,
        ?string $orderId = null,
        ?float $shipping = null,
        ?string $searchTerm = null
    ): self {
        return new self(
            EventId::generate(),
            $type,
            $customer,
            $items,
            $value,
            $pageUrl,
            new DateTimeImmutable(),
            $customProperties,
            $transactionId,
            $orderId,
            $shipping,
            $searchTerm
        );
    }

    /**
     * Reconstitute from existing ID (for repository loading).
     *
     * @param EventId $id
     * @param EventType $type
     * @param CustomerData $customer
     * @param array $items
     * @param Money|null $value
     * @param Url|null $pageUrl
     * @param DateTimeImmutable $occurredAt
     * @param array $customProperties
     * @param string|null $transactionId
     * @param string|null $orderId
     * @param float|null $shipping
     * @param string|null $searchTerm
     * @return self
     */
    public static function reconstitute(
        EventId $id,
        EventType $type,
        CustomerData $customer,
        array $items,
        ?Money $value,
        ?Url $pageUrl,
        DateTimeImmutable $occurredAt,
        array $customProperties,
        ?string $transactionId = null,
        ?string $orderId = null,
        ?float $shipping = null,
        ?string $searchTerm = null
    ): self {
        return new self(
            $id,
            $type,
            $customer,
            $items,
            $value,
            $pageUrl,
            $occurredAt,
            $customProperties,
            $transactionId,
            $orderId,
            $shipping,
            $searchTerm
        );
    }

    /**
     * Get event ID.
     *
     * @return EventId
     */
    public function getId(): EventId
    {
        return $this->id;
    }

    /**
     * Get event type.
     *
     * @return EventType
     */
    public function getType(): EventType
    {
        return $this->type;
    }

    /**
     * Get customer data.
     *
     * @return CustomerData
     */
    public function getCustomer(): CustomerData
    {
        return $this->customer;
    }

    /**
     * Get items.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get event value (monetary).
     *
     * @return Money|null
     */
    public function getValue(): ?Money
    {
        return $this->value;
    }

    /**
     * Get page URL.
     *
     * @return Url|null
     */
    public function getPageUrl(): ?Url
    {
        return $this->pageUrl;
    }

    /**
     * Get occurrence timestamp.
     *
     * @return DateTimeImmutable
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Get custom properties.
     *
     * @return array
     */
    public function getCustomProperties(): array
    {
        return $this->customProperties;
    }

    /**
     * Get transaction ID.
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * Get order ID.
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * Get shipping cost.
     *
     * @return float|null
     */
    public function getShipping(): ?float
    {
        return $this->shipping;
    }

    /**
     * Get search term.
     *
     * @return string|null
     */
    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    /**
     * Check if event has a specific custom property.
     *
     * @param string $key
     * @return bool
     */
    public function hasCustomProperty(string $key): bool
    {
        return array_key_exists($key, $this->customProperties);
    }

    /**
     * Get a custom property value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        return $this->customProperties[$key] ?? $default;
    }

    /**
     * Check if this event is a purchase.
     *
     * @return bool
     */
    public function isPurchase(): bool
    {
        return $this->type === EventType::PURCHASE;
    }

    /**
     * Check if this event has items.
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    /**
     * Check if this event has monetary value.
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return $this->value !== null && $this->value->isPositive();
    }
}
