<?php

use Bale\Core\Support\Cdn;

if (!function_exists('cdn_asset')) {
    /**
     * Generate CDN URL untuk asset path.
     * 
     * Mendukung 3 mode:
     * 1. Organization-specific: otomatis menambahkan organization_slug jika tersedia
     * 2. Shared: path diawali 'shared/' akan mengabaikan organization_slug
     * 3. Custom directory: gunakan parameter $customDir untuk specify direktori custom
     *
     * @param string $path Path file yang akan di-CDN-kan
     * @param string|null $customDir Custom directory (opsional)
     * @return string
     * 
     * @example
     * cdn_asset('thumbnails/logo.png')
     * → https://cdn.example.com/bale/organization-slug/thumbnails/logo.png
     * 
     * cdn_asset('shared/logo.png')
     * → https://cdn.example.com/bale/shared/logo.png
     * 
     * cdn_asset('images/banner.jpg', 'custom-folder')
     * → https://cdn.example.com/bale/custom-folder/images/banner.jpg
     */
    function cdn_asset(string $path, ?string $customDir = null): string
    {
        return app('cdn')->asset($path, $customDir);
    }
}

if (!function_exists('cdn_url')) {
    /**
     * Generate CDN URL untuk path.
     * Alias untuk cdn_asset()
     *
     * @param string $path Path file yang akan di-CDN-kan
     * @param string|null $customDir Custom directory (opsional)
     * @return string
     */
    function cdn_url(string $path, ?string $customDir = null): string
    {
        return app('cdn')->url($path, $customDir);
    }
}

if (!function_exists('cdn_enabled')) {
    /**
     * Check apakah CDN aktif.
     * Mengambil nilai dari environment variable CORE_CDN_ENABLED
     *
     * @return bool
     */
    function cdn_enabled(): bool
    {
        return app('cdn')->enabled();
    }
}
