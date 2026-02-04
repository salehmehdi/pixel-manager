<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PinterestEnvironment;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\PinterestCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Pinterest Tag adapter.
 *
 * BUG FIX 1: Fixed opt_out being set to event_name (original line 126).
 * BUG FIX 2: No longer hardcoded to sandbox - respects environment config.
 */
final class PinterestPlatformAdapter extends AbstractHttpPlatformAdapter
{
    public function getPlatformType(): PlatformType
    {
        return PlatformType::PINTEREST;
    }

    public function supports(EventType $eventType): bool
    {
        return $eventType !== EventType::CUSTOMIZE_PRODUCT;
    }

    public function mapEventName(EventType $type): ?string
    {
        return match ($type) {
            EventType::PURCHASE => 'checkout',
            EventType::ADD_TO_CART => 'add_to_cart',
            EventType::VIEW_ITEM, EventType::PAGE_VIEW => 'page_visit',
            EventType::SEARCH => 'search',
            EventType::COMPLETED_REGISTRATION => 'signup',
            default => null,
        };
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        $customer = $event->getCustomer();
        $userData = [];

        // Email (SHA256 hashed)
        if ($customer->email) {
            $userData['em'] = [$customer->email->hash()];
        }

        // Phone (SHA256 hashed)
        if ($customer->phone) {
            $userData['ph'] = [$customer->phone->hash()];
        }

        // Gender
        if ($customer->gender) {
            $userData['ge'] = [hash('sha256', strtolower($customer->gender))];
        }

        // Date of Birth
        if ($customer->dateOfBirth) {
            $userData['db'] = [hash('sha256', $customer->dateOfBirth->format('Ymd'))];
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
            $customData['value'] = (string) $event->getValue()->amount;
            $customData['currency'] = $event->getValue()->currencyCode();
        }

        if ($event->hasItems()) {
            $customData['content_ids'] = array_map(
                fn($item) => $item['item_id'] ?? '',
                $event->getItems()
            );

            $customData['num_items'] = count($event->getItems());
        }

        if ($event->getOrderId()) {
            $customData['order_id'] = $event->getOrderId();
        }

        if ($event->getSearchTerm()) {
            $customData['search_string'] = $event->getSearchTerm();
        }

        // Build event data
        $eventData = [
            'event_name' => $this->mapEventName($event->getType()),
            'event_id' => $event->getId()->toString(),
            'event_time' => $event->getOccurredAt()->getTimestamp(),
            'action_source' => 'web',
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        if ($event->getPageUrl()) {
            $eventData['event_source_url'] = $event->getPageUrl()->toString();
        }

        // BUG FIX: Was setting opt_out to event_name (original line 126)
        // NOW FIXED: Correctly checking and setting opt_out
        if ($event->hasCustomProperty('opt_out')) {
            $eventData['opt_out'] = (bool) $event->getCustomProperty('opt_out');
        }

        return [
            'data' => [$eventData],
        ];
    }

    /**
     * BUG FIX: No longer hardcoded to sandbox.
     * Now respects PinterestEnvironment from credentials.
     */
    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        assert($credentials instanceof PinterestCredentials);

        $baseUrl = $credentials->environment->baseUrl();
        $testParam = $credentials->environment === PinterestEnvironment::SANDBOX ? '?test=true' : '';

        return "{$baseUrl}/v5/ad_accounts/{$credentials->accountId}/events{$testParam}";
    }

    protected function getHeaders(PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof PinterestCredentials);

        return [
            'Authorization' => "Bearer {$credentials->accessToken}",
            'Content-Type' => 'application/json',
        ];
    }
}
