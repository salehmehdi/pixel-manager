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
