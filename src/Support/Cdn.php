<?php

namespace Bale\Core\Support;

use Illuminate\Support\Str;

class Cdn
{
    /**
     * Cache untuk organization slug agar tidak query database berulang kali
     * @var string|null
     */
    protected ?string $cachedOrganizationSlug = null;

    public function enabled(): bool
    {
        return (bool) config('core.cdn.enabled');
    }

    public function baseUrl(): ?string
    {
        $url = config('core.cdn.base_url');

        return $url ? rtrim($url, '/') : null;
    }

    public function prefix(): string
    {
        return trim(config('core.cdn.prefix'), '/');
    }

    /**
     * Get organization slug from database with caching
     * Menggunakan helper organization_slug() yang sudah ada
     */
    protected function organizationSlug(): string
    {
        if ($this->cachedOrganizationSlug === null) {
            $this->cachedOrganizationSlug = function_exists('organization_slug')
                ? (organization_slug() ?? '')
                : '';
        }

        return $this->cachedOrganizationSlug;
    }

    /**
     * Generate CDN URL dengan format: base_url/prefix/organization_slug/path
     * 
     * Mendukung 3 mode:
     * 1. Organization-specific: otomatis menambahkan organization_slug jika berada di CMS package
     * 2. Shared: path diawali 'shared/' akan mengabaikan organization_slug
     * 3. Custom directory: gunakan parameter $customDir untuk specify direktori custom
     * 
     * @param string $path Path file yang akan di-CDN-kan
     * @param string|null $customDir Custom directory (opsional), akan menggantikan organization_slug
     * 
     * @return string
     * 
     * Contoh:
     * - Organization: cdn_url('thumbnails/logo.png') 
     *   → https://cdn.example.com/bale/dinas-pendidikan/thumbnails/logo.png
     * 
     * - Shared: cdn_url('shared/logo.png')
     *   → https://cdn.example.com/bale/shared/logo.png
     * 
     * - Custom: cdn_url('images/banner.jpg', 'custom-folder')
     *   → https://cdn.example.com/bale/custom-folder/images/banner.jpg
     */
    public function url(string $path, ?string $customDir = null): string
    {
        $path = ltrim($path, '/');

        if (!$this->enabled() || !$this->baseUrl()) {
            return $this->fallback($path);
        }

        $segments = [
            $this->baseUrl(),
            $this->prefix(),
        ];

        // Jika ada custom directory, gunakan itu
        if ($customDir !== null) {
            $segments[] = trim($customDir, '/');
        }
        // Jika path diawali 'shared/', skip organization_slug
        elseif (!Str::startsWith($path, 'shared/')) {
            // Hanya tambahkan organization_slug jika bukan shared dan tidak ada custom dir
            $orgSlug = $this->organizationSlug();

            // Jika path diawali dengan organization slug, hapus agar tidak double
            if ($orgSlug && Str::startsWith($path, $orgSlug . '/')) {
                $path = Str::after($path, $orgSlug . '/');
            }

            if ($orgSlug) {
                $segments[] = $orgSlug;
            }
        }

        $segments[] = $path;

        return implode('/', array_filter(array_map(
            fn($v) => trim($v, '/'),
            $segments
        )));
    }

    /**
     * Alias untuk url() method
     * 
     * @param string $path Path file
     * @param string|null $customDir Custom directory (opsional)
     * @return string
     */
    public function asset(string $path, ?string $customDir = null): string
    {
        return $this->url($path, $customDir);
    }

    protected function fallback(string $path): string
    {
        return '/' . $path;
    }
}
