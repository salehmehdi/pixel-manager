<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Feature;

use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

final class TrackPixelEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the credentials repository to return test credentials
        $this->mockCredentials();
    }

    public function test_can_track_purchase_event(): void
    {
        Queue::fake();

        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'transaction_id' => 'TXN123456',
                'order_id' => 'ORD789',
                'customer' => [
                    'email' => 'customer@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ]
        ]);

        // Assert that jobs were dispatched
        Queue::assertPushed(\MehdiyevSignal\PixelManager\Infrastructure\Queue\SendPixelEventJob::class);
    }

    public function test_can_track_add_to_cart_event(): void
    {
        Queue::fake();

        PixelManager::track([
            'data' => [
                'event_type' => 'add_to_cart',
                'value' => 49.99,
                'currency' => 'USD',
                'customer' => [
                    'email' => 'customer@example.com',
                ],
                'items' => [
                    [
                        'item_id' => 'PROD123',
                        'item_name' => 'Premium Widget',
                        'price' => 49.99,
                        'quantity' => 1,
                    ]
                ]
            ]
        ]);

        Queue::assertPushed(\MehdiyevSignal\PixelManager\Infrastructure\Queue\SendPixelEventJob::class);
    }

    public function test_can_track_event_with_azerbaijani_currency(): void
    {
        Queue::fake();

        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'value' => 169.98,
                'currency' => 'AZN',
                'customer' => [
                    'email' => 'customer@example.com',
                ],
            ]
        ]);

        Queue::assertPushed(\MehdiyevSignal\PixelManager\Infrastructure\Queue\SendPixelEventJob::class);
    }

    public function test_distributes_to_correct_platforms_based_on_mapping(): void
    {
        Queue::fake();

        // Configure event mapping for purchase to go to Meta and Google
        config(['pixel-manager.event_mappings.purchase' => ['meta', 'google']]);

        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
            ]
        ]);

        // Should dispatch 2 jobs (one for Meta, one for Google)
        Queue::assertPushed(\MehdiyevSignal\PixelManager\Infrastructure\Queue\SendPixelEventJob::class, 2);
    }

    public function test_can_check_if_platform_is_enabled(): void
    {
        $this->assertTrue(PixelManager::isPlatformEnabled('meta'));
        $this->assertTrue(PixelManager::isPlatformEnabled('google'));
    }

    public function test_can_get_all_platforms(): void
    {
        $platforms = PixelManager::platforms();

        $this->assertIsArray($platforms);
        $this->assertContains('meta', $platforms);
        $this->assertContains('google', $platforms);
        $this->assertContains('tiktok', $platforms);
        $this->assertContains('brevo', $platforms);
        $this->assertContains('pinterest', $platforms);
        $this->assertContains('snapchat', $platforms);
    }

    public function test_handles_missing_required_fields_gracefully(): void
    {
        $this->expectException(\MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidEventDataException::class);

        PixelManager::track([
            'data' => [
                // Missing event_type
                'value' => 99.99,
                'currency' => 'USD',
            ]
        ]);
    }

    public function test_handles_invalid_currency_gracefully(): void
    {
        $this->expectException(\MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidCurrencyException::class);

        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'INVALID',
            ]
        ]);
    }

    public function test_handles_invalid_email_gracefully(): void
    {
        $this->expectException(\MehdiyevSignal\PixelManager\Domain\Exceptions\InvalidEmailException::class);

        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
                'customer' => [
                    'email' => 'not-a-valid-email',
                ],
            ]
        ]);
    }

    public function test_complete_purchase_flow_with_full_data(): void
    {
        Queue::fake();

        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'event' => 'purchase',
                'transaction_id' => 'TXN123456',
                'order_id' => 'ORD789',
                'value' => 149.98,
                'currency' => 'USD',
                'shipping' => 10.00,
                'tax' => 12.00,
                'customer' => [
                    'email' => 'customer@example.com',
                    'external_id' => 'USER12345',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'phone' => '+1234567890',
                    'city' => 'New York',
                    'state' => 'NY',
                    'country_code' => 'US',
                    'zip_code' => '10001',
                ],
                'items' => [
                    [
                        'item_id' => 'PROD123',
                        'item_name' => 'Premium Widget',
                        'price' => 69.99,
                        'quantity' => 1,
                        'category' => 'Electronics',
                        'item_brand' => 'BrandName',
                    ],
                    [
                        'item_id' => 'PROD456',
                        'item_name' => 'Standard Widget',
                        'price' => 79.99,
                        'quantity' => 1,
                        'category' => 'Electronics',
                        'item_brand' => 'BrandName',
                    ]
                ]
            ]
        ]);

        Queue::assertPushed(\MehdiyevSignal\PixelManager\Infrastructure\Queue\SendPixelEventJob::class);
    }

    private function mockCredentials(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...test_token',
                'google_measurement_id' => 'G-XXXXXXXXX',
                'google_api_secret' => 'test_secret',
                'tiktok_pixel_code' => 'ABC123',
                'tiktok_access_token' => 'tk_test_token',
                'brevo_api_key' => 'xkeysib-test',
                'pinterest_account_id' => '123456',
                'pinterest_access_token' => 'pina_test',
                'snapchat_pixel_id' => 'snap-123',
                'snapchat_access_token' => 'snap_test',
            ]
        );

        $mock = \Mockery::mock(CredentialsRepositoryInterface::class);
        $mock->shouldReceive('findByApplicationId')
            ->andReturn($credentials);

        $this->app->instance(CredentialsRepositoryInterface::class, $mock);
    }
}
