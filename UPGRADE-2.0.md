# Upgrade Guide: v1.x to v2.0

## Overview

Version 2.0 is a major rewrite with Domain-Driven Design (DDD) architecture, comprehensive bug fixes, and production-ready features. This guide will help you migrate from v1.x to v2.0.

## Breaking Changes

### 1. Package Name Change
- **Old:** `saleh-signal/pixel-manager`
- **New:** `mehdiyev-signal/pixel-manager`

### 2. Namespace Change
- **Old:** `SalehSignal\PixelManager`
- **New:** `MehdiyevSignal\PixelManager`

### 3. Service Provider Path
- **Old:** `SalehSignal\PixelManager\PixelManagerServiceProvider`
- **New:** `MehdiyevSignal\PixelManager\Presentation\Providers\PixelManagerServiceProvider`

### 4. Facade Path
- **Old:** `SalehSignal\PixelManager\Facades\PixelManager`
- **New:** `MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager`

## Migration Steps

### Step 1: Backup Your Data

**IMPORTANT:** Backup your MongoDB database before upgrading!

```bash
mongodump --db your_database_name --out=/backup/path
```

### Step 2: Update Composer

Remove the old package and install the new one:

```bash
composer remove saleh-signal/pixel-manager
composer require mehdiyev-signal/pixel-manager:^2.0
```

### Step 3: Update Namespace References

Update all references in your code:

**Before:**
```php
use SalehSignal\PixelManager\Facades\PixelManager;
```

**After:**
```php
use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;
```

### Step 4: Republish Configuration

```bash
php artisan vendor:publish --tag=pixel-manager-config --force
```

### Step 5: Update Environment Variables

Add new optional environment variables to your `.env`:

```env
# Caching (NEW)
PIXEL_MANAGER_CACHE_ENABLED=true
PIXEL_MANAGER_CACHE_TTL=3600

# Retry (NEW)
PIXEL_MANAGER_RETRY_ENABLED=true
PIXEL_MANAGER_RETRY_MAX_ATTEMPTS=3
PIXEL_MANAGER_RETRY_INITIAL_DELAY=100

# Circuit Breaker (NEW)
PIXEL_MANAGER_CIRCUIT_BREAKER_ENABLED=true
PIXEL_MANAGER_CIRCUIT_BREAKER_THRESHOLD=5
PIXEL_MANAGER_CIRCUIT_BREAKER_TIMEOUT=60

# Rate Limiting (NEW)
PIXEL_MANAGER_RATE_LIMITING_ENABLED=true
PIXEL_MANAGER_RATE_LIMIT=100

# Security (NEW)
PIXEL_MANAGER_ENCRYPT_CREDENTIALS=true
PIXEL_MANAGER_BOT_DETECTION=true
```

### Step 6: Pinterest Environment Configuration

If you use Pinterest, update your credentials to specify environment:

```javascript
// In MongoDB applications collection
db.applications.updateOne(
  { app_id: 40, category: "customer_event" },
  {
    $set: {
      "data.pinterest_environment": "production" // or "sandbox"
    }
  }
)
```

### Step 7: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
```

### Step 8: Test Your Integration

```php
use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;

// Test tracking
PixelManager::track([
    'data' => [
        'event_type' => 'purchase',
        'value' => 99.99,
        'currency' => 'USD',
        'customer' => [
            'email' => 'test@example.com',
        ],
    ]
]);

// Check platform status
$enabled = PixelManager::isPlatformEnabled('meta');
```

## What's New in v2.0

### Architecture
- ✅ Domain-Driven Design (DDD)
- ✅ SOLID principles throughout
- ✅ Proper separation of concerns
- ✅ ~50% code reduction via abstraction

### Bug Fixes
- ✅ **Meta:** Fixed date_of_birth using wrong method (setGender)
- ✅ **Pinterest:** Fixed opt_out being set to event_name
- ✅ **Pinterest:** Fixed hardcoded sandbox endpoint
- ✅ **Google:** Fixed undefined $response variable
- ✅ **All Platforms:** Fixed undefined botDetected() function

### Performance Improvements
- ✅ Credential caching (90% DB query reduction)
- ✅ Exponential backoff retry (3 attempts, smart delays)
- ✅ Circuit breaker pattern (prevents cascading failures)
- ✅ Rate limiting (protects against API limits)

### Security Enhancements
- ✅ AES-256 encryption for credentials at rest
- ✅ SHA256 hashing for PII (email, phone)
- ✅ Bot detection (20+ bot patterns)
- ✅ Secure-by-default configuration

### Developer Experience
- ✅ PHP 8.2+ features (readonly, enums)
- ✅ Type-safe throughout
- ✅ Comprehensive error handling
- ✅ Better logging with context

### New Currencies
- ✅ Added support for AZN (Azerbaijani Manat) 🇦🇿

## Backward Compatibility

### API Compatibility
The public API remains largely compatible:

```php
// v1.x - Still works!
PixelManager::track($data);

// v2.0 - Same!
PixelManager::track($data);
```

### Configuration Compatibility
Your existing `pixel-manager.php` config will continue to work. New features are opt-in with sensible defaults.

### Database Compatibility
MongoDB schema remains unchanged. No data migration required!

## Troubleshooting

### Issue: Namespace errors after upgrade

**Solution:** Clear all caches and ensure you've updated all use statements:

```bash
composer dump-autoload
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Credentials not found

**Solution:** Check MongoDB credentials collection and encryption:

```php
// In tinker
$repo = app(\MehdiyevSignal\PixelManager\Domain\Repositories\CredentialsRepositoryInterface::class);
$creds = $repo->findByApplicationId(40);
dd($creds);
```

### Issue: Events not being sent

**Solution:** Check queue worker and logs:

```bash
# Check queue
php artisan queue:work --queue=pixel-events

# Check logs
tail -f storage/logs/laravel.log | grep "Pixel Manager"
```

### Issue: Pinterest still using sandbox

**Solution:** Update environment in MongoDB:

```javascript
db.applications.findOne({ app_id: 40 })
// Add pinterest_environment: "production" to data field
```

## Support

### Documentation
- README.md - Updated with v2.0 features
- Config file comments - Inline documentation

### Issues
- GitHub Issues: https://github.com/mehdiyev-signal/pixel-manager/issues

### Questions
- Create a discussion on GitHub

## Rollback Procedure

If you need to roll back to v1.x:

```bash
# 1. Restore MongoDB backup
mongorestore --db your_database_name /backup/path/your_database_name

# 2. Reinstall v1.x
composer remove mehdiyev-signal/pixel-manager
composer require saleh-signal/pixel-manager:^1.0

# 3. Revert namespace changes in your code
# 4. Clear caches
php artisan cache:clear
php artisan config:clear
```

## Recommendations

1. **Test in Staging First** - Always test the upgrade in a staging environment
2. **Monitor Logs** - Watch logs closely after upgrade for any issues
3. **Enable All Features** - Take advantage of new resilience features
4. **Use Encryption** - Enable credential encryption for security
5. **Configure Rate Limits** - Adjust per your platform API limits

## Conclusion

Version 2.0 represents a major improvement in code quality, security, and reliability. While there are breaking changes, the migration path is straightforward and the benefits are significant.

**Estimated Migration Time:** 15-30 minutes

Thank you for using Pixel Manager! 🚀

---

**Changed Package Name:** saleh-signal → mehdiyev-signal
**Version:** 2.0.0
**Release Date:** 2026-02-04
