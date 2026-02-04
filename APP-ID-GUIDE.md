# 🔑 Application ID (APP_ID) Guide

Understanding the `PIXEL_MANAGER_APP_ID` configuration.

## 📋 What is APP_ID?

`PIXEL_MANAGER_APP_ID` is a **unique identifier** for your application that determines **which set of credentials** to use from the database.

Think of it as a "profile ID" or "tenant ID" for your pixel tracking configuration.

---

## 🎯 Why Do We Need It?

### Single Database, Multiple Applications

Imagine you have:
- **Production website** (app_id: 40)
- **Staging website** (app_id: 41)
- **Development website** (app_id: 42)
- **Mobile app** (app_id: 43)

Each needs **different pixel credentials** (different Facebook Pixel IDs, Google Analytics IDs, etc.), but they all use the **same database**.

### How It Works

```
┌─────────────────────────────────────────────┐
│         MongoDB/SQL Database                │
├─────────────────────────────────────────────┤
│                                             │
│  applications collection/table:             │
│                                             │
│  ┌────────────────────────────────────┐    │
│  │ app_id: 40                         │    │
│  │ category: customer_event           │    │
│  │ data: {                            │    │
│  │   meta_pixel_id: "123456789",     │    │
│  │   google_measurement_id: "G-XXX"  │    │
│  │ }                                  │    │
│  └────────────────────────────────────┘    │
│                                             │
│  ┌────────────────────────────────────┐    │
│  │ app_id: 41                         │    │
│  │ category: customer_event           │    │
│  │ data: {                            │    │
│  │   meta_pixel_id: "STAGING-123",   │    │
│  │   google_measurement_id: "G-YYY"  │    │
│  │ }                                  │    │
│  └────────────────────────────────────┘    │
│                                             │
└─────────────────────────────────────────────┘

Your Laravel App (PIXEL_MANAGER_APP_ID=40)
    ↓
Fetches credentials for app_id: 40
    ↓
Uses Production Pixel IDs
```

---

## 📊 Database Structure

### MongoDB Example

```javascript
// applications collection
{
    "_id": ObjectId("..."),
    "app_id": 40,                    // ← Your APP_ID
    "category": "customer_event",
    "data": {
        // Meta Pixel credentials
        "meta_pixel_id": "123456789",
        "meta_access_token": "EAA...",

        // Google Analytics credentials
        "google_measurement_id": "G-XXXXXXXXX",
        "google_api_secret": "xyz123...",

        // TikTok credentials
        "tiktok_pixel_code": "ABC123",
        "tiktok_access_token": "tk_...",

        // Pinterest credentials
        "pinterest_account_id": "123456",
        "pinterest_access_token": "pina_...",

        // Snapchat credentials
        "snapchat_pixel_id": "snap-123",
        "snapchat_access_token": "snap_...",

        // Brevo credentials
        "brevo_api_key": "xkeysib-..."
    },
    "created_at": ISODate("2026-02-04T00:00:00Z"),
    "updated_at": ISODate("2026-02-04T00:00:00Z")
}
```

### SQL Example

```sql
-- pixel_manager_credentials table
INSERT INTO pixel_manager_credentials (app_id, category, data, created_at, updated_at)
VALUES (
    40,                           -- ← Your APP_ID
    'customer_event',
    '{
        "meta_pixel_id": "123456789",
        "meta_access_token": "EAA...",
        "google_measurement_id": "G-XXXXXXXXX",
        "google_api_secret": "xyz123...",
        "tiktok_pixel_code": "ABC123",
        "tiktok_access_token": "tk_..."
    }',
    NOW(),
    NOW()
);
```

---

## 🛠️ Configuration

### 1. Set in Environment

```env
# .env file
PIXEL_MANAGER_APP_ID=40
```

### 2. Or in Config File

```php
// config/pixel-manager.php
return [
    'app_id' => env('PIXEL_MANAGER_APP_ID', 40),
    // ...
];
```

---

## 🎯 Use Cases

### Use Case 1: Multi-Environment Setup

**Production (.env.production)**
```env
PIXEL_MANAGER_APP_ID=40
```

**Staging (.env.staging)**
```env
PIXEL_MANAGER_APP_ID=41
```

**Development (.env.local)**
```env
PIXEL_MANAGER_APP_ID=42
```

**Benefits:**
- ✅ Separate tracking for each environment
- ✅ Test pixels don't affect production data
- ✅ Different API keys per environment

### Use Case 2: Multi-Tenant SaaS

If you have a SaaS with multiple customers:

**Customer A**
```env
PIXEL_MANAGER_APP_ID=100
```

**Customer B**
```env
PIXEL_MANAGER_APP_ID=101
```

**Customer C**
```env
PIXEL_MANAGER_APP_ID=102
```

Each customer has their own pixel credentials!

### Use Case 3: Multiple Brands

**Brand: TechStore**
```env
PIXEL_MANAGER_APP_ID=200
```

**Brand: FashionHub**
```env
PIXEL_MANAGER_APP_ID=201
```

**Brand: FoodMarket**
```env
PIXEL_MANAGER_APP_ID=202
```

Each brand tracks to different pixels!

---

## 🔧 How to Set Up

### Step 1: Choose Your APP_ID

Pick any integer (commonly 40, but can be 1, 100, 1000, whatever you want).

```env
PIXEL_MANAGER_APP_ID=40
```

### Step 2: Insert Credentials to Database

#### Option A: MongoDB

