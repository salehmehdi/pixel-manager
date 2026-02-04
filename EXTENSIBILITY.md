# 🔧 Extensibility Guide - Paket Genişletme Rehberi

Bu dokümantasyon, Pixel Manager paketini nasıl genişletebileceğinizi gösterir.

## 📋 İçindekiler

1. [Yeni Platform Ekleme](#yeni-platform-ekleme)
2. [Yeni Event Ekleme](#yeni-event-ekleme)
3. [SQL Desteği Ekleme](#sql-desteği-ekleme)
4. [Custom Repository Ekleme](#custom-repository-ekleme)
5. [Decorator Ekleme](#decorator-ekleme)

---

## 🚀 Yeni Platform Ekleme

### Adım 1: PlatformType Enum'ına Ekleyin

**Dosya:** `src/Domain/ValueObjects/PlatformType.php`

```php
enum PlatformType: string
{
    case META = 'meta';
    case GOOGLE = 'google';
    case TIKTOK = 'tiktok';
    case PINTEREST = 'pinterest';
    case SNAPCHAT = 'snapchat';
    case BREVO = 'brevo';

    // YENİ PLATFORM
    case LINKEDIN = 'linkedin';  // 👈 Yeni platform ekleyin

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

### Adım 2: Platform Credentials Oluşturun

**Dosya:** `src/Domain/ValueObjects/PixelCredentials/LinkedInCredentials.php`

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

### Adım 3: Platform Adapter Oluşturun

**Dosya:** `src/Infrastructure/Http/PlatformAdapters/LinkedInPlatformAdapter.php`

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

### Adım 4: ApplicationCredentials'a Ekleyin

**Dosya:** `src/Domain/Entities/ApplicationCredentials.php`

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
        PlatformType::LINKEDIN => LinkedInCredentials::fromArray($this->data), // 👈 YENİ
    };
}
```

### Adım 5: Factory'ye Kaydedin

**Dosya:** `src/Infrastructure/Http/PlatformAdapters/Factories/PlatformAdapterFactory.php`

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
        PlatformType::LINKEDIN => new LinkedInPlatformAdapter($this->http), // 👈 YENİ
    };

    return $this->applyDecorators($baseAdapter);
}
```

### Adım 6: Config'e Ekleyin

**Dosya:** `config/pixel-manager.php`

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

### Adım 7: Facade'e Ekleyin

**Dosya:** `src/Presentation/Facades/PixelManagerFacadeImpl.php`

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
        'linkedin',  // 👈 YENİ
    ];
}
```

---

## 📅 Yeni Event Ekleme

### Adım 1: EventType Enum'ına Ekleyin

**Dosya:** `src/Domain/ValueObjects/EventType.php`

```php
enum EventType: string
{
    case SEARCH = 'search';
    case SUBSCRIPTION = 'subscription';
    case ADD_TO_CART = 'add_to_cart';
    case PURCHASE = 'purchase';
    // ... diğer eventler

    // YENİ EVENT
    case CONTACT_FORM = 'contact_form';  // 👈 Yeni event ekleyin
}
```

### Adım 2: Platform Adapter'lara Ekleyin

Her platform adapter'ında `supports()` ve `mapEventName()` metodlarını güncelleyin:

```php
public function supports(EventType $eventType): bool
{
    return in_array($eventType, [
        EventType::PURCHASE,
        EventType::ADD_TO_CART,
        // ...
        EventType::CONTACT_FORM,  // 👈 Yeni event
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

### Adım 3: Config'e Ekleyin

```php
'event_mappings' => [
    'contact_form' => ['meta', 'google', 'brevo'],  // 👈 Yeni event mapping
    // ...
],
```

---

## 🗄️ SQL Desteği Ekleme

### MySQL/PostgreSQL Repository Implementasyonu

**Dosya:** `src/Infrastructure/Persistence/SQL/SQLCredentialsRepository.php`

```php
<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL;

use Illuminate\Support\Facades\DB;
use MehdiyevSignal\PixelManager\Domain\Entities\ApplicationCredentials;
use MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

final class SQLCredentialsRepository implements CredentialsRepositoryInterface
{
    public function __construct(
        private readonly string $tableName = 'pixel_manager_credentials'
    ) {
    }

    public function findByApplicationId(int $appId): ?ApplicationCredentials
    {
        $record = DB::table($this->tableName)
            ->where('app_id', $appId)
            ->where('category', 'customer_event')
            ->first();

        if (!$record) {
            return null;
        }

        return new ApplicationCredentials(
            appId: $record->app_id,
            category: $record->category,
            data: json_decode($record->data, true)
        );
    }

    public function findPlatformCredentials(
        int $appId,
        PlatformType $platform
    ): ?PlatformCredentialsInterface {
        $credentials = $this->findByApplicationId($appId);

        return $credentials?->getCredentialsFor($platform);
    }

    public function save(ApplicationCredentials $credentials): void
    {
        DB::table($this->tableName)->updateOrInsert(
            [
                'app_id' => $credentials->getAppId(),
                'category' => $credentials->getCategory(),
            ],
            [
                'data' => json_encode($credentials->getData()),
                'updated_at' => now(),
            ]
        );
    }

    public function delete(int $appId): void
    {
        DB::table($this->tableName)
            ->where('app_id', $appId)
            ->delete();
    }
}
```

**Dosya:** `src/Infrastructure/Persistence/SQL/SQLEventLogRepository.php`

```php
<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL;

use Illuminate\Support\Facades\DB;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Repositories\EventLogRepositoryInterface;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventId;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

final class SQLEventLogRepository implements EventLogRepositoryInterface
{
    public function __construct(
        private readonly string $tableName = 'pixel_manager_events'
    ) {
    }

    public function log(PixelEvent $event, array $destinations): void
    {
        DB::table($this->tableName)->insert([
            'event_id' => $event->getId()->toString(),
            'event_type' => $event->getType()->value,
            'event_name' => $event->getEventName(),
            'value' => $event->getValue(),
            'currency' => $event->getCurrency()?->value,
            'customer_email' => $event->getCustomer()?->email?->value(),
            'customer_phone' => $event->getCustomer()?->phone?->value(),
            'destinations' => json_encode(array_map(fn($p) => $p->value, $destinations)),
            'event_data' => json_encode($event->toArray()),
            'created_at' => $event->getEventTime(),
        ]);
    }

    public function findById(EventId $id): ?PixelEvent
    {
        $record = DB::table($this->tableName)
            ->where('event_id', $id->toString())
            ->first();

        if (!$record) {
            return null;
        }

        // Reconstruct PixelEvent from record
        // Implementation depends on your needs
        return null;
    }

    public function findByEventType(string $eventType, int $limit = 100): array
    {
        $records = DB::table($this->tableName)
            ->where('event_type', $eventType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Map to PixelEvent entities
        return [];
    }

    public function countByDateRange(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): int {
        return DB::table($this->tableName)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }
}
```

### SQL Migration

**Dosya:** `database/migrations/2026_02_04_000001_create_pixel_manager_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credentials table
        Schema::create('pixel_manager_credentials', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id')->unique();
            $table->string('category', 50)->default('customer_event');
            $table->json('data');
            $table->timestamps();

            $table->index('app_id');
        });

        // Events log table
        Schema::create('pixel_manager_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique();
            $table->string('event_type', 50);
            $table->string('event_name', 100);
            $table->decimal('value', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->json('destinations');
            $table->json('event_data');
            $table->timestamp('created_at');

            $table->index('event_type');
            $table->index('created_at');
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pixel_manager_events');
        Schema::dropIfExists('pixel_manager_credentials');
    }
};
```

### SQL Config

**Dosya:** `config/pixel-manager.php`

```php
return [
    // Database driver: 'mongodb' or 'sql'
    'driver' => env('PIXEL_MANAGER_DRIVER', 'mongodb'),

    // SQL configuration (when driver is 'sql')
    'sql' => [
        'connection' => env('PIXEL_MANAGER_SQL_CONNECTION', 'mysql'),
        'credentials_table' => 'pixel_manager_credentials',
        'events_table' => 'pixel_manager_events',
    ],

    // MongoDB configuration (when driver is 'mongodb')
    'mongodb' => [
        'connection' => env('PIXEL_MANAGER_DB_CONNECTION', 'mongodb'),
        'applications_collection' => 'applications',
        'events_collection' => env('PIXEL_MANAGER_COLLECTION', 'mp_customer_event'),
    ],

    // ...
];
```

### ServiceProvider'da SQL Binding

**Dosya:** `src/Presentation/Providers/PixelManagerServiceProvider.php`

```php
private function registerRepositories(): void
{
    $driver = config('pixel-manager.driver', 'mongodb');

    if ($driver === 'sql') {
        // SQL repositories
        $this->app->singleton(
            CredentialsRepositoryInterface::class,
            fn() => new SQLCredentialsRepository(
                config('pixel-manager.sql.credentials_table')
            )
        );

        $this->app->singleton(
            EventLogRepositoryInterface::class,
            fn() => new SQLEventLogRepository(
                config('pixel-manager.sql.events_table')
            )
        );
    } else {
        // MongoDB repositories (default)
        $this->app->singleton(
            CredentialsRepositoryInterface::class,
            MongoDBCredentialsRepository::class
        );

        $this->app->singleton(
            EventLogRepositoryInterface::class,
            MongoDBEventLogRepository::class
        );
    }

    // Wrap with caching decorator
    if (config('pixel-manager.cache.enabled', true)) {
        $this->app->extend(
            CredentialsRepositoryInterface::class,
            fn($repo, $app) => new CachedCredentialsRepository(
                $repo,
                $app->make('cache.store'),
                config('pixel-manager.cache.ttl', 3600)
            )
        );
    }
}
```

---

## 🎨 Custom Decorator Ekleme

Kendi decorator'ınızı ekleyebilirsiniz (örneğin, metrics toplama):

```php
<?php

namespace YourApp\PixelManager\Decorators;

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

    public function sendEvent(PixelEvent $event, PlatformCredentialsInterface $credentials): PlatformResponse
    {
        $startTime = microtime(true);
        $response = $this->inner->sendEvent($event, $credentials);
        $duration = (microtime(true) - $startTime) * 1000;

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

ServiceProvider'da kaydedin:

```php
private function applyDecorators(PlatformAdapterInterface $adapter): PlatformAdapterInterface
{
    // Custom decorator
    if (config('app.metrics_enabled')) {
        $adapter = new MetricsPlatformAdapter($adapter, app(MetricsCollector::class));
    }

    // Existing decorators
    if (config('pixel-manager.logging', true)) {
        $adapter = new LoggingPlatformAdapter($adapter, Log::channel());
    }

    // ...

    return $adapter;
}
```

---

## 📦 Package Olarak Yayınlama

Kendi platform veya decorator'ınızı ayrı bir package olarak yayınlayabilirsiniz:

```json
{
    "name": "your-vendor/pixel-manager-linkedin",
    "description": "LinkedIn Insight Tag adapter for Pixel Manager",
    "require": {
        "mehdiyev-signal/pixel-manager": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "YourVendor\\PixelManagerLinkedIn\\": "src/"
        }
    }
}
```

---

## 🎯 Özet

### ✅ Kolay Genişletilebilir
- ✅ Yeni platform ekleme: 7 dosya (1-2 saat)
- ✅ Yeni event ekleme: 3 dosya (30 dakika)
- ✅ SQL desteği: Repository değiştirme (2 saat)
- ✅ Custom decorator: 1 dosya (1 saat)

### 📚 Daha Fazla Bilgi
- [README.md](README.md) - Genel kullanım
- [UPGRADE-2.0.md](UPGRADE-2.0.md) - Migration rehberi
- [README-V2-ADDENDUM.md](README-V2-ADDENDUM.md) - v2.0 özellikleri

---

**DDD mimarisi sayesinde her şey modüler ve genişletilebilir!** 🎉
