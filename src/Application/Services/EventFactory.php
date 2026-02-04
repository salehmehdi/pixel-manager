<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\Services;

use MehdiyevSignal\PixelManager\Application\DTOs\EventDTO;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Exceptions\DomainException;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\CustomerData;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Url;

/**
 * Event factory service.
 *
 * Creates domain entities from DTOs and raw data.
 */
final class EventFactory
{
    /**
     * Create PixelEvent from raw array data.
     *
     * @param array $data
     * @return PixelEvent
     * @throws DomainException
     */
    public function createFromArray(array $data): PixelEvent
    {
        $dto = EventDTO::fromArray($data);
        return $this->createFromDTO($dto);
    }

    /**
     * Create PixelEvent from DTO.
     *
     * @param EventDTO $dto
     * @return PixelEvent
     * @throws DomainException
     */
    public function createFromDTO(EventDTO $dto): PixelEvent
    {
        // Parse event type
        $eventType = EventType::tryFrom($dto->eventType);
        if (!$eventType) {
            throw new DomainException("Invalid event type: {$dto->eventType}");
        }

        // Build customer data
        $customer = $dto->customer
            ? CustomerData::fromArray($dto->customer->toArray())
            : CustomerData::builder()->build();

        // Parse value/currency
        $value = null;
        if ($dto->value !== null && $dto->currency) {
            try {
                $value = Money::from($dto->value, $dto->currency);
            } catch (\Exception $e) {
                // Invalid currency, ignore
            }
        }

        // Parse page URL
        $pageUrl = null;
        if ($dto->pageUrl) {
            try {
                $pageUrl = Url::fromString($dto->pageUrl);
            } catch (\Exception $e) {
                // Invalid URL, ignore
            }
        }

        return PixelEvent::create(
            type: $eventType,
            customer: $customer,
            items: $dto->items,
            value: $value,
            pageUrl: $pageUrl,
            customProperties: $dto->customProperties,
            transactionId: $dto->transactionId,
            orderId: $dto->orderId,
            shipping: $dto->shipping,
            searchTerm: $dto->searchTerm
        );
    }
}
