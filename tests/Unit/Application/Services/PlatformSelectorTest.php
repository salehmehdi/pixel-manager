<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Application\Services;

use MehdiyevSignal\PixelManager\Application\Services\PlatformSelector;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Money;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\Currency;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class PlatformSelectorTest extends TestCase
{
    private PlatformSelector $selector;
    private array $eventMappings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventMappings = [
            'purchase' => ['meta', 'google', 'tiktok', 'brevo'],
            'add_to_cart' => ['meta', 'google'],
            'view_item' => ['meta'],
            'page_view' => ['google'],
        ];

        $this->selector = new PlatformSelector($this->eventMappings);
    }

    public function test_selects_correct_platforms_for_purchase_event(): void
    {
        $event = $this->createEvent(EventType::PURCHASE);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            'google_measurement_id' => 'G-XXX',
            'google_api_secret' => 'secret',
            'tiktok_pixel_code' => 'ABC',
            'tiktok_access_token' => 'tk_token',
            'brevo_api_key' => 'brevo_key',
        ]);

        $platforms = $this->selector->selectPlatforms($event, $credentials);

        $this->assertCount(4, $platforms);
        $this->assertContains(PlatformType::META, $platforms);
        $this->assertContains(PlatformType::GOOGLE, $platforms);
        $this->assertContains(PlatformType::TIKTOK, $platforms);
        $this->assertContains(PlatformType::BREVO, $platforms);
    }

    public function test_selects_correct_platforms_for_add_to_cart_event(): void
    {
        $event = $this->createEvent(EventType::ADD_TO_CART);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            'google_measurement_id' => 'G-XXX',
            'google_api_secret' => 'secret',
        ]);

        $platforms = $this->selector->selectPlatforms($event, $credentials);

        $this->assertCount(2, $platforms);
        $this->assertContains(PlatformType::META, $platforms);
        $this->assertContains(PlatformType::GOOGLE, $platforms);
    }

    public function test_only_returns_platforms_with_configured_credentials(): void
    {
        $event = $this->createEvent(EventType::PURCHASE);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            // Google and TikTok credentials missing
        ]);

        $platforms = $this->selector->selectPlatforms($event, $credentials);

        // Only Meta should be returned because it's the only one configured
        $this->assertCount(1, $platforms);
        $this->assertContains(PlatformType::META, $platforms);
        $this->assertNotContains(PlatformType::GOOGLE, $platforms);
        $this->assertNotContains(PlatformType::TIKTOK, $platforms);
    }

    public function test_returns_empty_array_for_unmapped_event(): void
    {
        $event = $this->createEvent(EventType::BEGIN_CHECKOUT);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
        ]);

        $platforms = $this->selector->selectPlatforms($event, $credentials);

        $this->assertCount(0, $platforms);
        $this->assertIsArray($platforms);
    }

    public function test_returns_empty_array_when_no_credentials_configured(): void
    {
        $event = $this->createEvent(EventType::PURCHASE);
        $credentials = $this->createCredentials([]);

        $platforms = $this->selector->selectPlatforms($event, $credentials);

        $this->assertCount(0, $platforms);
    }

    public function test_handles_wildcard_event_mapping(): void
    {
        $selector = new PlatformSelector([
            '*' => ['meta', 'google'], // All events go to Meta and Google
        ]);

        $event = $this->createEvent(EventType::PURCHASE);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            'google_measurement_id' => 'G-XXX',
            'google_api_secret' => 'secret',
        ]);

        $platforms = $selector->selectPlatforms($event, $credentials);

        $this->assertCount(2, $platforms);
        $this->assertContains(PlatformType::META, $platforms);
        $this->assertContains(PlatformType::GOOGLE, $platforms);
    }

    public function test_specific_mapping_overrides_wildcard(): void
    {
        $selector = new PlatformSelector([
            '*' => ['meta', 'google'],
            'purchase' => ['meta'], // Purchase only goes to Meta
        ]);

        $purchaseEvent = $this->createEvent(EventType::PURCHASE);
        $addToCartEvent = $this->createEvent(EventType::ADD_TO_CART);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            'google_measurement_id' => 'G-XXX',
            'google_api_secret' => 'secret',
        ]);

        $purchasePlatforms = $selector->selectPlatforms($purchaseEvent, $credentials);
        $addToCartPlatforms = $selector->selectPlatforms($addToCartEvent, $credentials);

        // Purchase should only go to Meta
        $this->assertCount(1, $purchasePlatforms);
        $this->assertContains(PlatformType::META, $purchasePlatforms);

        // Add to cart should go to both (wildcard)
        $this->assertCount(2, $addToCartPlatforms);
        $this->assertContains(PlatformType::META, $addToCartPlatforms);
        $this->assertContains(PlatformType::GOOGLE, $addToCartPlatforms);
    }

    public function test_filters_out_invalid_platform_names(): void
    {
        $selector = new PlatformSelector([
            'purchase' => ['meta', 'invalid_platform', 'google'],
        ]);

        $event = $this->createEvent(EventType::PURCHASE);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            'google_measurement_id' => 'G-XXX',
            'google_api_secret' => 'secret',
        ]);

        $platforms = $selector->selectPlatforms($event, $credentials);

        // Should only include valid platforms
        $this->assertCount(2, $platforms);
        $this->assertContains(PlatformType::META, $platforms);
        $this->assertContains(PlatformType::GOOGLE, $platforms);
    }

    public function test_returns_platforms_in_consistent_order(): void
    {
        $event = $this->createEvent(EventType::PURCHASE);
        $credentials = $this->createCredentials([
            'meta_pixel_id' => '123',
            'meta_access_token' => 'token',
            'google_measurement_id' => 'G-XXX',
            'google_api_secret' => 'secret',
        ]);

        $platforms1 = $this->selector->selectPlatforms($event, $credentials);
        $platforms2 = $this->selector->selectPlatforms($event, $credentials);

        $this->assertEquals($platforms1, $platforms2);
    }

    private function createEvent(EventType $eventType): PixelEvent
    {
        return new PixelEvent(
            id: EventId::generate(),
            eventType: $eventType,
            value: new Money(99.99, Currency::USD),
            appId: 40
        );
    }

    private function createCredentials(array $data): ApplicationCredentials
    {
        return new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: $data
        );
    }
}
