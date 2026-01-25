<?php

namespace Bale\Core\Support;

use Illuminate\Support\Str;

class Cdn
{
    /**
     * Cache untuk organization slug agar tidak query database berulang kali
     * @var string|null
     */
    protected static ?string $cachedOrganizationSlug = null;

    public static function enabled(): bool
    {
        return (bool) config('core.cdn.enabled');
    }

    public static function baseUrl(): ?string
    {
        $url = config('core.cdn.base_url');

        return $url ? rtrim($url, '/') : null;
    }

    public static function prefix(): string
    {
        return trim(config('core.cdn.prefix'), '/');
    }

    /**
     * Get organization slug from database with caching
     * Menggunakan helper organization_slug() yang sudah ada
     */
    protected static function organizationSlug(): string
    {
        if (static::$cachedOrganizationSlug === null) {
            static::$cachedOrganizationSlug = organization_slug() ?? '';
        }

        return static::$cachedOrganizationSlug;
    }

    /**
     * Generate CDN URL dengan format: base_url/bucket/organization_slug/path
     * Jika path diawali dengan 'shared/', organization_slug akan diabaikan.
     * 
     * Contoh Org: https://cdn.ponorogo.go.id/bale/dinas-pendidikan/thumbnails/logo.png
     * Contoh Shared: https://cdn.ponorogo.go.id/bale/shared/logo-png.png
     */
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        if (!static::enabled() || !static::baseUrl()) {
            return static::fallback($path);
        }

        $orgSlug = static::organizationSlug();

        // Jika path diawali dengan organization slug, hapus agar tidak double
        if ($orgSlug && Str::startsWith($path, $orgSlug . '/')) {
            $path = Str::after($path, $orgSlug . '/');
        }

        $segments = [
            static::baseUrl(),
            static::prefix(),
        ];

        // Jika bukan path shared, tambahkan organization slug
        if (!Str::startsWith($path, 'shared/')) {
            $segments[] = $orgSlug;
        }

        $segments[] = $path;

        return implode('/', array_filter(array_map(
            fn($v) => trim($v, '/'),
            $segments
        )));
    }

    /**
     * Alias untuk url() method
     */
    public static function asset(string $path): string
    {
        return static::url($path);
    }

    protected static function fallback(string $path): string
    {
        return '/' . $path;
    }
}
