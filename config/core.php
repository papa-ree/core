<?php

// config for Bale/Core
return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CDN (Content Delivery Network) settings for serving assets.
    | CDN dapat digunakan secara global dan mendukung:
    | - Custom directory paths
    | - Shared paths (tanpa organization_slug)
    | - Organization-specific paths (otomatis menggunakan organization_slug)
    |
    */
    'cdn' => [
        /*
         * Enable or disable CDN functionality
         */
        'enabled' => env('CORE_CDN_ENABLED', false),

        /*
         * CDN Base URL
         * Example: https://cdn.example.com
         */
        'base_url' => env('CORE_CDN_URL', null),

        /*
         * CDN Path Prefix
         * Prefix yang ditambahkan setelah base URL
         * Example: 'bale' will generate https://cdn.example.com/bale/...
         */
        'prefix' => env('CORE_CDN_PREFIX', 'bale'),
    ],
];
