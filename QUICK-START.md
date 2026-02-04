# ⚡ Quick Start Guide

Get started with Pixel Manager in **5 minutes** without database setup!

## 🎯 Simple Mode vs Advanced Mode

| Feature | Simple Mode (ENV) | Advanced Mode (Database) |
|---------|-------------------|--------------------------|
| **Setup Time** | 5 minutes | 15-30 minutes |
| **Credentials** | `.env` file | MongoDB/SQL database |
| **APP_ID** | Not needed | Required |
| **Multi-Environment** | Manual | Automatic |
| **Best For** | Single app, simple setup | Multi-environment, SaaS |

---

## ⚡ Quick Start (Simple Mode)

Perfect for single applications or when you want to get started immediately.

### Step 1: Install Package

```bash
composer require mehdiyev-signal/pixel-manager
```

### Step 2: Configure `.env`

Add these lines to your `.env` file:

```env
# Simple Mode Configuration
PIXEL_MANAGER_DRIVER=env

# Meta Pixel (Facebook)
PIXEL_META_PIXEL_ID=your_meta_pixel_id
PIXEL_META_ACCESS_TOKEN=your_meta_access_token

# Google Analytics 4
PIXEL_GOOGLE_MEASUREMENT_ID=G-XXXXXXXXXX
PIXEL_GOOGLE_API_SECRET=your_google_api_secret

# TikTok (optional)
PIXEL_TIKTOK_PIXEL_CODE=your_tiktok_pixel_code
PIXEL_TIKTOK_ACCESS_TOKEN=your_tiktok_access_token

# Pinterest (optional)
PIXEL_PINTEREST_ACCOUNT_ID=your_pinterest_account_id
PIXEL_PINTEREST_ACCESS_TOKEN=your_pinterest_access_token
PIXEL_PINTEREST_ENVIRONMENT=production

# Snapchat (optional)
PIXEL_SNAPCHAT_PIXEL_ID=your_snapchat_pixel_id
PIXEL_SNAPCHAT_ACCESS_TOKEN=your_snapchat_access_token

# Brevo (optional)
PIXEL_BREVO_API_KEY=your_brevo_api_key

# Optional: Event logging (if you want to log events)
PIXEL_MANAGER_LOGGING=false
```

### Step 3: Start Tracking!

```php
use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;

// Track a purchase
PixelManager::track([
    'data' => [
        'event_type' => 'purchase',
        'value' => 99.99,
        'currency' => 'USD',
        'transaction_id' => 'TXN123456',
        'customer' => [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ]
]);
```

**That's it!** Events are now being sent to your configured platforms.

---

## 📝 Complete Example

### `.env` Configuration

```env
# ==================================
# PIXEL MANAGER - SIMPLE MODE
# ==================================

# Use ENV mode (no database needed)
PIXEL_MANAGER_DRIVER=env

# Meta Pixel (Required if using Meta)
PIXEL_META_PIXEL_ID=123456789012345
PIXEL_META_ACCESS_TOKEN=EAA...your-token-here

# Google Analytics 4 (Required if using Google)
PIXEL_GOOGLE_MEASUREMENT_ID=G-XXXXXXXXXX
PIXEL_GOOGLE_API_SECRET=abcd1234567890

# Optional Platforms (add only if you use them)
PIXEL_TIKTOK_PIXEL_CODE=ABC123XYZ
PIXEL_TIKTOK_ACCESS_TOKEN=tk_your_token_here

PIXEL_PINTEREST_ACCOUNT_ID=123456789
PIXEL_PINTEREST_ACCESS_TOKEN=pina_your_token_here
PIXEL_PINTEREST_ENVIRONMENT=production

PIXEL_SNAPCHAT_PIXEL_ID=snap-pixel-id
PIXEL_SNAPCHAT_ACCESS_TOKEN=snap_token_here

PIXEL_BREVO_API_KEY=xkeysib-your-key-here

# Optional: Disable event logging to save space
PIXEL_MANAGER_LOGGING=false

# Optional: Configure which events go to which platforms
# (See config/pixel-manager.php for event mappings)
```

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;

