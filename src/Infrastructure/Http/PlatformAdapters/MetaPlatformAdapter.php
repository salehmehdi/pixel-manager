<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\MetaCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Meta (Facebook) Pixel adapter.
 *
 * BUG FIX: Fixed date_of_birth being set with setGender() method (original line 189).
 */
final class MetaPlatformAdapter extends AbstractHttpPlatformAdapter
{
    public function getPlatformType(): PlatformType
    {
        return PlatformType::META;
    }

    public function supports(EventType $eventType): bool
    {
        return true; // Meta supports all event types
    }

    public function mapEventName(EventType $type): ?string
    {
        return match ($type) {
            EventType::ADD_TO_CART => 'AddToCart',
            EventType::VIEW_ITEM => 'ViewContent',
            EventType::PURCHASE => 'Purchase',
            EventType::COMPLETED_REGISTRATION => 'CompleteRegistration',
            EventType::PAGE_VIEW => 'PageView',
            EventType::SEARCH => 'Search',
            EventType::SUBSCRIPTION => 'Subscribe',
            EventType::BEGIN_CHECKOUT => 'InitiateCheckout',
            EventType::VIEW_CART => 'ViewCart',
            EventType::ADD_PAYMENT_INFO => 'AddPaymentInfo',
            EventType::ADD_TO_WISHLIST => 'AddToWishlist',
            EventType::CUSTOMIZE_PRODUCT => 'CustomizeProduct',
        };
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof MetaCredentials);

        $customer = $event->getCustomer();
        $userData = [];

        // Email (hashed)
        if ($customer->email) {
            $userData['em'] = $customer->email->hash();
        }

        // Phone (hashed)
        if ($customer->phone) {
            $userData['ph'] = $customer->phone->hash();
        }

        // IP Address
        if ($customer->ipAddress) {
            $userData['client_ip_address'] = $customer->ipAddress->toString();
        }

        // User Agent
        if ($customer->userAgent) {
            $userData['client_user_agent'] = $customer->userAgent->toString();
        }

        // BUG FIX: Was using setGender() for date_of_birth (original line 189)
        // NOW FIXED: Correctly setting date_of_birth
        if ($customer->dateOfBirth) {
            $userData['db'] = $customer->dateOfBirth->format('Ymd');
        }

        // Gender
        if ($customer->gender) {
            $userData['ge'] = strtolower(substr($customer->gender, 0, 1));
        }

        // Names
        if ($customer->firstName) {
            $userData['fn'] = strtolower($customer->firstName);
        }

        if ($customer->lastName) {
            $userData['ln'] = strtolower($customer->lastName);
        }

        // Location
        if ($customer->city) {
            $userData['ct'] = strtolower($customer->city);
        }

        if ($customer->state) {
            $userData['st'] = strtolower($customer->state);
        }

        if ($customer->countryCode) {
            $userData['country'] = strtolower($customer->countryCode);
        }

        if ($customer->zipCode) {
            $userData['zp'] = $customer->zipCode;
        }

        // External ID
        if ($customer->externalId) {
            $userData['external_id'] = $customer->externalId;
        }

        // FBC/FBP tracking
        if ($customer->fbc) {
            $userData['fbc'] = $customer->fbc;
        }

        if ($customer->fbp) {
            $userData['fbp'] = $customer->fbp;
        }

        // Build custom data
        $customData = [];

        if ($event->getValue()) {
            $customData['value'] = $event->getValue()->amount;
            $customData['currency'] = $event->getValue()->currencyCode();
        }

        if ($event->hasItems()) {
            $customData['contents'] = array_map(function ($item) {
                return [
                    'id' => $item['item_id'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'item_price' => $item['price'] ?? 0,
                ];
            }, $event->getItems());

            $customData['num_items'] = count($event->getItems());
        }

        if ($event->getTransactionId()) {
            $customData['order_id'] = $event->getTransactionId();
        }

        if ($event->getSearchTerm()) {
            $customData['search_string'] = $event->getSearchTerm();
        }

        // Build final event
        $fbEvent = [
            'event_name' => $this->mapEventName($event->getType()),
            'event_id' => $event->getId()->toString(),
            'event_time' => $event->getOccurredAt()->getTimestamp(),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        if ($event->getPageUrl()) {
            $fbEvent['event_source_url'] = $event->getPageUrl()->toString();
        }

        return [
            'data' => [$fbEvent],
            'access_token' => $credentials->accessToken,
        ];
    }

    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        assert($credentials instanceof MetaCredentials);
        return "https://graph.facebook.com/v18.0/{$credentials->pixelId}/events";
    }

    protected function getHeaders(PlatformCredentialsInterface $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }
}
