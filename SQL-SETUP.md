# 🗄️ SQL Database Setup Guide

Pixel Manager v2.0 artık **MongoDB** ve **SQL** (MySQL, PostgreSQL, SQLite) veritabanlarını destekliyor!

## 📋 Hızlı Başlangıç

### 1. Driver Seçimi

`.env` dosyanızda driver'ı seçin:

```env
# SQL kullanmak için
PIXEL_MANAGER_DRIVER=sql
PIXEL_MANAGER_SQL_CONNECTION=mysql

# MongoDB kullanmak için (default)
PIXEL_MANAGER_DRIVER=mongodb
```

### 2. Migration'ları Çalıştırın

SQL kullanıyorsanız, tabloları oluşturun:

```bash
php artisan migrate
```

Migration dosyası:
```bash
src/Infrastructure/Persistence/SQL/Migrations/create_pixel_manager_tables.php
```

Laravel'in migration klasörüne kopyalayın:
```bash
cp src/Infrastructure/Persistence/SQL/Migrations/create_pixel_manager_tables.php database/migrations/2026_02_04_000001_create_pixel_manager_tables.php
```

### 3. Veritabanı Yapılandırması

#### MySQL

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

PIXEL_MANAGER_DRIVER=sql
PIXEL_MANAGER_SQL_CONNECTION=mysql
```

#### PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

PIXEL_MANAGER_DRIVER=sql
PIXEL_MANAGER_SQL_CONNECTION=pgsql
```

#### SQLite

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

PIXEL_MANAGER_DRIVER=sql
PIXEL_MANAGER_SQL_CONNECTION=sqlite
```

---

## 📊 Tablo Yapısı

### `pixel_manager_credentials` Tablosu

Platform credentials'larını saklar:

```sql
CREATE TABLE pixel_manager_credentials (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    app_id INT UNIQUE NOT NULL,
    category VARCHAR(50) DEFAULT 'customer_event',
    data JSON NOT NULL,  -- Tüm platform credentials
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX(app_id, category)
);
```

**Örnek data (JSON):**
```json
{
    "meta_pixel_id": "123456789",
    "meta_access_token": "EAA...",
    "google_measurement_id": "G-XXXXXXXXX",
    "google_api_secret": "xyz...",
    "tiktok_pixel_code": "ABC...",
    "tiktok_access_token": "tk..."
}
```

### `pixel_manager_events` Tablosu

Tüm pixel event'leri loglar:

```sql
CREATE TABLE pixel_manager_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id VARCHAR(100) UNIQUE NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    value DECIMAL(12,2),
    currency VARCHAR(3),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    customer_first_name VARCHAR(100),
    customer_last_name VARCHAR(100),
    customer_city VARCHAR(100),
    customer_country VARCHAR(2),
    ip_address VARCHAR(45),
    user_agent TEXT,
    destinations JSON,  -- Hangi platformlara gönderildi
    event_data JSON,    -- Tam event datası
    created_at TIMESTAMP,
    INDEX(event_type),
    INDEX(event_name),
    INDEX(created_at),
    INDEX(customer_email)
);
```

**Örnek event kaydı:**
```json
{
    "event_id": "65f1234567890abcdef",
    "event_type": "purchase",
    "event_name": "Purchase",
    "value": 99.99,
    "currency": "USD",
    "customer_email": "customer@example.com",
    "destinations": ["meta", "google", "tiktok"],
    "event_data": { ... }
}
```

---

## 🔄 MongoDB'dan SQL'e Geçiş

### 1. Mevcut MongoDB Verilerini Export Edin

```bash
# Credentials export
mongoexport --db=your_db --collection=applications \
  --query='{"category":"customer_event"}' \
  --out=credentials.json

# Events export
mongoexport --db=your_db --collection=mp_customer_event \
  --out=events.json
```

### 2. SQL'e Import Scripti

```php
<?php

use Illuminate\Support\Facades\DB;