class CheckoutController extends Controller
{
    public function complete(Request $request)
    {
        $order = $request->user()->orders()->latest()->first();

        // Track the purchase
        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'event' => 'purchase',
                'transaction_id' => $order->transaction_id,
                'order_id' => $order->id,
                'value' => $order->total,
                'currency' => $order->currency,
                'shipping' => $order->shipping_cost,
                'tax' => $order->tax,
                'customer' => [
                    'email' => $order->customer_email,
                    'external_id' => $order->customer_id,
                    'first_name' => $order->first_name,
                    'last_name' => $order->last_name,
                    'phone' => $order->phone,
                    'city' => $order->city,
                    'state' => $order->state,
                    'country_code' => $order->country_code,
                    'zip_code' => $order->zip_code,
                ],
                'items' => $order->items->map(fn($item) => [
                    'item_id' => $item->product_id,
                    'item_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'category' => $item->category,
                ])->toArray(),
            ]
        ]);

        return view('checkout.success', compact('order'));
    }
}
```

---

## 🎨 Event Examples

### Add to Cart

```php
PixelManager::track([
    'data' => [
        'event_type' => 'add_to_cart',
        'value' => 49.99,
        'currency' => 'USD',
        'customer' => [
            'email' => 'user@example.com',
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
```

### View Item

```php
PixelManager::track([
    'data' => [
        'event_type' => 'view_item',
        'value' => 29.99,
        'currency' => 'USD',
        'items' => [
            [
                'item_id' => 'PROD456',
                'item_name' => 'Cool Product',
                'price' => 29.99,
            ]
        ]
    ]
]);
```

### Begin Checkout

```php
PixelManager::track([
    'data' => [
        'event_type' => 'begin_checkout',
        'value' => 149.99,
        'currency' => 'USD',
        'customer' => [
            'email' => 'customer@example.com',
        ],
        'items' => [
            // Your cart items
        ]
    ]
]);
```

---

## ⚙️ Configuration Options

Even in simple mode, you can publish the config for advanced customization:

```bash
php artisan vendor:publish --tag=pixel-manager-config
```

Then edit `config/pixel-manager.php`:

```php
return [
    'driver' => env('PIXEL_MANAGER_DRIVER', 'env'),

    // Configure which platforms receive which events
    'event_mappings' => [
        'purchase' => ['meta', 'google', 'tiktok'],
        'add_to_cart' => ['meta', 'google'],
        'view_item' => ['meta'],
    ],

    // Enable/disable features
    'cache' => [
        'enabled' => false, // Not needed in env mode
    ],

    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
    ],

    'circuit_breaker' => [
        'enabled' => true,
    ],

    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => 100,
    ],

    'security' => [
        'bot_detection_enabled' => true,
    ],
];
```

---

## 🔄 Switching to Advanced Mode Later

When you need multi-environment support or more advanced features, you can easily switch to database mode:

### 1. Set up MongoDB or SQL

```bash
# For MongoDB
composer require mongodb/laravel-mongodb

# For SQL (already included in Laravel)
# Just configure your database connection
```

### 2. Update `.env`

```env
# Switch to database mode
PIXEL_MANAGER_DRIVER=mongodb  # or 'sql'
PIXEL_MANAGER_APP_ID=40
```

### 3. Insert credentials to database

```javascript
// MongoDB
db.applications.insertOne({
    app_id: 40,
    category: "customer_event",
    data: {
        meta_pixel_id: "...",
        meta_access_token: "...",
        // ... other credentials
    }
});
```

### 4. Remove env variables (optional)

Remove `PIXEL_*` variables from `.env` as they're now in the database.

---

## 🆚 When to Use Which Mode?

### Use Simple Mode (ENV) When:

✅ You have a **single application**
✅ You want **quick setup**
✅ You don't need **multi-environment** credentials
✅ You're **prototyping** or **testing**
✅ You have **simple requirements**

### Use Advanced Mode (Database) When:

✅ You have **multiple environments** (dev/staging/prod)
✅ You're building a **multi-tenant SaaS**
✅ You have **multiple brands/websites**
✅ You need **encrypted credentials**
✅ You want **centralized credential management**

---

## 🔍 Troubleshooting

### Events Not Sending?

**Check 1:** Is driver set to `env`?
```env
PIXEL_MANAGER_DRIVER=env
```

**Check 2:** Are credentials set?
```bash
php artisan tinker
>>> env('PIXEL_META_PIXEL_ID')
=> "123456789"  # Should show your ID
```

**Check 3:** Is queue worker running?
```bash
php artisan queue:work
```

**Check 4:** Check logs
```bash
tail -f storage/logs/laravel.log
```

### Missing Platform?

Make sure you've set the credentials for that platform:

```env
# Example for TikTok
PIXEL_TIKTOK_PIXEL_CODE=your_code
PIXEL_TIKTOK_ACCESS_TOKEN=your_token
```

If you don't need a platform, just don't set its credentials.

---

## 📚 Next Steps

- **[README.md](README.md)** - Full package documentation
- **[APP-ID-GUIDE.md](APP-ID-GUIDE.md)** - Understand advanced mode
- **[EXTENSIBILITY.md](EXTENSIBILITY.md)** - Add custom platforms
- **[OVERRIDE.md](OVERRIDE.md)** - Customize behavior
- **[SQL-SETUP.md](SQL-SETUP.md)** - Use SQL instead of MongoDB

---

## 💡 Tips

### Tip 1: Test Credentials

Use sandbox/test credentials during development:

```env
PIXEL_META_PIXEL_ID=test_pixel_123
PIXEL_PINTEREST_ENVIRONMENT=sandbox
```

### Tip 2: Selective Tracking

Only add credentials for platforms you actually use. If you only use Meta and Google, only add those:

```env
PIXEL_MANAGER_DRIVER=env
PIXEL_META_PIXEL_ID=...
PIXEL_META_ACCESS_TOKEN=...
PIXEL_GOOGLE_MEASUREMENT_ID=...
PIXEL_GOOGLE_API_SECRET=...
```

### Tip 3: Environment-Specific Files

Use different `.env` files per environment:

```bash
.env.production    # Production credentials
.env.staging       # Staging credentials
.env.local         # Local development credentials
```

---

**Ready to track? Just add credentials to `.env` and start tracking!** 🚀
