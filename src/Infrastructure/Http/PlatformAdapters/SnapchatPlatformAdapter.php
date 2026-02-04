<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\SnapchatCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Snapchat Pixel adapter.
 */
final class SnapchatPlatformAdapter extends AbstractHttpPlatformAdapter
{
    public function getPlatformType(): PlatformType
    {
        return PlatformType::SNAPCHAT;
    }

    public function supports(EventType $eventType): bool
    {
        return $eventType !== EventType::CUSTOMIZE_PRODUCT;
    }

    public function mapEventName(EventType $type): ?string
    {
        return match ($type) {
            EventType::PURCHASE => 'PURCHASE',
            EventType::ADD_TO_CART => 'ADD_CART',
            EventType::VIEW_ITEM => 'VIEW_CONTENT',
            EventType::BEGIN_CHECKOUT => 'START_CHECKOUT',
            EventType::ADD_TO_WISHLIST => 'ADD_TO_WISHLIST',
            EventType::COMPLETED_REGISTRATION => 'SIGN_UP',
            EventType::PAGE_VIEW => 'PAGE_VIEW',
            EventType::SEARCH => 'SEARCH',
            EventType::SUBSCRIPTION => 'SUBSCRIBE',
            default => null,
        };
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof SnapchatCredentials);

        $customer = $event->getCustomer();
        $userData = [];

        // Email and phone (SHA256 hashed)
        if ($customer->email) {
            $userData['em'] = [$customer->email->hash()];
        }

        if ($customer->phone) {
            $userData['ph'] = [$customer->phone->hash()];
        }

        // Gender
        if ($customer->gender) {
            $userData['ge'] = [hash('sha256', strtolower($customer->gender))];
        }

        // Names
        if ($customer->firstName) {
            $userData['fn'] = [hash('sha256', strtolower($customer->firstName))];
        }

        if ($customer->lastName) {
            $userData['ln'] = [hash('sha256', strtolower($customer->lastName))];
        }

        // Location
        if ($customer->city) {
            $userData['ct'] = [hash('sha256', strtolower($customer->city))];
        }

        if ($customer->state) {
            $userData['st'] = [hash('sha256', strtolower($customer->state))];
        }

        if ($customer->countryCode) {
            $userData['country'] = [hash('sha256', strtolower($customer->countryCode))];
        }

        if ($customer->zipCode) {
            $userData['zp'] = [hash('sha256', $customer->zipCode)];
        }

        // External ID
        if ($customer->externalId) {
            $userData['external_id'] = [hash('sha256', $customer->externalId)];
        }

        // IP and User Agent (not hashed)
        if ($customer->ipAddress) {
            $userData['client_ip_address'] = $customer->ipAddress->toString();
        }

        if ($customer->userAgent) {
            $userData['client_user_agent'] = $customer->userAgent->toString();
        }

        // Build custom data
        $customData = [];

        if ($event->getValue()) {
            $customData['price'] = (string) $event->getValue()->amount;
            $customData['currency'] = $event->getValue()->currencyCode();
        }

        if ($event->hasItems()) {
            $customData['item_ids'] = array_map(
                fn($item) => $item['item_id'] ?? '',
                $event->getItems()
            );

            $customData['number_items'] = (string) count($event->getItems());
        }

        if ($event->getOrderId()) {
            $customData['transaction_id'] = $event->getOrderId();
        }

        if ($event->getSearchTerm()) {
            $customData['search_string'] = $event->getSearchTerm();
        }

        // Build event data
        $eventData = [
            'event_name' => $this->mapEventName($event->getType()),
            'event_conversion_type' => 'WEB',
            'event_tag' => 'event',
            'timestamp' => $event->getOccurredAt()->getTimestamp() * 1000, // milliseconds
            'hashed_email' => $userData['em'] ?? null,
            'hashed_phone_number' => $userData['ph'] ?? null,
            'user_agent' => $userData['client_user_agent'] ?? null,
            'ip_address' => $userData['client_ip_address'] ?? null,
        ];

        if ($event->getPageUrl()) {
            $eventData['page_url'] = $event->getPageUrl()->toString();
        }

        if (!empty($customData)) {
            $eventData['custom_data'] = $customData;
        }

        return [
            'data' => [$eventData],
        ];
    }

    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        assert($credentials instanceof SnapchatCredentials);

        return "https://tr.snapchat.com/v3/{$credentials->pixelId}/events"
            . "?access_token={$credentials->accessToken}";
    }

    protected function getHeaders(PlatformCredentialsInterface $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }
}
