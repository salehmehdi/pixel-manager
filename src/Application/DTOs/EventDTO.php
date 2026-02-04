<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\DTOs;

/**
 * Event Data Transfer Object.
 *
 * Transfers event data between layers.
 */
final readonly class EventDTO
{
    public function __construct(
        public string $eventType,
        public string $eventName,
        public ?string $eventId,
        public ?string $transactionId,
        public ?string $orderId,
        public ?float $value,
        public ?string $currency,
        public ?float $shipping,
        public ?string $searchTerm,
        public ?string $pageUrl,
        public ?CustomerDTO $customer,
        public array $items,
        public array $customProperties
    ) {
    }

    /**
     * Create from raw array data.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventType: $data['event_type'] ?? $data['event'] ?? '',
            eventName: $data['event'] ?? '',
            eventId: $data['event_id'] ?? null,
            transactionId: $data['transaction_id'] ?? null,
            orderId: $data['order_id'] ?? null,
            value: isset($data['value']) ? (float) $data['value'] : null,
            currency: $data['currency'] ?? null,
            shipping: isset($data['shipping']) ? (float) $data['shipping'] : null,
            searchTerm: $data['search_term'] ?? null,
            pageUrl: $data['page_url'] ?? $data['event_source_url'] ?? null,
            customer: isset($data['customer']) ? CustomerDTO::fromArray($data['customer']) : null,
            items: $data['items'] ?? $data['contents'] ?? [],
            customProperties: $data['custom_properties'] ?? []
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
            'event_type' => $this->eventType,
            'event' => $this->eventName,
            'event_id' => $this->eventId,
            'transaction_id' => $this->transactionId,
            'order_id' => $this->orderId,
            'value' => $this->value,
            'currency' => $this->currency,
            'shipping' => $this->shipping,
            'search_term' => $this->searchTerm,
            'page_url' => $this->pageUrl,
            'customer' => $this->customer?->toArray(),
            'items' => $this->items,
            'custom_properties' => $this->customProperties,
        ];
    }
}
