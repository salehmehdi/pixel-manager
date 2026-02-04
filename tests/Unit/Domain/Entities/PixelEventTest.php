<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\Entities;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\CustomerData;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Email;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class PixelEventTest extends TestCase
{
    public function test_can_create_pixel_event_with_required_fields(): void
    {
        $eventId = EventId::generate();
        $eventType = EventType::PURCHASE;
        $value = new Money(99.99, Currency::USD);

        $event = new PixelEvent(
            id: $eventId,
            eventType: $eventType,
            value: $value,
            appId: 40
        );

        $this->assertSame($eventId, $event->getId());
        $this->assertSame($eventType, $event->getEventType());
        $this->assertSame($value, $event->getValue());
        $this->assertEquals(40, $event->getAppId());
    }

    public function test_can_create_pixel_event_with_customer_data(): void
    {
        $eventId = EventId::generate();
        $eventType = EventType::PURCHASE;
        $value = new Money(99.99, Currency::USD);
        $customer = new CustomerData(
            email: new Email('test@example.com')
        );

        $event = new PixelEvent(
            id: $eventId,
            eventType: $eventType,
            value: $value,
            appId: 40,
            customer: $customer
        );

        $this->assertSame($customer, $event->getCustomer());
        $this->assertNotNull($event->getCustomer());
    }

    public function test_can_create_event_without_customer_data(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PAGE_VIEW,
            value: new Money(0, Currency::USD),
            appId: 40
        );

        $this->assertNull($event->getCustomer());
    }

    public function test_can_set_transaction_id(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            transactionId: 'TXN123456'
        );

        $this->assertEquals('TXN123456', $event->getTransactionId());
    }

    public function test_can_set_order_id(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            orderId: 'ORD789'
        );

        $this->assertEquals('ORD789', $event->getOrderId());
    }

    public function test_can_add_items_to_event(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            items: [
                [
                    'item_id' => 'PROD123',
                    'item_name' => 'Premium Widget',
                    'price' => 49.99,
                    'quantity' => 2,
                ]
            ]
        );

        $items = $event->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('PROD123', $items[0]['item_id']);
        $this->assertEquals('Premium Widget', $items[0]['item_name']);
    }

    public function test_can_set_user_agent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            userAgent: $userAgent
        );

        $this->assertEquals($userAgent, $event->getUserAgent());
    }

    public function test_can_set_ip_address(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            ipAddress: '192.168.1.1'
        );

        $this->assertEquals('192.168.1.1', $event->getIpAddress());
    }

    public function test_can_set_custom_properties(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            customProperties: [
                'campaign_id' => 'CAMP123',
                'source' => 'facebook',
            ]
        );

        $this->assertTrue($event->hasCustomProperty('campaign_id'));
        $this->assertEquals('CAMP123', $event->getCustomProperty('campaign_id'));
        $this->assertFalse($event->hasCustomProperty('nonexistent'));
    }

    public function test_returns_null_for_nonexistent_custom_property(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );

        $this->assertNull($event->getCustomProperty('nonexistent'));
    }

    public function test_event_types_are_correctly_set(): void
    {
        $purchaseEvent = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );

        $addToCartEvent = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::ADD_TO_CART,
            value: new Money(49.99, Currency::USD),
            appId: 40
        );

        $this->assertEquals(EventType::PURCHASE, $purchaseEvent->getEventType());
        $this->assertEquals(EventType::ADD_TO_CART, $addToCartEvent->getEventType());
        $this->assertNotEquals($purchaseEvent->getEventType(), $addToCartEvent->getEventType());
    }

    public function test_different_currencies_are_supported(): void
    {
        $usdEvent = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );

        $aznEvent = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(169.98, Currency::AZN),
            appId: 40
        );

        $this->assertEquals(Currency::USD, $usdEvent->getValue()->currency);
        $this->assertEquals(Currency::AZN, $aznEvent->getValue()->currency);
    }

    public function test_event_id_is_unique(): void
    {
        $event1 = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );

        $event2 = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );

        $this->assertNotEquals(
            $event1->getId()->toString(),
            $event2->getId()->toString()
        );
    }

    public function test_can_set_event_source_url(): void
    {
        $sourceUrl = 'https://example.com/checkout';
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            sourceUrl: $sourceUrl
        );

        $this->assertEquals($sourceUrl, $event->getSourceUrl());
    }

    public function test_can_set_event_timestamp(): void
    {
        $timestamp = time();
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            timestamp: $timestamp
        );

        $this->assertEquals($timestamp, $event->getTimestamp());
    }
}