```javascript
db.applications.insertOne({
    app_id: 40,  // ← Match your APP_ID
    category: "customer_event",
    data: {
        meta_pixel_id: "YOUR_META_PIXEL_ID",
        meta_access_token: "YOUR_META_TOKEN",
        google_measurement_id: "YOUR_GA_ID",
        google_api_secret: "YOUR_GA_SECRET",
        // ... other platforms
    }
});
```

#### Option B: SQL

```sql
INSERT INTO pixel_manager_credentials (app_id, category, data)
VALUES (
    40,  -- ← Match your APP_ID
    'customer_event',
    '{"meta_pixel_id":"YOUR_ID","meta_access_token":"YOUR_TOKEN"}'
);
```

### Step 3: Verify

```php
use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;

// This will use credentials from app_id: 40
PixelManager::track([
    'data' => [
        'event_type' => 'purchase',
        'value' => 99.99,
        'currency' => 'USD',
    ]
]);
```

---

## 🔍 How the Package Uses It

### 1. Package Reads Your APP_ID

```php
// From .env
PIXEL_MANAGER_APP_ID=40

// ConfigService reads it
$appId = config('pixel-manager.app_id'); // 40
```

### 2. Fetches Credentials from Database

```php
// CredentialsRepository
$credentials = $repository->findByApplicationId(40);

// Queries database:
// MongoDB: db.applications.findOne({ app_id: 40, category: "customer_event" })
// SQL: SELECT * FROM pixel_manager_credentials WHERE app_id = 40
```

### 3. Decrypts Credentials (if encrypted)

```php
$decrypted = $encryptor->decryptArray($credentials->data);
```

### 4. Extracts Platform-Specific Credentials

```php
$metaCredentials = $credentials->getCredentialsFor(PlatformType::META);
// Returns: { pixel_id: "123456789", access_token: "EAA..." }

$googleCredentials = $credentials->getCredentialsFor(PlatformType::GOOGLE);
// Returns: { measurement_id: "G-XXX", api_secret: "xyz..." }
```

### 5. Sends Events to Platforms

```php
$response = $metaAdapter->sendEvent($event, $metaCredentials);
```

---

## ⚠️ Common Mistakes

### Mistake 1: Mismatch Between .env and Database

```env
# .env
PIXEL_MANAGER_APP_ID=40
```

```javascript
// But in database
{ app_id: 41, ... }  // ❌ Different!
```

**Result:** No credentials found, events won't send!

**Fix:** Make sure they match!

### Mistake 2: Missing Category

```javascript
// ❌ Wrong - missing category
{
    app_id: 40,
    data: { ... }
}

// ✅ Correct - includes category
{
    app_id: 40,
    category: "customer_event",  // Required!
    data: { ... }
}
```

### Mistake 3: Forgetting to Set APP_ID

If you don't set it, it defaults to `40`. Make sure you have credentials for `app_id: 40` in your database!

---

## 🧪 Testing Different Environments

### Local Development

```env
# .env.local
PIXEL_MANAGER_APP_ID=999
APP_ENV=local

# Use test credentials
```

```javascript
db.applications.insertOne({
    app_id: 999,
    category: "customer_event",
    data: {
        meta_pixel_id: "TEST_PIXEL_123",
        meta_access_token: "TEST_TOKEN",
        // Test credentials won't affect production
    }
});
```

### Staging

```env
# .env.staging
PIXEL_MANAGER_APP_ID=41
APP_ENV=staging
```

### Production

```env
# .env.production
PIXEL_MANAGER_APP_ID=40
APP_ENV=production
```

---

## 📚 Advanced: Dynamic APP_ID

If you need different APP_IDs for different users/tenants:

```php
// In your controller
$tenantAppId = auth()->user()->tenant->app_id;

// Override the default
config(['pixel-manager.app_id' => $tenantAppId]);

// Now tracking uses tenant-specific credentials
PixelManager::track([...]);
```

Or create a middleware:

```php
class SetTenantPixelAppId
{
    public function handle($request, $next)
    {
        if (auth()->check()) {
            $appId = auth()->user()->tenant->pixel_app_id;
            config(['pixel-manager.app_id' => $appId]);
        }

        return $next($request);
    }
}
```

---

## 🎯 Summary

### What is APP_ID?
A unique identifier to fetch the correct pixel credentials from the database.

### Why do we need it?
To support multiple applications/environments/tenants with different credentials.

### What's the default?
`40` (but you can use any integer)

### Where is it stored?
- Configuration: `.env` → `PIXEL_MANAGER_APP_ID`
- Credentials: Database → `applications.app_id` (MongoDB) or `pixel_manager_credentials.app_id` (SQL)

### How does it work?
```
.env (APP_ID=40) → Config → Repository → Database Query (app_id=40) → Credentials → Platforms
```

---

## 🆘 Troubleshooting

### "No credentials found" Error

**Check 1:** Does your APP_ID match?
```php
echo config('pixel-manager.app_id'); // Should match database
```

**Check 2:** Does the database record exist?
```javascript
// MongoDB
db.applications.findOne({ app_id: 40, category: "customer_event" })

// SQL
SELECT * FROM pixel_manager_credentials WHERE app_id = 40;
```

**Check 3:** Is the category correct?
```javascript
// Must be exactly "customer_event"
{ app_id: 40, category: "customer_event", ... }
```

---

**Need more help?** Check [README.md](README.md) or open an issue on GitHub!
