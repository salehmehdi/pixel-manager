# 🗄️ SQL Database Setup Guide

Pixel Manager v2.0 now supports **MongoDB** and **SQL** databases (MySQL, PostgreSQL, SQLite)!

## 📋 Quick Start

### 1. Choose Driver

Configure the driver in your `.env` file:

```env
# To use SQL
PIXEL_MANAGER_DRIVER=sql
PIXEL_MANAGER_SQL_CONNECTION=mysql

# To use MongoDB (default)
PIXEL_MANAGER_DRIVER=mongodb
```

### 2. Run Migrations

If using SQL, create the tables:

```bash
php artisan migrate
```

Migration file location:
```bash
src/Infrastructure/Persistence/SQL/Migrations/create_pixel_manager_tables.php
```

Copy to Laravel migrations folder:
```bash
cp src/Infrastructure/Persistence/SQL/Migrations/create_pixel_manager_tables.php \
   database/migrations/2026_02_04_000001_create_pixel_manager_tables.php
```

### 3. Database Configuration

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

## 📊 Table Structure

### `pixel_manager_credentials` Table

Stores platform credentials:

```sql
CREATE TABLE pixel_manager_credentials (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    app_id INT UNIQUE NOT NULL,
    category VARCHAR(50) DEFAULT 'customer_event',
    data JSON NOT NULL,  -- All platform credentials
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX(app_id, category)
);
```

**Example data (JSON):**
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

### `pixel_manager_events` Table

Logs all pixel events:

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
    destinations JSON,  -- Which platforms received the event
    event_data JSON,    -- Full event data
    created_at TIMESTAMP,
    INDEX(event_type),
    INDEX(event_name),
    INDEX(created_at),
    INDEX(customer_email)
);
```

**Example event record:**
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

## 🔄 Migrating from MongoDB to SQL

### 1. Export Existing MongoDB Data

```bash
# Export credentials
mongoexport --db=your_db --collection=applications \
  --query='{"category":"customer_event"}' \
  --out=credentials.json

# Export events
mongoexport --db=your_db --collection=mp_customer_event \
  --out=events.json
```

### 2. Import to SQL Script

```php
<?php

use Illuminate\Support\Facades\DB;

// Import credentials
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

// Import events
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

The advantage of SQL: Easy analytics!

### Event Statistics

```php
use MehdiyevSignal\PixelManager\Infrastructure\Persistence\SQL\SQLEventLogRepository;

$repo = app(SQLEventLogRepository::class);

// Stats by platform
$stats = $repo->getStatsByPlatform(
    now()->subDays(7),
    now()
);
// ['meta' => 1250, 'google' => 1200, 'tiktok' => 980]

// Stats by event type
$eventStats = $repo->getStatsByEventType(
    now()->subDays(30),
    now()
);
// ['purchase' => 350, 'add_to_cart' => 1200, 'view_item' => 4500]

// Revenue statistics
$revenue = $repo->getRevenueStats(
    now()->subDays(7),
    now()
);
// ['USD' => 12500.50, 'EUR' => 8900.00, 'AZN' => 3450.00]
```

### Custom SQL Queries

```php
use Illuminate\Support\Facades\DB;

// Top selling products
$topProducts = DB::table('pixel_manager_events')
    ->where('event_type', 'purchase')
    ->whereDate('created_at', '>=', now()->subDays(30))
    ->select('event_data->items->item_id as product_id', DB::raw('COUNT(*) as sales'))
    ->groupBy('product_id')
    ->orderBy('sales', 'desc')
    ->limit(10)
    ->get();

// Daily conversion rate
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

// Revenue by country
$revenueByCountry = DB::table('pixel_manager_events')
    ->where('event_type', 'purchase')
    ->whereNotNull('customer_country')
    ->select('customer_country', DB::raw('SUM(value) as total_revenue'))
    ->groupBy('customer_country')
    ->orderBy('total_revenue', 'desc')
    ->get();
```

---

## ⚡ Performance Tips

### 1. Indexes

The migration already creates necessary indexes, but you can add more:

```sql
-- For customer email searches
CREATE INDEX idx_customer_email ON pixel_manager_events(customer_email);

-- For date-based queries
CREATE INDEX idx_created_at_event_type ON pixel_manager_events(created_at, event_type);

-- For JSON field searches (MySQL 5.7+)
CREATE INDEX idx_destinations ON pixel_manager_events((CAST(destinations AS CHAR(50) ARRAY)));
```

### 2. Partitioning (For Large Datasets)

Date-based partitioning in MySQL:

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

### 3. Archiving Old Records

