# Pixel Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saleh-signal/pixel-manager.svg?style=flat-square)](https://packagist.org/packages/saleh-signal/pixel-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/saleh-signal/pixel-manager.svg?style=flat-square)](https://packagist.org/packages/saleh-signal/pixel-manager)

A powerful Laravel package for tracking and distributing customer events to multiple marketing platforms (Meta Pixel, Google Analytics 4, Brevo, TikTok, Pinterest, Snapchat) in real-time.

## Features

- 🚀 **Multi-Platform Support**: Meta, Google, Brevo, TikTok, Pinterest, Snapchat
- ⚡ **Asynchronous Processing**: Queue-based event distribution
- 🎯 **Event Mapping**: Configure which platforms receive specific events
- 📊 **MongoDB Logging**: Track all events for analytics
- 🔧 **Highly Configurable**: Flexible configuration system
- 🎨 **Clean Architecture**: Domain-driven design with clear separation of concerns
- 🧪 **Well Tested**: Comprehensive test coverage

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher
- MongoDB extension and driver
- Queue driver (Redis, Database, etc.)

## Installation

Install the package via Composer:

```bash
composer require saleh-signal/pixel-manager
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=pixel-manager-config
```

Optionally, publish the migration files:

```bash
php artisan vendor:publish --tag=pixel-manager-migrations
php artisan migrate
```

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# Pixel Manager Configuration
PIXEL_MANAGER_APP_ID=40
PIXEL_MANAGER_DB_CONNECTION=mongodb
PIXEL_MANAGER_COLLECTION=mp_customer_event
PIXEL_MANAGER_QUEUE=default
PIXEL_MANAGER_LOGGING=true

# MongoDB Connection
DB_CONNECTION=mongodb
DB_DSN=mongodb://localhost:27017
DB_DATABASE=your_database
```

### Platform Credentials

Store your platform credentials in MongoDB's `applications` collection:

```javascript
db.applications.insertOne({
    app_id: 40,
    category: "customer_event",
    data: {
        // Meta Pixel
        meta_pixel_id: "YOUR_META_PIXEL_ID",
        meta_access_token: "YOUR_META_ACCESS_TOKEN",

        // Google Analytics 4
        google_measurement_id: "YOUR_GA4_MEASUREMENT_ID",
        google_api_secret: "YOUR_GA4_API_SECRET",

        // Brevo
        brevo_api_key: "YOUR_BREVO_API_KEY",

        // TikTok
        tiktok_pixel_code: "YOUR_TIKTOK_PIXEL_CODE",
        tiktok_access_token: "YOUR_TIKTOK_ACCESS_TOKEN",

        // Pinterest
        pinterest_account_id: "YOUR_PINTEREST_ACCOUNT_ID",
        pinterest_access_token: "YOUR_PINTEREST_ACCESS_TOKEN",

        // Snapchat
        snapchat_pixel_id: "YOUR_SNAPCHAT_PIXEL_ID",
        snapchat_access_token: "YOUR_SNAPCHAT_ACCESS_TOKEN"
    }
})
```

### Event Mapping

Configure which platforms receive specific events in `config/pixel-manager.php`:

```php
'event_mappings' => [
    'purchase' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
    'add_to_cart' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
    'view_item' => ['meta', 'google', 'tiktok', 'brevo'],
    'search' => ['meta', 'google'],
    // Add more event mappings...
],
```

## Usage

### Basic Usage

Use the Facade to track events:

```php
use SalehSignal\PixelManager\Facades\PixelManager;

PixelManager::track([
    'data' => [
        'event_type' => 'purchase',
        'event' => 'purchase',
        'transaction_id' => 'TXN123456',
        'order_id' => 'ORD789',
        'value' => 99.99,
        'currency' => 'USD',
        'shipping' => 5.00,
        'customer' => [
            'email' => 'customer@example.com',
            'external_id' => 'user_12345',
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
                'price' => 49.99,
                'quantity' => 2,
                'category' => 'Electronics',
                'item_brand' => 'BrandName',
            ]
        ]
    ]
]);
```

### In a Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SalehSignal\PixelManager\Facades\PixelManager;

class EventController extends Controller
{
    public function track(Request $request)
    {
        PixelManager::track($request->all());

        return response()->json(['success' => true]);
    }
}
```

### Check Platform Status

```php
// Get all supported platforms
$platforms = PixelManager::platforms();
// Returns: ['meta', 'google', 'brevo', 'tiktok', 'pinterest', 'snapchat']

// Check if a platform is enabled
if (PixelManager::isPlatformEnabled('meta')) {
    // Meta pixel is configured and enabled
}
```

## Supported Events

The package supports the following standard events:

| Event Type | Description | Platforms |
|------------|-------------|-----------|
| `purchase` | Completed purchase | All |
| `add_to_cart` | Product added to cart | All |
| `view_item` | Product viewed | All |
| `begin_checkout` | Checkout started | All |
| `view_cart` | Shopping cart viewed | All |
| `search` | Search performed | All |
| `add_payment_info` | Payment info added | All |
| `add_to_wishlist` | Item added to wishlist | All |
| `page_view` | Page viewed | All |
| `completed_registration` | User registration | All |
| `subscription` | Subscription created | All |
| `customize_product` | Product customization | Meta only |

## Platform-Specific Features

### Meta Pixel
- Server-side event tracking
- Automatic user data hashing
- FBC/FBP tracking support
- Full Facebook Business SDK integration

### Google Analytics 4
- Measurement Protocol v2
- Standard GA4 event names
- Rich e-commerce data
- Client ID and User ID support

### Brevo
- Contact identification (email, WhatsApp, external ID)
- Contact properties tracking
- Event properties with cart data
- Real-time CRM updates

### TikTok, Pinterest, Snapchat
- Server-side conversion tracking
- Product catalog integration
- Advanced event parameters
- Audience building support

## Queue Configuration

The package uses Laravel's queue system for asynchronous processing. Make sure to configure your queue driver:

```env
QUEUE_CONNECTION=redis
PIXEL_MANAGER_QUEUE=pixel-events
```

Run the queue worker:

```bash
php artisan queue:work --queue=pixel-events
```

## Event Logging

All events are logged to MongoDB for analytics and debugging. Access logs through the `CustomerEvent` model:

```php
use SalehSignal\PixelManager\Models\CustomerEvent;

$events = CustomerEvent::where('event_name', 'purchase')
    ->where('created_at', '>=', now()->subDay())
    ->get();
```

## Testing

Run the test suite:

```bash
composer test
```

With coverage:

```bash
composer test-coverage
```

## Troubleshooting

### Events Not Being Sent

1. Check queue worker is running
2. Verify platform credentials in MongoDB
3. Check `storage/logs/laravel.log` for errors
4. Ensure event type is mapped in config

### MongoDB Connection Issues

```bash
# Verify MongoDB extension is installed
php -m | grep mongodb

# Test connection
php artisan tinker
>>> DB::connection('mongodb')->getMongoDB()->listCollections()
```

### Platform-Specific Issues

Check individual platform action logs:
```bash
tail -f storage/logs/laravel.log | grep "Brevo\|Meta\|Google"
```

## Security

If you discover any security-related issues, please email security@saleh-signal.com instead of using the issue tracker.

## Credits

- [Saleh Signal](https://github.com/saleh-signal)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Support

For support, please open an issue on GitHub or contact support@saleh-signal.com.
