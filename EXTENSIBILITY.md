# 🔧 Extensibility Guide

This guide shows you how to extend Pixel Manager to add new platforms, events, and features.

## 📋 Table of Contents

1. [Adding New Platforms](#adding-new-platforms)
2. [Adding New Events](#adding-new-events)
3. [Adding SQL Support](#adding-sql-support)
4. [Custom Repositories](#custom-repositories)
5. [Custom Decorators](#custom-decorators)

---

## 🚀 Adding New Platforms

Let's add LinkedIn Insight Tag as an example. This process takes approximately 1-2 hours.

### Step 1: Add to PlatformType Enum

**File:** `src/Domain/ValueObjects/PlatformType.php`

```php
enum PlatformType: string
{
    case META = 'meta';
    case GOOGLE = 'google';
    case TIKTOK = 'tiktok';
    case PINTEREST = 'pinterest';
    case SNAPCHAT = 'snapchat';
    case BREVO = 'brevo';

    // NEW PLATFORM
    case LINKEDIN = 'linkedin';  // 👈 Add your platform

    public function displayName(): string
    {
        return match ($this) {
            self::META => 'Meta Pixel (Facebook)',
            self::GOOGLE => 'Google Analytics 4',
            self::TIKTOK => 'TikTok Pixel',
            self::PINTEREST => 'Pinterest Tag',
            self::SNAPCHAT => 'Snapchat Pixel',
            self::BREVO => 'Brevo (Sendinblue)',
            self::LINKEDIN => 'LinkedIn Insight Tag',  // 👈 Display name
        };
    }
}
```

### Step 2: Create Platform Credentials

**File:** `src/Domain/ValueObjects/PixelCredentials/LinkedInCredentials.php`

```php
<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;

final readonly class LinkedInCredentials implements PlatformCredentialsInterface
{
    public function __construct(
        public string $partnerId,
        public string $conversionId,
        public string $accessToken,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            partnerId: $data['linkedin_partner_id'] ?? '',
            conversionId: $data['linkedin_conversion_id'] ?? '',
            accessToken: $data['linkedin_access_token'] ?? '',
        );
    }

    public function isValid(): bool
    {
        return !empty($this->partnerId)
            && !empty($this->conversionId)
            && !empty($this->accessToken);
    }

    public function toArray(): array
    {
        return [
            'partner_id' => $this->partnerId,
            'conversion_id' => $this->conversionId,
            'access_token' => $this->accessToken,
        ];
    }
}
```

### Step 3: Create Platform Adapter

**File:** `src/Infrastructure/Http/PlatformAdapters/LinkedInPlatformAdapter.php`

```php
<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use Illuminate\Http\Client\Factory as HttpClient;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PixelCredentials\LinkedInCredentials;

final class LinkedInPlatformAdapter extends AbstractHttpPlatformAdapter
{
    private const API_VERSION = 'v2';
    private const BASE_URL = 'https://api.linkedin.com';

    public function getPlatformType(): PlatformType
    {
        return PlatformType::LINKEDIN;
    }

    public function supports(EventType $eventType): bool
    {
        return in_array($eventType, [
            EventType::PURCHASE,
            EventType::ADD_TO_CART,
            EventType::VIEW_ITEM,
            EventType::COMPLETED_REGISTRATION,
        ]);
    }

    public function mapEventName(EventType $type): ?string
    {
        return match ($type) {
            EventType::PURCHASE => 'PURCHASE',
            EventType::ADD_TO_CART => 'ADD_TO_CART',
            EventType::VIEW_ITEM => 'VIEW_CONTENT',
            EventType::COMPLETED_REGISTRATION => 'SIGN_UP',
            default => null,
        };
    }

    protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string
    {
        assert($credentials instanceof LinkedInCredentials);

        return self::BASE_URL . '/' . self::API_VERSION
            . '/conversions?ids=' . $credentials->partnerId;
    }

    protected function buildHeaders(PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof LinkedInCredentials);

        return [
            'Authorization' => 'Bearer ' . $credentials->accessToken,
            'Content-Type' => 'application/json',
            'LinkedIn-Version' => self::API_VERSION,
        ];
    }

    protected function buildPayload(PixelEvent $event, PlatformCredentialsInterface $credentials): array
    {
        assert($credentials instanceof LinkedInCredentials);

        $eventName = $this->mapEventName($event->getType());

        return [
            'conversion' => $credentials->conversionId,
            'conversionHappenedAt' => $event->getEventTime()->getTimestamp() * 1000,
            'conversionValue' => [
                'currencyCode' => $event->getCurrency()?->value ?? 'USD',
                'amount' => (string) $event->getValue(),
            ],
            'eventId' => $event->getId()->toString(),
            'user' => [
                'userIds' => [
                    [
                        'idType' => 'SHA256_EMAIL',
                        'idValue' => $event->getCustomer()?->email?->hashed() ?? '',
                    ],
                ],
            ],
        ];
    }
}
```

### Step 4: Add to ApplicationCredentials

**File:** `src/Domain/Entities/ApplicationCredentials.php`

```php
public function getCredentialsFor(PlatformType $platform): ?PlatformCredentialsInterface
{
    return match ($platform) {
        PlatformType::META => MetaCredentials::fromArray($this->data),
        PlatformType::GOOGLE => GoogleCredentials::fromArray($this->data),
        PlatformType::TIKTOK => TikTokCredentials::fromArray($this->data),
        PlatformType::PINTEREST => PinterestCredentials::fromArray($this->data),
        PlatformType::SNAPCHAT => SnapchatCredentials::fromArray($this->data),
        PlatformType::BREVO => BrevoCredentials::fromArray($this->data),
        PlatformType::LINKEDIN => LinkedInCredentials::fromArray($this->data), // 👈 NEW
    };
}
```

### Step 5: Register in Factory

**File:** `src/Infrastructure/Http/PlatformAdapters/Factories/PlatformAdapterFactory.php`

```php
private function createPlatformAdapter(PlatformType $platform): PlatformAdapterInterface
{
    $baseAdapter = match ($platform) {
        PlatformType::META => new MetaPlatformAdapter($this->http),
        PlatformType::GOOGLE => new GooglePlatformAdapter($this->http),
        PlatformType::TIKTOK => new TikTokPlatformAdapter($this->http),
        PlatformType::PINTEREST => new PinterestPlatformAdapter($this->http),
        PlatformType::SNAPCHAT => new SnapchatPlatformAdapter($this->http),
        PlatformType::BREVO => new BrevoPlatformAdapter($this->http),
        PlatformType::LINKEDIN => new LinkedInPlatformAdapter($this->http), // 👈 NEW
    };

    return $this->applyDecorators($baseAdapter);
}
```

### Step 6: Add to Config

**File:** `config/pixel-manager.php`

```php
'event_mappings' => [
    'purchase' => ['meta', 'google', 'tiktok', 'pinterest', 'snapchat', 'brevo', 'linkedin'],
    'add_to_cart' => ['meta', 'google', 'tiktok', 'pinterest', 'snapchat', 'brevo', 'linkedin'],
    // ...
],

'platforms' => [
    // ...
    'linkedin' => [
        'title' => 'LinkedIn Insight Tag',
        'code' => 'linkedin',
        'category' => 'pixel',
        'fields' => [
            'linkedin_partner_id' => [
                'label' => 'Partner ID',
                'type' => 'text',
                'required' => true,
            ],
            'linkedin_conversion_id' => [
                'label' => 'Conversion ID',
                'type' => 'text',
                'required' => true,
            ],
            'linkedin_access_token' => [
                'label' => 'Access Token',
                'type' => 'text',
                'required' => true,
            ],
        ],
    ],
],
```

### Step 7: Add to Facade

**File:** `src/Presentation/Facades/PixelManagerFacadeImpl.php`

```php
public function platforms(): array
{
    return [
        'meta',
        'google',
        'tiktok',
        'pinterest',
        'snapchat',
        'brevo',
        'linkedin',  // 👈 NEW
    ];
}
```

---

## 📅 Adding New Events

Adding a new event type takes approximately 30 minutes.

### Step 1: Add to EventType Enum

**File:** `src/Domain/ValueObjects/EventType.php`

```php
enum EventType: string
{
    case SEARCH = 'search';
    case SUBSCRIPTION = 'subscription';
    case ADD_TO_CART = 'add_to_cart';
    case PURCHASE = 'purchase';
    // ... other events

    // NEW EVENT
    case CONTACT_FORM = 'contact_form';  // 👈 Add your event
}
```

### Step 2: Update Platform Adapters

Update `supports()` and `mapEventName()` in each relevant platform adapter:

```php
public function supports(EventType $eventType): bool
{
    return in_array($eventType, [
        EventType::PURCHASE,
        EventType::ADD_TO_CART,
        // ...
        EventType::CONTACT_FORM,  // 👈 New event
    ]);
}

public function mapEventName(EventType $type): ?string
{
    return match ($type) {
        EventType::PURCHASE => 'Purchase',
        EventType::ADD_TO_CART => 'AddToCart',
        // ...
        EventType::CONTACT_FORM => 'Contact',  // 👈 Platform-specific mapping
        default => null,
    };
}
```

### Step 3: Add to Config

```php
'event_mappings' => [
    'contact_form' => ['meta', 'google', 'brevo'],  // 👈 New event mapping
    // ...
],
```

---

## 🗄️ Adding SQL Support

SQL support is already built-in! Just configure and migrate.

### Using MySQL/PostgreSQL/SQLite

**1. Configure `.env`:**

```env
# Choose SQL driver
PIXEL_MANAGER_DRIVER=sql

# SQL connection (mysql, pgsql, sqlite)
PIXEL_MANAGER_SQL_CONNECTION=mysql
```

**2. Copy migration:**

```bash
cp src/Infrastructure/Persistence/SQL/Migrations/create_pixel_manager_tables.php \
   database/migrations/2026_02_04_000001_create_pixel_manager_tables.php
```

**3. Run migration:**

```bash
php artisan migrate
```

See [SQL-SETUP.md](SQL-SETUP.md) for detailed instructions.

---

## 🗃️ Custom Repositories

You can create custom repository implementations for any storage backend.

### Example: Redis Repository

**File:** `src/Infrastructure/Persistence/Redis/RedisCredentialsRepository.php`

```php
<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\Redis;

use Illuminate\Support\Facades\Redis;
use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

final class RedisCredentialsRepository implements CredentialsRepositoryInterface
{
    private const KEY_PREFIX = 'pixel_manager:credentials:';
    private const TTL = 3600; // 1 hour

    public function findByApplicationId(int $appId): ?ApplicationCredentials
    {
        $key = self::KEY_PREFIX . $appId;
        $data = Redis::get($key);

        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);

        return new ApplicationCredentials(
            appId: $decoded['app_id'],
            category: $decoded['category'],
            data: $decoded['data']
        );
    }

    public function save(ApplicationCredentials $credentials): void
    {
        $key = self::KEY_PREFIX . $credentials->getAppId();
        $data = json_encode([
            'app_id' => $credentials->getAppId(),
            'category' => $credentials->getCategory(),
            'data' => $credentials->getData(),
        ]);

        Redis::setex($key, self::TTL, $data);
    }

    // ... implement other methods
}
```

**Register in ServiceProvider:**

```php
$this->app->bind(
    CredentialsRepositoryInterface::class,
    RedisCredentialsRepository::class
);
```

---

## 🎨 Custom Decorators

Add your own cross-cutting concerns using the Decorator pattern.

### Example: Metrics Decorator

**File:** `src/Infrastructure/Http/PlatformAdapters/Decorators/MetricsPlatformAdapter.php`

```php
<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters\Decorators;

use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

final class MetricsPlatformAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly PlatformAdapterInterface $inner,
        private readonly MetricsCollector $metrics
    ) {
    }

    public function sendEvent(
        PixelEvent $event,
        PlatformCredentialsInterface $credentials
    ): PlatformResponse {
        $startTime = microtime(true);
        $response = $this->inner->sendEvent($event, $credentials);
        $duration = (microtime(true) - $startTime) * 1000;

        // Collect metrics
        $this->metrics->record([
            'platform' => $this->getPlatformType()->value,
            'event_type' => $event->getType()->value,
            'success' => $response->isSuccess(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }

    // Delegate other methods to inner adapter
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

**Register in PlatformAdapterFactory:**

```php
private function applyDecorators(PlatformAdapterInterface $adapter): PlatformAdapterInterface
{
    // Custom decorator
    if (config('app.metrics_enabled')) {
        $adapter = new MetricsPlatformAdapter(
            $adapter,
            app(MetricsCollector::class)
        );
    }

    // Built-in decorators
    if (config('pixel-manager.logging', true)) {
        $adapter = new LoggingPlatformAdapter($adapter, Log::channel());
    }

    if (config('pixel-manager.rate_limiting.enabled', true)) {
        $adapter = new RateLimitingPlatformAdapter(
            $adapter,
            app(CacheRepository::class),
            config('pixel-manager.rate_limiting.max_requests_per_minute', 100)
        );
    }

    // ... other decorators

    return $adapter;
}
```

---

## 📦 Publishing as Separate Package

You can publish your platform adapter as a separate package:

### Example: composer.json

```json
{
    "name": "your-vendor/pixel-manager-linkedin",
    "description": "LinkedIn Insight Tag adapter for Pixel Manager",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "mehdiyev-signal/pixel-manager": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "YourVendor\\PixelManagerLinkedIn\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "YourVendor\\PixelManagerLinkedIn\\LinkedInServiceProvider"
            ]
        }
    }
}
```

### Service Provider

```php
<?php

namespace YourVendor\PixelManagerLinkedIn;

use Illuminate\Support\ServiceProvider;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

class LinkedInServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your platform adapter
        $this->app->extend(
            PlatformAdapterFactory::class,
            function ($factory) {
                $factory->register(
                    PlatformType::LINKEDIN,
                    LinkedInPlatformAdapter::class
                );
                return $factory;
            }
        );
    }
}
```

---

## 🎯 Summary

### ✅ Easy to Extend

- ✅ **Add new platform:** 7 files (1-2 hours)
- ✅ **Add new event:** 3 files (30 minutes)
- ✅ **Add SQL support:** Built-in, just configure
- ✅ **Custom decorator:** 1 file (1 hour)
- ✅ **Custom repository:** 1 file (2 hours)

### 🏗️ DDD Benefits

- **Interface-based:** Everything follows contracts
- **Modular:** Add features without touching core
- **Testable:** Mock any component
- **Maintainable:** Clear separation of concerns

### 📚 More Information

- [README.md](README.md) - General usage
- [UPGRADE-2.0.md](UPGRADE-2.0.md) - Migration guide
- [README-V2-ADDENDUM.md](README-V2-ADDENDUM.md) - v2.0 features
- [SQL-SETUP.md](SQL-SETUP.md) - SQL database setup

---

**Thanks to DDD architecture, everything is modular and extensible!** 🎉
