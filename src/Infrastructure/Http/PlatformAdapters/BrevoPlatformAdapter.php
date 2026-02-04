<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\BrevoCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Brevo (Sendinblue) adapter.
 */
final class BrevoPlatformAdapter extends AbstractHttpPlatformAdapter
{
    public function getPlatformType(): PlatformType
    {
        return PlatformType::BREVO;
    }

    public function supports(EventType $eventType): bool
    {
        return true;
    }

    public function mapEventName(EventType $type): ?string
    {
        return $type->value; // Brevo uses same event names
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        $customer = $event->getCustomer();

        // Build contact identifiers (at least one required)
        $identifiers = [];

        if ($customer->email) {
            $identifiers['email_id'] = $customer->email->toString();
        }

        if ($customer->externalId) {
            $identifiers['ext_id'] = $customer->externalId;
        }

        // Build contact properties
        $contactProperties = [];

        if ($customer->firstName) {
            $contactProperties['FIRSTNAME'] = $customer->firstName;
        }

        if ($customer->lastName) {
            $contactProperties['LASTNAME'] = $customer->lastName;
        }

        if ($customer->phone) {
            $contactProperties['SMS'] = $customer->phone->toString();
        }

        if ($customer->city) {
            $contactProperties['CITY'] = $customer->city;
        }

        if ($customer->state) {
            $contactProperties['STATE'] = $customer->state;
        }

        if ($customer->countryCode) {
            $contactProperties['COUNTRY'] = $customer->countryCode;
        }

        if ($customer->zipCode) {
            $contactProperties['ZIP_CODE'] = $customer->zipCode;
        }

        // Build event properties
        $eventProperties = [];

        if ($event->getValue()) {
            $eventProperties['value'] = $event->getValue()->amount;
            $eventProperties['currency'] = $event->getValue()->currencyCode();
        }

        if ($event->getOrderId()) {
            $eventProperties['order_id'] = $event->getOrderId();
        }

        if ($event->hasItems()) {
            $eventProperties['items'] = array_map(function ($item) {
                return [
                    'product_id' => $item['item_id'] ?? '',
                    'title' => $item['item_name'] ?? '',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'category' => $item['category'] ?? '',
                    'brand' => $item['brand'] ?? '',
                ];
            }, $event->getItems());
        }

        if ($event->getPageUrl()) {
            $eventProperties['page_url'] = $event->getPageUrl()->toString();
        }

        return [
            'event' => $this->mapEventName($event->getType()),
            'identifiers' => $identifiers,
            'contact_properties' => $contactProperties,
            'event_properties' => $eventProperties,
        ];
    }

    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        return 'https://api.brevo.com/v3/events';
    }

    protected function getHeaders(PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof BrevoCredentials);

        return [
            'api-key' => $credentials->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
