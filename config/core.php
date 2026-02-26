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

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi analytics eksternal.
    | Saat ini mendukung Umami Analytics (self-hosted).
    |
    */
    'analytics' => [
        'umami' => [
            /*
             | URL instance Umami (self-hosted)
             | Contoh: https://umami.ponorogo.go.id
             */
            'url' => env('UMAMI_URL', ''),

            /*
             | Umami API Token
             | Buat di dashboard Umami: Settings → API Keys → Create API Key
             */
            'api_key' => env('UMAMI_API_KEY', ''),

            /*
             | Timezone untuk penghitungan tanggal range data.
             | HARUS sama dengan timezone yang dikonfigurasi di dashboard Umami.
             | Contoh: 'Asia/Jakarta', 'UTC', 'Asia/Singapore'
             */
            'timezone' => env('UMAMI_TIMEZONE', 'Asia/Jakarta'),

            /*
             | TTL cache data analytics dalam detik (default: 5 menit)
             | Data analytics di-cache di database tenant untuk menghindari spam ke API
             */
            'cache_ttl' => env('UMAMI_CACHE_TTL', 300),
        ],
    ],
];
