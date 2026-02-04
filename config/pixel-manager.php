<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application ID
    |--------------------------------------------------------------------------
    |
    | The application ID used to retrieve platform credentials from MongoDB.
    | This can be overridden in your .env file using PIXEL_MANAGER_APP_ID.
    |
    */
    'app_id' => env('PIXEL_MANAGER_APP_ID', 40),

    /*
    |--------------------------------------------------------------------------
    | MongoDB Connection
    |--------------------------------------------------------------------------
    |
    | Configure the MongoDB connection and collection names for event logging.
    |
    */
    'connection' => env('PIXEL_MANAGER_DB_CONNECTION', 'mongodb'),
    'collection' => env('PIXEL_MANAGER_COLLECTION', 'mp_customer_event'),
    'applications_collection' => env('PIXEL_MANAGER_APPLICATIONS_COLLECTION', 'applications'),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | The queue connection and name to use for asynchronous event processing.
    |
    */
    'queue' => env('PIXEL_MANAGER_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Event Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable event logging to MongoDB. Useful for analytics and debugging.
    |
    */
    'logging' => env('PIXEL_MANAGER_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Enable credential caching to reduce MongoDB queries by ~90%.
    | Uses Laravel's cache driver (Redis, Memcached, etc.).
    |
    */
    'cache' => [
        'enabled' => env('PIXEL_MANAGER_CACHE_ENABLED', true),
        'ttl' => env('PIXEL_MANAGER_CACHE_TTL', 3600), // 1 hour in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure exponential backoff retry logic for failed API calls.
    |
    */
    'retry' => [
        'enabled' => env('PIXEL_MANAGER_RETRY_ENABLED', true),
        'max_attempts' => env('PIXEL_MANAGER_RETRY_MAX_ATTEMPTS', 3),
        'initial_delay_ms' => env('PIXEL_MANAGER_RETRY_INITIAL_DELAY', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Prevent cascading failures by opening circuit after threshold failures.
    |
    */
    'circuit_breaker' => [
        'enabled' => env('PIXEL_MANAGER_CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('PIXEL_MANAGER_CIRCUIT_BREAKER_THRESHOLD', 5),
        'timeout_seconds' => env('PIXEL_MANAGER_CIRCUIT_BREAKER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limit API requests per minute to prevent rate limit violations.
    |
    */
    'rate_limiting' => [
        'enabled' => env('PIXEL_MANAGER_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => env('PIXEL_MANAGER_RATE_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Configure security features like encryption and bot detection.
    |
    */
    'security' => [
        'encrypt_credentials' => env('PIXEL_MANAGER_ENCRYPT_CREDENTIALS', true),
        'bot_detection_enabled' => env('PIXEL_MANAGER_BOT_DETECTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Mappings
    |--------------------------------------------------------------------------
    |
    | Define which platforms should receive each event type.
    | Reference: https://developers.facebook.com/docs/meta-pixel/reference#standard-events
    |
    */
    'event_mappings' => [
        'search' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'subscription' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'add_to_cart' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'purchase' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'view_item' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'completed_registration' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'begin_checkout' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'view_cart' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'add_payment_info' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'add_to_wishlist' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'page_view' => ['meta', 'google', 'tiktok', 'brevo', 'pinterest', 'snapchat'],
        'customize_product' => ['meta'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Configurations
    |--------------------------------------------------------------------------
    |
    | Define the required fields and metadata for each supported platform.
    |
    */
    'platforms' => [
        'meta' => [
            'title' => 'Meta Pixel',
            'code' => 'meta',
            'category' => 'pixel',
            'fields' => [
                'meta_pixel_id' => [
                    'label' => 'Pixel ID',
                    'type' => 'text',
                    'required' => true,
                ],
                'meta_access_token' => [
                    'label' => 'Access Token',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ],

        'google' => [
            'title' => 'Google Analytics 4',
            'code' => 'google',
            'category' => 'pixel',
            'fields' => [
                'google_measurement_id' => [
                    'label' => 'Measurement ID',
                    'type' => 'text',
                    'required' => true,
                ],
                'google_api_secret' => [
                    'label' => 'API Secret',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ],

        'tiktok' => [
            'title' => 'TikTok Pixel',
            'code' => 'tiktok',
            'category' => 'pixel',
            'fields' => [
                'tiktok_pixel_code' => [
                    'label' => 'Pixel Code',
                    'type' => 'text',
                    'required' => true,
                ],
                'tiktok_access_token' => [
                    'label' => 'Access Token',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ],

        'pinterest' => [
            'title' => 'Pinterest Tag',
            'code' => 'pinterest',
            'category' => 'pixel',
            'fields' => [
                'pinterest_account_id' => [
                    'label' => 'Account ID',
                    'type' => 'text',
                    'required' => true,
                ],
                'pinterest_access_token' => [
                    'label' => 'Access Token',
                    'type' => 'text',
                    'required' => true,
                ],
                'pinterest_environment' => [
                    'label' => 'Environment',
                    'type' => 'select',
                    'options' => ['production', 'sandbox'],
                    'default' => 'production',
                    'required' => false,
                ],
            ],
        ],

        'snapchat' => [
            'title' => 'Snapchat Pixel',
            'code' => 'snapchat',
            'category' => 'pixel',
            'fields' => [
                'snapchat_pixel_id' => [
                    'label' => 'Pixel ID',
                    'type' => 'text',
                    'required' => true,
                ],
                'snapchat_access_token' => [
                    'label' => 'Access Token',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ],

        'brevo' => [
            'title' => 'Brevo (formerly Sendinblue)',
            'code' => 'brevo',
            'category' => 'pixel',
            'fields' => [
                'brevo_api_key' => [
                    'label' => 'API Key',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ],
    ],
];
