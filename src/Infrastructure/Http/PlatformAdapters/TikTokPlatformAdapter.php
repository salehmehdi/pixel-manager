<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\TikTokCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * TikTok Pixel adapter.
 */
final class TikTokPlatformAdapter extends AbstractHttpPlatformAdapter
{
    public function getPlatformType(): PlatformType
    {
        return PlatformType::TIKTOK;
    }

    public function supports(EventType $eventType): bool
    {
        return $eventType !== EventType::CUSTOMIZE_PRODUCT;
    }

    public function mapEventName(EventType $type): ?string
    {
        return match ($type) {
            EventType::PURCHASE => 'CompletePayment',
            EventType::ADD_TO_CART => 'AddToCart',
            EventType::VIEW_ITEM => 'ViewContent',
            EventType::BEGIN_CHECKOUT => 'InitiateCheckout',
            EventType::ADD_TO_WISHLIST => 'AddToWishlist',
            EventType::ADD_PAYMENT_INFO => 'AddPaymentInfo',
            EventType::COMPLETED_REGISTRATION => 'CompleteRegistration',
            EventType::PAGE_VIEW => 'PageView',
            EventType::SEARCH => 'Search',
            EventType::SUBSCRIPTION => 'Subscribe',
            default => null,
        };
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof TikTokCredentials);

        $customer = $event->getCustomer();
        $user = [];

        // Email and phone (SHA256 hashed)
        if ($customer->email) {
            $user['email'] = $customer->email->hash();
        }

        if ($customer->phone) {
            $user['phone_number'] = $customer->phone->hash();
        }

        // IP and User Agent (plain)
        if ($customer->ipAddress) {
            $user['ip'] = $customer->ipAddress->toString();
        }

        if ($customer->userAgent) {
            $user['user_agent'] = $customer->userAgent->toString();
        }

        // External ID
        if ($customer->externalId) {
            $user['external_id'] = $customer->externalId;
        }

        // Build properties
        $properties = [
            'pixel_code' => $credentials->pixelCode,
        ];

        if ($event->getValue()) {
            $properties['value'] = $event->getValue()->amount;
            $properties['currency'] = $event->getValue()->currencyCode();
        }

        if ($event->hasItems()) {
            $properties['contents'] = array_map(function ($item) {
                return [
                    'content_id' => $item['item_id'] ?? '',
                    'content_name' => $item['item_name'] ?? '',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'content_category' => $item['category'] ?? '',
                    'brand' => $item['brand'] ?? '',
                ];
            }, $event->getItems());

            $properties['content_type'] = 'product';
        }

        if ($event->getSearchTerm()) {
            $properties['query'] = $event->getSearchTerm();
        }

        // Build event
        return [
            'pixel_code' => $credentials->pixelCode,
            'event' => $this->mapEventName($event->getType()),
            'event_id' => $event->getId()->toString(),
            'timestamp' => $event->getOccurredAt()->format('Y-m-d\TH:i:s\Z'),
            'context' => [
                'user' => $user,
                'page' => [
                    'url' => $event->getPageUrl()?->toString() ?? '',
                ],
            ],
            'properties' => $properties,
        ];
    }

    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        return 'https://business-api.tiktok.com/open_api/v1.3/event/track/';
    }

    protected function getHeaders(PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof TikTokCredentials);

        return [
            'Access-Token' => $credentials->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
