# 🔧 Override & Customization Guide

This guide shows you how to override and customize Pixel Manager without modifying the package source code.

## 📋 Table of Contents

1. [Override Service Bindings](#override-service-bindings)
2. [Extend Platform Adapters](#extend-platform-adapters)
3. [Custom Repositories](#custom-repositories)
4. [Add Custom Decorators](#add-custom-decorators)
5. [Override Config](#override-config)
6. [Extend Domain Entities](#extend-domain-entities)
7. [Complete Examples](#complete-examples)

---

## 🎯 Override Service Bindings

The easiest way to customize the package is to override service bindings in your own Service Provider.

### Example: Custom Bot Detector

**1. Create your custom implementation:**

```php
<?php

namespace App\Services\PixelManager;

use MehdiyevSignal\PixelManager\Domain\Services\BotDetectorInterface;

class CustomBotDetector implements BotDetectorInterface
{
    private const CUSTOM_BOT_PATTERNS = [
        'mybot',
        'customcrawler',
        'internalbot',
    ];

    public function isBot(?string $userAgent = null): bool
    {
        $ua = $userAgent ?? request()->userAgent();

        if (empty($ua)) {
            return false;
        }

        $ua = strtolower($ua);

        // Add your custom logic
        foreach (self::CUSTOM_BOT_PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        // Check IP-based detection
        if ($this->isBlockedIp(request()->ip())) {
            return true;
        }

        return false;
    }

    private function isBlockedIp(?string $ip): bool
    {
        $blockedIps = config('pixel-manager.blocked_ips', []);
        return in_array($ip, $blockedIps);
    }
}
```

**2. Override in your AppServiceProvider:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MehdiyevSignal\PixelManager\Domain\Services\BotDetectorInterface;
use App\Services\PixelManager\CustomBotDetector;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override the BotDetector
        $this->app->bind(
            BotDetectorInterface::class,
            CustomBotDetector::class
        );
    }
}
```

**Done!** Your custom bot detector will now be used automatically.

---

## 🚀 Extend Platform Adapters

You can extend existing platform adapters to add custom behavior.

### Example: Extended Meta Adapter with Custom Fields

```php
<?php

namespace App\Services\PixelManager\Adapters;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\MetaPlatformAdapter;

class ExtendedMetaPlatformAdapter extends MetaPlatformAdapter
{
    protected function buildPayload(
        PixelEvent $event,
        PlatformCredentialsInterface $credentials
    ): array {
        // Get base payload from parent
        $payload = parent::buildPayload($event, $credentials);

        // Add custom fields
        $payload['data'][0]['custom_data']['source'] = 'my-app';
        $payload['data'][0]['custom_data']['campaign_id'] = $event->getCustomProperty('campaign_id');

        // Add custom user properties
        if ($event->hasCustomProperty('customer_tier')) {
            $payload['data'][0]['user_data']['tier'] = $event->getCustomProperty('customer_tier');
        }

        return $payload;
    }
}
```

**Register in PlatformAdapterFactory:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Factories\PlatformAdapterFactory;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use App\Services\PixelManager\Adapters\ExtendedMetaPlatformAdapter;

class PixelManagerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Override Meta adapter
        $this->app->extend(PlatformAdapterFactory::class, function ($factory, $app) {
            $factory->register(
                PlatformType::META,
                ExtendedMetaPlatformAdapter::class
            );
            return $factory;
        });
    }
}
```

---

## 🗄️ Custom Repositories

Replace the default repositories with your own implementation.

### Example: Custom Event Log Repository with Extra Analytics

```php
<?php

namespace App\Repositories;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\MongoDBEventLogRepository;

class CustomEventLogRepository extends MongoDBEventLogRepository
{
    public function log(PixelEvent $event, array $destinations): void
    {
        // Call parent to do the standard logging
        parent::log($event, $destinations);

        // Add custom analytics tracking
        $this->trackToAnalyticsService($event, $destinations);

        // Send to external monitoring
        $this->sendToMonitoring($event);
    }

    private function trackToAnalyticsService(PixelEvent $event, array $destinations): void
    {
        // Your custom analytics logic
        \Illuminate\Support\Facades\DB::table('custom_analytics')->insert([
            'event_id' => $event->getId()->toString(),
            'event_type' => $event->getType()->value,
            'platforms_count' => count($destinations),
            'value' => $event->getValue(),
            'timestamp' => now(),
        ]);
    }

    private function sendToMonitoring(PixelEvent $event): void
    {
        // Send to Datadog, New Relic, etc.
        if (config('services.datadog.enabled')) {
            app('datadog')->event([
                'title' => 'Pixel Event',
                'text' => $event->getType()->value,
                'tags' => ['source:pixel-manager'],
            ]);
        }
    }
}
```

**Override in AppServiceProvider:**

```php
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use App\Repositories\CustomEventLogRepository;

$this->app->bind(
    EventLogRepositoryInterface::class,
    CustomEventLogRepository::class
);
```

---

## 🎨 Add Custom Decorators

Add your own decorator to the adapter chain.

### Example: Metrics Collection Decorator

```php
<?php

namespace App\Services\PixelManager\Decorators;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use Illuminate\Support\Facades\Cache;

class MetricsCollectorDecorator implements PlatformAdapterInterface
{
    public function __construct(
        private readonly PlatformAdapterInterface $inner
    ) {
    }

    public function sendEvent(
        PixelEvent $event,
        PlatformCredentialsInterface $credentials
    ): PlatformResponse {
        $startTime = microtime(true);

        try {
            $response = $this->inner->sendEvent($event, $credentials);

            // Record success metrics
            $this->recordMetrics([
                'platform' => $this->getPlatformType()->value,
                'event_type' => $event->getType()->value,
                'status' => 'success',
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            return $response;
        } catch (\Throwable $e) {
            // Record failure metrics
            $this->recordMetrics([
                'platform' => $this->getPlatformType()->value,
                'event_type' => $event->getType()->value,
                'status' => 'failure',
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function recordMetrics(array $data): void
    {
        // Increment counters
        $key = "metrics:{$data['platform']}:{$data['event_type']}:{$data['status']}";
        Cache::increment($key);

        // Record average duration
        $durationKey = "metrics:duration:{$data['platform']}";
        $durations = Cache::get($durationKey, []);
        $durations[] = $data['duration_ms'];

        // Keep only last 100 durations
        if (count($durations) > 100) {
            array_shift($durations);
        }

        Cache::put($durationKey, $durations, now()->addHour());

        // Send to external service
        if (config('services.metrics.enabled')) {
            $this->sendToMetricsService($data);
        }
    }

    private function sendToMetricsService(array $data): void
    {
        // Send to Prometheus, StatsD, etc.
    }

    // Delegate other methods
    public function getPlatformType(): PlatformType
    {
        return $this->inner->getPlatformType();
    }

    public function supports(EventType $eventType): bool
    {
        return $this->inner->supports($eventType);
    }

    public function mapEventName(EventType $type): ?string
    {
        return $this->inner->mapEventName($type);
    }
}
```

**Register the decorator:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Factories\PlatformAdapterFactory;
use App\Services\PixelManager\Decorators\MetricsCollectorDecorator;

class PixelManagerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add custom decorator to all adapters
        $this->app->extend(PlatformAdapterFactory::class, function ($factory, $app) {
            $originalMethod = [$factory, 'create'];

            // Wrap the factory method
            return new class($originalMethod) extends PlatformAdapterFactory {
                public function create($platform)
                {
                    $adapter = parent::create($platform);

                    // Wrap with metrics collector
                    return new MetricsCollectorDecorator($adapter);
                }
            };
        });
    }
}
```

---

## ⚙️ Override Config

Publish and modify the configuration file.

**1. Publish config:**

```bash
php artisan vendor:publish --tag=pixel-manager-config
```

**2. Modify `config/pixel-manager.php`:**

```php
return [
    'app_id' => env('PIXEL_MANAGER_APP_ID', 40),

    // Add custom settings
    'blocked_ips' => [
        '192.168.1.1',
        '10.0.0.1',
    ],

    'custom_event_handlers' => [
        'purchase' => \App\Handlers\CustomPurchaseHandler::class,
    ],

    // Override default mappings
    'event_mappings' => [
        'purchase' => ['meta', 'google', 'custom-platform'],
    ],

    // Add new platforms
    'platforms' => [
        'custom-platform' => [
            'title' => 'My Custom Platform',
            'code' => 'custom',
            'fields' => [
                'api_key' => ['label' => 'API Key', 'type' => 'text'],
            ],
        ],
    ],
];
```

---

## 📦 Extend Domain Entities

Add custom properties to events.

### Example: Event with Custom Properties

```php
<?php

namespace App\Models;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;

class ExtendedPixelEvent extends PixelEvent
{
    private ?string $campaignId = null;
    private ?string $affiliateCode = null;
    private ?string $referralSource = null;

    public function setCampaignId(string $campaignId): self
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    public function getCampaignId(): ?string
    {
        return $this->campaignId;
    }

    public function setAffiliateCode(string $code): self
    {
        $this->affiliateCode = $code;
        return $this;
    }

    public function getAffiliateCode(): ?string
    {
        return $this->affiliateCode;
    }

    public function setReferralSource(string $source): self
    {
        $this->referralSource = $source;
        return $this;
    }

    public function getReferralSource(): ?string
    {
        return $this->referralSource;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        // Add custom fields
        $data['campaign_id'] = $this->campaignId;
        $data['affiliate_code'] = $this->affiliateCode;
        $data['referral_source'] = $this->referralSource;

        return $data;
    }
}
```

**Use in your code:**

```php
use App\Models\ExtendedPixelEvent;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;

$event = new ExtendedPixelEvent(
    type: EventType::PURCHASE,
    eventName: 'Purchase',
    value: 99.99,
    currency: Currency::USD
);

$event->setCampaignId('summer-sale-2026');
$event->setAffiliateCode('AFF123');
$event->setReferralSource('google-ads');

// Track as usual
PixelManager::track($event->toArray());
```

---

## 📚 Complete Examples

### Example 1: Complete Custom Platform Implementation

**Create custom platform adapter:**

```php
<?php

namespace App\PixelManager\Platforms;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use Illuminate\Http\Client\Factory as HttpClient;

class MyCustomPlatformAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly HttpClient $http
    ) {
    }

    public function getPlatformType(): PlatformType
    {
        return PlatformType::from('custom'); // If added to enum
    }

    public function supports(EventType $eventType): bool
    {
        return in_array($eventType, [
            EventType::PURCHASE,
            EventType::ADD_TO_CART,
        ]);
    }

    public function mapEventName(EventType $type): ?string
    {
        return match($type) {
            EventType::PURCHASE => 'order_completed',
            EventType::ADD_TO_CART => 'cart_add',
            default => null,
        };
    }

    public function sendEvent(
        PixelEvent $event,
        PlatformCredentialsInterface $credentials
    ): PlatformResponse {
        try {
            $response = $this->http->post('https://api.myplatform.com/events', [
                'event' => $this->mapEventName($event->getType()),
                'value' => $event->getValue(),
                'currency' => $event->getCurrency()?->value,
                'user' => [
                    'email' => $event->getCustomer()?->email?->hashed(),
                ],
            ], [
                'Authorization' => 'Bearer ' . $credentials->toArray()['api_key'],
            ]);

            if ($response->successful()) {
                return PlatformResponse::success($response->json());
            }

            return PlatformResponse::failure(
                "Custom platform error: {$response->body()}",
                $response->json()
            );
        } catch (\Throwable $e) {
            return PlatformResponse::failure($e->getMessage());
        }
    }
}
```

**Register everything:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PixelManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register custom adapter
        $this->app->bind(
            'pixel-manager.adapter.custom',
            \App\PixelManager\Platforms\MyCustomPlatformAdapter::class
        );
    }

    public function boot(): void
    {
        // Add to factory
        $this->app->extend(
            \MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Factories\PlatformAdapterFactory::class,
            function ($factory) {
                $factory->register('custom', app('pixel-manager.adapter.custom'));
                return $factory;
            }
        );
    }
}
```

---

## 🎯 Summary

### ✅ What You Can Override

- ✅ **Service Bindings** - Replace any interface implementation
- ✅ **Platform Adapters** - Extend or replace adapters
- ✅ **Repositories** - Custom storage implementations
- ✅ **Decorators** - Add cross-cutting concerns
- ✅ **Configuration** - Publish and modify settings
- ✅ **Domain Entities** - Extend with custom properties
- ✅ **Event Processing** - Hook into the pipeline

### 🏗️ Best Practices

1. **Always implement interfaces** - Don't break contracts
2. **Extend, don't modify** - Use inheritance and decoration
3. **Use Service Provider** - Register overrides properly
4. **Test thoroughly** - Ensure your customizations work
5. **Document your changes** - Help your team understand

### 📚 More Information

- [EXTENSIBILITY.md](EXTENSIBILITY.md) - Adding new features
- [SQL-SETUP.md](SQL-SETUP.md) - Database customization
- [README.md](README.md) - General usage

---

**Thanks to SOLID principles and DDD, everything is overridable!** 🎉
