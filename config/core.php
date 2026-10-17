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

    /*
    |--------------------------------------------------------------------------
    | Keycloak Session Configuration
    |--------------------------------------------------------------------------
    |
    | Jika true: maka ketika sso logout maka aplikasi logout, dan sebaliknya
    | Jika false: maka meskipun sso tidak memiliki sesi aktif maka di aplikasi tetap terlogin
    |
    */
    'sso_session_sync' => env('CORE_SSO_SESSION_SYNC', false),

    /*
    |--------------------------------------------------------------------------
    | WordPress Migration (queue) Configuration
    |--------------------------------------------------------------------------
    |
    | Migrasi WordPress → Bale CMS dijalankan lewat queue, satu job per post.
    | `wp-import` menangani tahap SQL (ringan), `wp-media` menangani tahap
    | konversi media (berat). Media otomatis dirantai setelah tahap SQL selesai.
    |
    | PENTING: siapkan queue worker untuk kedua antrian, contoh:
    |   php artisan queue:work --queue=wp-import,wp-media
    |
    */
    'wp_migration' => [
        /*
         | Otomatis mulai tahap media setelah batch impor SQL selesai.
         */
        'chain_media' => env('WP_MIGRATION_CHAIN_MEDIA', true),

        /*
         | Otomatis mulai tahap thumbnail (featured image) setelah tahap media
         | selesai. Butuh dump lengkap yang memuat tabel `wp_postmeta`
         | (`_thumbnail_id`); pada dump "posts-only" tahap ini selesai tanpa
         | thumbnail (dilaporkan sebagai `thumbnail_run_id = null`).
         */
        'chain_thumbnails' => env('WP_MIGRATION_CHAIN_THUMBNAILS', true),

        /*
         | Nama antrian untuk tahap impor SQL.
         */
        'sql_queue' => env('WP_MIGRATION_SQL_QUEUE', 'wp-import'),

        /*
         | Nama antrian untuk tahap migrasi media.
         */
        'media_queue' => env('WP_MIGRATION_MEDIA_QUEUE', 'wp-media'),

        /*
         | Jumlah item per statement INSERT saat membuat item run.
         */
        'chunk_size' => env('WP_MIGRATION_CHUNK_SIZE', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Entrance Gate IP Allowlist
    |--------------------------------------------------------------------------
    |
    | Membatasi akses halaman form login (`entrance.gate`) hanya dari alamat IP
    | yang terdaftar. Daftar utama dikelola lewat halaman "Login Allowlist"
    | (tabel `entrance_gate_allowlist`); `allow` di sini adalah daftar statis
    | tambahan (dipisah koma) sebagai jalur darurat bila halaman admin tidak
    | bisa diakses.
    |
    | Perilaku fail-open: bila fitur nonaktif ATAU tidak ada satu pun entri
    | (baik dari database maupun `allow`), semua IP diizinkan agar admin tidak
    | terkunci. `127.0.0.1` dan `::1` selalu diizinkan.
    |
    */
    'entrance_gate' => [
        'enabled' => env('CORE_ENTRANCE_GATE_ENABLED', false),
        'allow' => env('CORE_ENTRANCE_GATE_ALLOW', ''),

        /*
         | Selalu izinkan localhost (127.0.0.1 / ::1) saat fitur aktif, sebagai
         | jalur darurat anti-lockout. Set false di produksi bila origin TIDAK
         | berada di balik proxy lokal -- supaya IP yang salah resolve menjadi
         | 127.0.0.1 tidak justru membuka gate.
         */
        'allow_localhost' => env('CORE_ENTRANCE_GATE_ALLOW_LOCALHOST', true),
    ],
];
