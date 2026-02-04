<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\GoogleCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Google Analytics 4 adapter.
 *
 * BUG FIX: Fixed undefined $response variable (original line 23).
 */
final class GooglePlatformAdapter extends AbstractHttpPlatformAdapter
{
    public function getPlatformType(): PlatformType
    {
        return PlatformType::GOOGLE;
    }

    public function supports(EventType $eventType): bool
    {
        return true; // GA4 supports all event types
    }

    public function mapEventName(EventType $type): ?string
    {
        return match ($type) {
            EventType::PURCHASE => 'purchase',
            EventType::ADD_TO_CART => 'add_to_cart',
            EventType::VIEW_ITEM => 'view_item',
            EventType::BEGIN_CHECKOUT => 'begin_checkout',
            EventType::VIEW_CART => 'view_cart',
            EventType::ADD_PAYMENT_INFO => 'add_payment_info',
            EventType::ADD_TO_WISHLIST => 'add_to_wishlist',
            EventType::COMPLETED_REGISTRATION => 'sign_up',
            EventType::PAGE_VIEW => 'page_view',
            EventType::SEARCH => 'search',
            EventType::SUBSCRIPTION => 'purchase',
            default => $type->value,
        };
    }

    /**
     * BUG FIX: Properly handle errors with defined $response variable.
     * Original code had undefined $response at line 23.
     */
    public function sendEvent(PixelEvent $event, PlatformCredentialsInterface $credentials): PlatformResponse
    {
        try {
            $payload = $this->buildPayload($event, $credentials);
            $url = $this->getEndpointUrl($credentials);

            $response = $this->http
                ->timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->post($url, $payload);

            // Google Analytics returns 204 No Content on success
            if ($response->successful()) {
                return PlatformResponse::success();
            }

            $error = "Google Analytics error: {$response->body()}";

            $this->logger->warning('Google Analytics 4 Event Error', [
                'status' => $response->status(),
                'error' => $error,
                'event_id' => $event->getId()->toString(),
            ]);

            return PlatformResponse::failure($error, $response->json());

        } catch (\Exception $e) {
            $this->logger->error('Google Analytics 4 Exception', [
                'error' => $e->getMessage(),
                'event_id' => $event->getId()->toString(),
            ]);

            return PlatformResponse::failure($e->getMessage());
        }
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        $customer = $event->getCustomer();

        // Build event parameters
        $params = [];

        if ($event->getValue()) {
            $params['currency'] = $event->getValue()->currencyCode();
            $params['value'] = $event->getValue()->amount;
        }

        if ($event->getTransactionId()) {
            $params['transaction_id'] = $event->getTransactionId();
        }

        if ($event->getShipping()) {
            $params['shipping'] = $event->getShipping();
        }

        if ($event->getSearchTerm()) {
            $params['search_term'] = $event->getSearchTerm();
        }

        if ($event->hasItems()) {
            $params['items'] = array_map(function ($item) {
                return [
                    'item_id' => $item['item_id'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'item_category' => $item['category'] ?? null,
                    'item_brand' => $item['brand'] ?? null,
                ];
            }, $event->getItems());
        }

        if ($event->getPageUrl()) {
            $params['page_location'] = $event->getPageUrl()->toString();
        }

        return [
            'client_id' => $customer->externalId ?? 'anonymous',
            'user_id' => $customer->externalId,
            'events' => [
                [
                    'name' => $this->mapEventName($event->getType()),
                    'params' => $params,
                ],
            ],
        ];
    }

    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        assert($credentials instanceof GoogleCredentials);

        return "https://www.google-analytics.com/mp/collect"
            . "?measurement_id={$credentials->measurementId}"
            . "&api_secret={$credentials->apiSecret}";
    }

    protected function getHeaders(PlatformCredentialsInterface $credentials): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }
}
