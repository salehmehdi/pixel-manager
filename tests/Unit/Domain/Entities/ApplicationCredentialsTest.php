<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\Entities;

use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\MetaCredentials;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\GoogleCredentials;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class ApplicationCredentialsTest extends TestCase
{
    public function test_can_create_application_credentials(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...token',
                'google_measurement_id' => 'G-XXXXXXXXX',
                'google_api_secret' => 'secret123',
            ]
        );

        $this->assertEquals(40, $credentials->getAppId());
        $this->assertEquals('customer_event', $credentials->getCategory());
    }

    public function test_can_get_meta_credentials(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...token',
            ]
        );

        $metaCredentials = $credentials->getCredentialsFor(PlatformType::META);

        $this->assertInstanceOf(MetaCredentials::class, $metaCredentials);
        $this->assertEquals('123456789', $metaCredentials->pixelId);
        $this->assertEquals('EAA...token', $metaCredentials->accessToken);
    }

    public function test_can_get_google_credentials(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'google_measurement_id' => 'G-XXXXXXXXX',
                'google_api_secret' => 'secret123',
            ]
        );

        $googleCredentials = $credentials->getCredentialsFor(PlatformType::GOOGLE);

        $this->assertInstanceOf(GoogleCredentials::class, $googleCredentials);
        $this->assertEquals('G-XXXXXXXXX', $googleCredentials->measurementId);
        $this->assertEquals('secret123', $googleCredentials->apiSecret);
    }

    public function test_returns_null_for_missing_platform_credentials(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...token',
            ]
        );

        $googleCredentials = $credentials->getCredentialsFor(PlatformType::GOOGLE);

        $this->assertNull($googleCredentials);
    }

    public function test_can_check_if_platform_is_configured(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...token',
                'google_measurement_id' => 'G-XXXXXXXXX',
                'google_api_secret' => 'secret123',
            ]
        );

        $this->assertTrue($credentials->hasPlatform(PlatformType::META));
        $this->assertTrue($credentials->hasPlatform(PlatformType::GOOGLE));
        $this->assertFalse($credentials->hasPlatform(PlatformType::TIKTOK));
    }

    public function test_can_get_all_configured_platforms(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...token',
                'google_measurement_id' => 'G-XXXXXXXXX',
                'google_api_secret' => 'secret123',
                'tiktok_pixel_code' => 'ABC123',
                'tiktok_access_token' => 'tk_token',
            ]
        );

        $platforms = $credentials->getConfiguredPlatforms();

        $this->assertCount(3, $platforms);
        $this->assertContains(PlatformType::META, $platforms);
        $this->assertContains(PlatformType::GOOGLE, $platforms);
        $this->assertContains(PlatformType::TIKTOK, $platforms);
    }

    public function test_returns_empty_array_when_no_platforms_configured(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: []
        );

        $platforms = $credentials->getConfiguredPlatforms();

        $this->assertCount(0, $platforms);
        $this->assertIsArray($platforms);
    }

    public function test_can_update_credentials_data(): void
    {
        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: [
                'meta_pixel_id' => '123456789',
                'meta_access_token' => 'EAA...token',
            ]
        );

        $credentials->updateData([
            'meta_pixel_id' => '987654321',
            'meta_access_token' => 'EAA...newtoken',
            'google_measurement_id' => 'G-YYYYYYYYY',
            'google_api_secret' => 'newsecret456',
        ]);

        $metaCredentials = $credentials->getCredentialsFor(PlatformType::META);
        $googleCredentials = $credentials->getCredentialsFor(PlatformType::GOOGLE);

        $this->assertEquals('987654321', $metaCredentials->pixelId);
        $this->assertEquals('EAA...newtoken', $metaCredentials->accessToken);
        $this->assertNotNull($googleCredentials);
    }

    public function test_different_app_ids_create_different_credentials(): void
    {
        $credentials1 = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: ['meta_pixel_id' => '123']
        );

        $credentials2 = new ApplicationCredentials(
            appId: 41,
            category: 'customer_event',
            data: ['meta_pixel_id' => '123']
        );

        $this->assertNotEquals($credentials1->getAppId(), $credentials2->getAppId());
    }

    public function test_can_get_raw_data(): void
    {
        $data = [
            'meta_pixel_id' => '123456789',
            'meta_access_token' => 'EAA...token',
            'custom_field' => 'custom_value',
        ];

        $credentials = new ApplicationCredentials(
            appId: 40,
            category: 'customer_event',
            data: $data
        );

        $rawData = $credentials->getData();

        $this->assertEquals($data, $rawData);
        $this->assertArrayHasKey('custom_field', $rawData);
        $this->assertEquals('custom_value', $rawData['custom_field']);
    }
}