// Credentials import
$credentials = json_decode(file_get_contents('credentials.json'), true);
foreach ($credentials as $cred) {
    DB::table('pixel_manager_credentials')->insert([
        'app_id' => $cred['app_id'],
        'category' => $cred['category'],
        'data' => json_encode($cred['data']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Events import
$events = json_decode(file_get_contents('events.json'), true);
foreach ($events as $event) {
    DB::table('pixel_manager_events')->insert([
        'event_id' => $event['_id']['$oid'] ?? uniqid(),
        'event_type' => $event['event_type'] ?? 'unknown',
        'event_name' => $event['event_name'] ?? 'Unknown',
        'value' => $event['value'] ?? null,
        'currency' => $event['currency'] ?? null,
        'customer_email' => $event['customer_email'] ?? null,
        'destinations' => json_encode($event['destinations'] ?? []),
        'event_data' => json_encode($event),
        'created_at' => $event['created_at'] ?? now(),
    ]);
}
```

---

## 📈 Analytics Queries

SQL kullanmanın avantajı: Kolay analytics!

### Event İstatistikleri

```php
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL\SQLEventLogRepository;

$repo = app(SQLEventLogRepository::class);

// Platform başına istatistikler
$stats = $repo->getStatsByPlatform(
    now()->subDays(7),
    now()
);
// ['meta' => 1250, 'google' => 1200, 'tiktok' => 980]

// Event tipi başına
$eventStats = $repo->getStatsByEventType(
    now()->subDays(30),
    now()
);
// ['purchase' => 350, 'add_to_cart' => 1200, 'view_item' => 4500]

// Gelir istatistikleri
$revenue = $repo->getRevenueStats(
    now()->subDays(7),
    now()
);
// ['USD' => 12500.50, 'EUR' => 8900.00, 'AZN' => 3450.00]
```

### Custom SQL Queries

```php
use Illuminate\Support\Facades\DB;

// En çok satan ürünler
$topProducts = DB::table('pixel_manager_events')
    ->where('event_type', 'purchase')
    ->whereDate('created_at', '>=', now()->subDays(30))
    ->select('event_data->items->item_id as product_id', DB::raw('COUNT(*) as sales'))
    ->groupBy('product_id')
    ->orderBy('sales', 'desc')
    ->limit(10)
    ->get();

// Günlük conversion rate
$dailyStats = DB::table('pixel_manager_events')
    ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('SUM(CASE WHEN event_type = "purchase" THEN 1 ELSE 0 END) as purchases'),
        DB::raw('SUM(CASE WHEN event_type = "add_to_cart" THEN 1 ELSE 0 END) as carts'),
        DB::raw('SUM(CASE WHEN event_type = "view_item" THEN 1 ELSE 0 END) as views')
    )
    ->whereDate('created_at', '>=', now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->get();
```

---

## ⚡ Performance Tips

### 1. Indexler

Migration zaten gerekli indexleri oluşturuyor, ancak ek indexler ekleyebilirsiniz:

```sql
-- Customer email aramaları için
CREATE INDEX idx_customer_email ON pixel_manager_events(customer_email);

-- Tarih bazlı sorgular için
CREATE INDEX idx_created_at_event_type ON pixel_manager_events(created_at, event_type);

-- JSON alan aramaları için (MySQL 5.7+)
CREATE INDEX idx_destinations ON pixel_manager_events((CAST(destinations AS CHAR(50) ARRAY)));
```

### 2. Partitioning (Büyük Veri İçin)

MySQL'de tarih bazlı partitioning:

```sql
ALTER TABLE pixel_manager_events
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202601 VALUES LESS THAN (202602),
    PARTITION p202602 VALUES LESS THAN (202603),
    PARTITION p202603 VALUES LESS THAN (202604),
    -- ...
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

### 3. Eski Kayıtları Temizleme

```php
// 90 günden eski event'leri sil
DB::table('pixel_manager_events')
    ->where('created_at', '<', now()->subDays(90))
    ->delete();
```

---

## 🔍 Troubleshooting

### "Table doesn't exist" Hatası

```bash
# Migration'ı çalıştırdığınızdan emin olun
php artisan migrate

# Migration dosyasını kontrol edin
ls -la database/migrations/ | grep pixel_manager
```

### JSON Alan Sorunları

MySQL 5.7+ kullandığınızdan emin olun:

```sql
SELECT VERSION();
-- 5.7.0 veya üzeri olmalı
```

### Performance Sorunları

```sql
-- Query plan'ı kontrol edin
EXPLAIN SELECT * FROM pixel_manager_events
WHERE event_type = 'purchase'
AND created_at >= '2026-01-01';

-- Index kullanımını kontrol edin
SHOW INDEX FROM pixel_manager_events;
```

---

## 🆚 MongoDB vs SQL Karşılaştırması

| Özellik | MongoDB | SQL |
|---------|---------|-----|
| **Setup** | Kolay | Çok Kolay |
| **Scalability** | Mükemmel | İyi |
| **Analytics** | İyi | Mükemmel |
| **Join'ler** | Zor | Kolay |
| **Flexible Schema** | Evet | Hayır |
| **Transaction Support** | İyi | Mükemmel |
| **Hosting Cost** | Yüksek | Düşük |

### Ne Zaman SQL Kullanılmalı?

✅ Mevcut SQL veritabanınız varsa
✅ Complex analytics sorgular yapıyorsanız
✅ Relational data'nız varsa
✅ MongoDB kurulum maliyetinden kaçınmak istiyorsanız

### Ne Zaman MongoDB Kullanılmalı?

✅ Çok büyük data volume'ü varsa
✅ Horizontal scaling gerekiyorsa
✅ Flexible schema tercih ediyorsanız
✅ Document-based yapı daha uygunsa

---

## 🎯 Sonuç

SQL desteği ile Pixel Manager artık **daha esnek** ve **her ortama uyumlu**!

Sorularınız için: [GitHub Issues](https://github.com/mehdiyev-signal/pixel-manager/issues)

**v2.0 ile DDD architecture + SQL support = Production-ready!** 🚀
