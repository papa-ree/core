<?php

// config for Bale/Core
return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CDN settings for asset delivery.
    | When enabled, assets will be served from the CDN URL.
    |
    */
    'cdn' => [
        /*
         | Enable or disable CDN
         | Set CORE_CDN_ENABLED=true in .env to activate
         */
        'enabled' => env('CORE_CDN_ENABLED', false),

        /*
         | CDN Base URL
         | Example: https://cdn.ponorogo.go.id
         */
        'base_url' => env('CORE_CDN_URL', ''),

        /*
         | CDN Prefix/Bucket Name
         | This will be added after base_url
         | Example: 'bale' results in https://cdn.ponorogo.go.id/bale/...
         */
        'prefix' => env('CORE_CDN_PREFIX', 'bale'),
    ],
];