```php
// Delete events older than 90 days
DB::table('pixel_manager_events')
    ->where('created_at', '<', now()->subDays(90))
    ->delete();

// Or archive to a separate table
DB::statement('
    INSERT INTO pixel_manager_events_archive
    SELECT * FROM pixel_manager_events
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
');

DB::table('pixel_manager_events')
    ->where('created_at', '<', now()->subDays(90))
    ->delete();
```

---

## 🔍 Troubleshooting

### "Table doesn't exist" Error

```bash
# Ensure you ran the migration
php artisan migrate

# Check migration file exists
ls -la database/migrations/ | grep pixel_manager
```

### JSON Field Issues

Ensure you're using MySQL 5.7+ or PostgreSQL 9.4+:

```sql
SELECT VERSION();
-- MySQL: Should be 5.7.0 or higher
-- PostgreSQL: Should be 9.4 or higher
```

### Performance Issues

```sql
-- Check query plan
EXPLAIN SELECT * FROM pixel_manager_events
WHERE event_type = 'purchase'
AND created_at >= '2026-01-01';

-- Check index usage
SHOW INDEX FROM pixel_manager_events;

-- Analyze table (MySQL)
ANALYZE TABLE pixel_manager_events;

-- Analyze table (PostgreSQL)
ANALYZE pixel_manager_events;
```

### Connection Issues

```php
// Test database connection
try {
    DB::connection('mysql')->getPdo();
    echo "Connection successful!";
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

---

## 🆚 MongoDB vs SQL Comparison

| Feature | MongoDB | SQL |
|---------|---------|-----|
| **Setup** | Easy | Very Easy |
| **Scalability** | Excellent | Good |
| **Analytics** | Good | Excellent |
| **Joins** | Difficult | Easy |
| **Flexible Schema** | Yes | No |
| **Transaction Support** | Good | Excellent |
| **Hosting Cost** | Higher | Lower |
| **Query Language** | MongoDB Query | Standard SQL |

### When to Use SQL?

✅ You already have an SQL database
✅ You need complex analytics queries
✅ You have relational data
✅ You want to avoid MongoDB hosting costs
✅ You need strong ACID transactions

### When to Use MongoDB?

✅ Very large data volumes
✅ Horizontal scaling required
✅ Flexible schema preferred
✅ Document-based structure fits better
✅ High write throughput needed

---

## 📊 Example Analytics Dashboard

### Daily Metrics Query

```php
$metrics = DB::table('pixel_manager_events')
    ->select([
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(DISTINCT customer_email) as unique_users'),
        DB::raw('COUNT(*) as total_events'),
        DB::raw('SUM(CASE WHEN event_type = "purchase" THEN 1 ELSE 0 END) as purchases'),
        DB::raw('SUM(CASE WHEN event_type = "purchase" THEN value ELSE 0 END) as revenue'),
    ])
    ->whereDate('created_at', '>=', now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->get();
```

### Platform Performance

```php
$platformPerformance = DB::table('pixel_manager_events')
    ->select([
        DB::raw('JSON_UNQUOTE(JSON_EXTRACT(destinations, "$[0]")) as platform'),
        DB::raw('COUNT(*) as events_sent'),
        DB::raw('AVG(value) as avg_value'),
    ])
    ->whereDate('created_at', '>=', now()->subDays(7))
    ->groupBy('platform')
    ->get();
```

### Conversion Funnel

```php
$funnel = DB::table('pixel_manager_events')
    ->select([
        DB::raw('SUM(CASE WHEN event_type = "view_item" THEN 1 ELSE 0 END) as views'),
        DB::raw('SUM(CASE WHEN event_type = "add_to_cart" THEN 1 ELSE 0 END) as add_to_cart'),
        DB::raw('SUM(CASE WHEN event_type = "begin_checkout" THEN 1 ELSE 0 END) as checkouts'),
        DB::raw('SUM(CASE WHEN event_type = "purchase" THEN 1 ELSE 0 END) as purchases'),
    ])
    ->whereDate('created_at', '>=', now()->subDays(7))
    ->first();

// Calculate conversion rates
$conversionRates = [
    'view_to_cart' => ($funnel->add_to_cart / $funnel->views) * 100,
    'cart_to_checkout' => ($funnel->checkouts / $funnel->add_to_cart) * 100,
    'checkout_to_purchase' => ($funnel->purchases / $funnel->checkouts) * 100,
];
```

---

## 🎯 Conclusion

SQL support makes Pixel Manager **more flexible** and **compatible with every environment**!

For questions: [GitHub Issues](https://github.com/mehdiyev-signal/pixel-manager/issues)

**v2.0 with DDD architecture + SQL support = Production-ready!** 🚀
