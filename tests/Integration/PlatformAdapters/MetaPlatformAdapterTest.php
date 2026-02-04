<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Integration\PlatformAdapters;

use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\MetaPlatformAdapter;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\CustomerData;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Email;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Phone;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\MetaCredentials;
use MehdiyevSignal\PixelManager\Tests\TestCase;
use Mockery;

final class MetaPlatformAdapterTest extends TestCase
{
    private MetaPlatformAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create adapter with mocked dependencies if needed
        $this->adapter = new MetaPlatformAdapter(
            logger: $this->createMock(\Psr\Log\LoggerInterface::class)
        );
    }

    public function test_builds_correct_payload_for_purchase_event(): void
    {
        $customer = new CustomerData(
            email: new Email('test@example.com'),
            firstName: 'John',
            lastName: 'Doe',
            phone: new Phone('+1234567890', 'US'),
            city: 'New York',
            state: 'NY',
            countryCode: 'US',
            zipCode: '10001'
        );

        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            customer: $customer,
            transactionId: 'TXN123',
            sourceUrl: 'https://example.com/checkout'
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        // Verify payload structure
        $this->assertArrayHasKey('event_name', $payload);
        $this->assertEquals('Purchase', $payload['event_name']);

        $this->assertArrayHasKey('event_time', $payload);
        $this->assertIsInt($payload['event_time']);

        $this->assertArrayHasKey('user_data', $payload);
        $this->assertIsArray($payload['user_data']);

        $this->assertArrayHasKey('custom_data', $payload);
        $this->assertIsArray($payload['custom_data']);
    }

    public function test_hashes_user_data_with_sha256(): void
    {
        $email = 'test@example.com';
        $customer = new CustomerData(
            email: new Email($email)
        );

        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            customer: $customer
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        // Verify email is hashed
        $expectedHash = hash('sha256', strtolower(trim($email)));
        $this->assertEquals($expectedHash, $payload['user_data']['em']);
    }

    public function test_maps_purchase_event_correctly(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        $this->assertEquals('Purchase', $payload['event_name']);
    }

    public function test_maps_add_to_cart_event_correctly(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::ADD_TO_CART,
            value: new Money(49.99, Currency::USD),
            appId: 40
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        $this->assertEquals('AddToCart', $payload['event_name']);
    }

    public function test_includes_custom_data_values(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            transactionId: 'TXN123'
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        $this->assertEquals(99.99, $payload['custom_data']['value']);
        $this->assertEquals('USD', $payload['custom_data']['currency']);
    }

    public function test_includes_items_in_custom_data(): void
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

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        $this->assertArrayHasKey('contents', $payload['custom_data']);
        $this->assertIsArray($payload['custom_data']['contents']);
        $this->assertCount(1, $payload['custom_data']['contents']);
    }

    public function test_includes_event_source_url(): void
    {
        $sourceUrl = 'https://example.com/checkout';
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(99.99, Currency::USD),
            appId: 40,
            sourceUrl: $sourceUrl
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        $this->assertEquals($sourceUrl, $payload['event_source_url']);
    }

    public function test_handles_event_without_customer_data(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PAGE_VIEW,
            value: new Money(0, Currency::USD),
            appId: 40
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        // Should still have user_data key, but might be empty or minimal
        $this->assertArrayHasKey('user_data', $payload);
    }

    public function test_supports_azerbaijani_currency(): void
    {
        $event = new PixelEvent(
            id: EventId::generate(),
            eventType: EventType::PURCHASE,
            value: new Money(169.98, Currency::AZN),
            appId: 40
        );

        $credentials = new MetaCredentials(
            pixelId: '123456789',
            accessToken: 'EAA...token'
        );

        $payload = $this->adapter->buildPayload($event, $credentials);

        $this->assertEquals('AZN', $payload['custom_data']['currency']);
        $this->assertEquals(169.98, $payload['custom_data']['value']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
