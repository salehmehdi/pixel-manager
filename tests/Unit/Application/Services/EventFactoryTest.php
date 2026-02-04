<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Application\Services;

use MehdiyevSignal\PixelManager\Application\Services\EventFactory;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidEventDataException;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class EventFactoryTest extends TestCase
{
    private EventFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EventFactory();
    }

    public function test_can_create_event_from_array_data(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'transaction_id' => 'TXN123',
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertInstanceOf(PixelEvent::class, $event);
        $this->assertEquals(EventType::PURCHASE, $event->getEventType());
        $this->assertEquals(99.99, $event->getValue()->amount);
        $this->assertEquals(Currency::USD, $event->getValue()->currency);
        $this->assertEquals('TXN123', $event->getTransactionId());
    }

    public function test_can_create_event_with_customer_data(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'customer' => [
                    'email' => 'test@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'phone' => '+1234567890',
                ],
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $customer = $event->getCustomer();
        $this->assertNotNull($customer);
        $this->assertEquals('test@example.com', $customer->email->value());
        $this->assertEquals('John', $customer->firstName);
        $this->assertEquals('Doe', $customer->lastName);
        $this->assertEquals('+1234567890', $customer->phone->value());
    }

    public function test_can_create_event_with_items(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'items' => [
                    [
                        'item_id' => 'PROD123',
                        'item_name' => 'Premium Widget',
                        'price' => 49.99,
                        'quantity' => 2,
                    ],
                    [
                        'item_id' => 'PROD456',
                        'item_name' => 'Standard Widget',
                        'price' => 29.99,
                        'quantity' => 1,
                    ],
                ],
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $items = $event->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('PROD123', $items[0]['item_id']);
        $this->assertEquals('PROD456', $items[1]['item_id']);
    }

    public function test_throws_exception_for_missing_event_type(): void
    {
        $data = [
            'data' => [
                'value' => 99.99,
                'currency' => 'USD',
            ]
        ];

        $this->expectException(InvalidEventDataException::class);
        $this->expectExceptionMessage('Event type is required');

        $this->factory->createFromArray($data, 40);
    }

    public function test_throws_exception_for_invalid_event_type(): void
    {
        $data = [
            'data' => [
                'event_type' => 'invalid_event',
                'value' => 99.99,
                'currency' => 'USD',
            ]
        ];

        $this->expectException(InvalidEventDataException::class);
        $this->expectExceptionMessage('Invalid event type: invalid_event');

        $this->factory->createFromArray($data, 40);
    }

    public function test_throws_exception_for_missing_value(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'currency' => 'USD',
            ]
        ];

        $this->expectException(InvalidEventDataException::class);
        $this->expectExceptionMessage('Event value is required');

        $this->factory->createFromArray($data, 40);
    }

    public function test_throws_exception_for_missing_currency(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
            ]
        ];

        $this->expectException(InvalidEventDataException::class);
        $this->expectExceptionMessage('Currency is required');

        $this->factory->createFromArray($data, 40);
    }

    public function test_can_create_add_to_cart_event(): void
    {
        $data = [
            'data' => [
                'event_type' => 'add_to_cart',
                'value' => 49.99,
                'currency' => 'USD',
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertEquals(EventType::ADD_TO_CART, $event->getEventType());
    }

    public function test_can_create_view_item_event(): void
    {
        $data = [
            'data' => [
                'event_type' => 'view_item',
                'value' => 29.99,
                'currency' => 'USD',
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertEquals(EventType::VIEW_ITEM, $event->getEventType());
    }

    public function test_can_create_event_with_azn_currency(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 169.98,
                'currency' => 'AZN',
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertEquals(Currency::AZN, $event->getValue()->currency);
        $this->assertEquals(169.98, $event->getValue()->amount);
    }

    public function test_sets_user_agent_from_data(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'user_agent' => $userAgent,
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertEquals($userAgent, $event->getUserAgent());
    }

    public function test_sets_ip_address_from_data(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'ip_address' => '192.168.1.1',
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertEquals('192.168.1.1', $event->getIpAddress());
    }

    public function test_sets_source_url_from_data(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'source_url' => 'https://example.com/checkout',
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertEquals('https://example.com/checkout', $event->getSourceUrl());
    }

    public function test_sets_custom_properties_from_data(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'custom_properties' => [
                    'campaign_id' => 'CAMP123',
                    'source' => 'facebook',
                ],
            ]
        ];

        $event = $this->factory->createFromArray($data, 40);

        $this->assertTrue($event->hasCustomProperty('campaign_id'));
        $this->assertEquals('CAMP123', $event->getCustomProperty('campaign_id'));
    }

    public function test_generates_unique_event_id_for_each_event(): void
    {
        $data = [
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
            ]
        ];

        $event1 = $this->factory->createFromArray($data, 40);
        $event2 = $this->factory->createFromArray($data, 40);

        $this->assertNotEquals(
            $event1->getId()->toString(),
            $event2->getId()->toString()
        );
    }
}
