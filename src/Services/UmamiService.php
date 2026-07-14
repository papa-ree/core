<?php

namespace Bale\Core\Services;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UmamiService
{
    /**
     * Umami base URL (dari env UMAMI_URL).
     */
    protected string $baseUrl;

    /**
     * Umami API Token (dari env UMAMI_API_KEY).
     */
    protected string $apiKey;

    /**
     * Cache TTL dalam detik (dari env UMAMI_CACHE_TTL, default 300).
     */
    protected int $cacheTtl;

    /**
     * Timezone untuk penghitungan tanggal & API request.
     * Harus sama dengan timezone yang digunakan di dashboard Umami.
     */
    protected string $timezone;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('core.analytics.umami.url') ?: env('UMAMI_URL', ''), '/');
        $this->apiKey = config('core.analytics.umami.api_key') ?: env('UMAMI_API_KEY', '');
        $this->cacheTtl = (int) (config('core.analytics.umami.cache_ttl') ?: env('UMAMI_CACHE_TTL', 300));
        $this->timezone = config('core.analytics.umami.timezone') ?: env('UMAMI_TIMEZONE', 'Asia/Jakarta');

        // Log peringatan segera jika konfigurasi utama tidak terbaca sama sekali
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::error('[UmamiService] Gagal membaca konfigurasi dari config(core) maupun env.', [
                'url_source' => config('core.analytics.umami.url') ? 'config' : (env('UMAMI_URL') ? 'env' : 'missing'),
                'key_source' => config('core.analytics.umami.api_key') ? 'config' : (env('UMAMI_API_KEY') ? 'env' : 'missing'),
            ]);
        }
    }

    /**
     * Buat HTTP client dengan header yang diperlukan.
     *
     * Cloudflare Managed Challenge memblokir request PHP default karena
     * tidak ada browser fingerprint. Header Accept + User-Agent yang
     * menyerupai browser cukup untuk melewati managed challenge.
     */
    protected function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->timeout(15)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (compatible; BaleAnalytics/1.0)',
            ]);
    }

    /**
     * Ambil statistik ringkasan dari Umami (total visitors, pageviews, bounce rate, dll).
     *
     * @param  int  $days  Jumlah hari terakhir yang ingin diambil datanya
     * @param  string|null  $url  URL path filter (misal: /loker/my-slug)
     * @return array|null  Null jika tidak dikonfigurasi atau Umami tidak tersedia
     */
    public function getStats($days = 7, ?string $url = null): ?array
    {
        $config = $this->getAnalyticsConfig();

        if (!$config) {
            return null;
        }

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning('[UmamiService] Konfigurasi Umami tidak lengkap (UMAMI_URL / UMAMI_API_KEY).');
            return null;
        }

        $cacheKeyString = is_array($days) ? implode('_', $days) : $days;
        $cacheKey = "umami_stats_{$config->bale_id}_{$cacheKeyString}" . ($url ? "_" . md5($url) : "");
        $cacheStore = $this->getTenantCacheStore();

        return $cacheStore->remember($cacheKey, $this->cacheTtl, function () use ($config, $days, $url) {
            [$startAt, $endAt] = $this->getDateRange($days);

            try {
                $params = [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                ];

                if ($url) {
                    $params['url'] = $url;
                    $params['path'] = $url;
                }

                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/websites/{$config->website_id}/stats", $params);

                if ($response->failed()) {
                    Log::error('[UmamiService] getStats failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::error('[UmamiService] getStats exception', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Ambil data pageviews per hari dari Umami (untuk chart).
     *
     * @param  mixed  $days  Jumlah hari terakhir (int) atau array [$startAt, $endAt]
     * @param  string|null  $url  URL path filter (misal: /loker/my-slug)
     * @return array|null  Null jika tidak dikonfigurasi atau Umami tidak tersedia
     */
    public function getPageviews($days = 7, ?string $url = null): ?array
    {
        $config = $this->getAnalyticsConfig();

        if (!$config) {
            return null;
        }

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning('[UmamiService] Konfigurasi Umami tidak lengkap (UMAMI_URL / UMAMI_API_KEY).');
            return null;
        }

        $cacheKeyString = is_array($days) ? implode('_', $days) : $days;
        $cacheKey = "umami_pageviews_{$config->bale_id}_{$cacheKeyString}" . ($url ? "_" . md5($url) : "");
        $cacheStore = $this->getTenantCacheStore();

        return $cacheStore->remember($cacheKey, $this->cacheTtl, function () use ($config, $days, $url) {
            [$startAt, $endAt] = $this->getDateRange($days);

            try {
                $params = [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                    'unit' => 'day',
                    'timezone' => $this->timezone,
                ];

                if ($url) {
                    $params['url'] = $url;
                    $params['path'] = $url;
                }

                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/websites/{$config->website_id}/pageviews", $params);

                if ($response->failed()) {
                    Log::error('[UmamiService] getPageviews failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::error('[UmamiService] getPageviews exception', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }


    /**
     * Ambil URL metrics dari Umami (total pageviews per URL path).
     *
     * Endpoint /api/websites/{id}/metrics?type=url adalah satu-satunya endpoint
     * yang secara andal memberikan data terpisah per URL path.
     * Endpoint stats & pageviews dengan filter `url` seringkali diabaikan oleh Umami.
     *
     * Response: [{x: '/jobs/slug', y: 9}, {x: '/jobs/other', y: 3}, ...]
     *
     * @param  int  $days   Jumlah hari terakhir
     * @param  int  $limit  Batas jumlah URL (default 500)
     * @return array|null
     */
    public function getUrlMetrics(int $days = 30, int $limit = 500): ?array
    {
        $config = $this->getAnalyticsConfig();

        if (!$config) {
            return null;
        }

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning('[UmamiService] Konfigurasi Umami tidak lengkap (UMAMI_URL / UMAMI_API_KEY).');
            return null;
        }

        $cacheKey = "umami_url_metrics_{$config->bale_id}_{$days}d_lmt{$limit}";
        $cacheStore = $this->getTenantCacheStore();

        return $cacheStore->remember($cacheKey, $this->cacheTtl, function () use ($config, $days, $limit) {
            [$startAt, $endAt] = $this->getDateRange($days);

            try {
                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/websites/{$config->website_id}/metrics", [
                        'startAt' => $startAt,
                        'endAt'   => $endAt,
                        'type'    => 'url',
                        'limit'   => $limit,
                    ]);

                if ($response->failed()) {
                    Log::error('[UmamiService] getUrlMetrics failed', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::error('[UmamiService] getUrlMetrics exception', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }


    /**
     * Ambil konfigurasi analytics dari tabel tenant_analytics di main DB
     * berdasarkan bale_id aktif dari session.
     */
    protected function getAnalyticsConfig(): ?object
    {
        $baleId = session('bale_active_uuid');

        if (!$baleId) {
            return null;
        }

        try {
            $record = DB::table('tenant_analytics')
                ->where('bale_id', $baleId)
                ->where('provider', 'umami')
                ->where('enabled', true)
                ->first();

            return $record ?: null;
        } catch (\Throwable $e) {
            Log::error('[UmamiService] Gagal membaca tenant_analytics', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Ambil cache store yang terhubung ke database tenant aktif.
     * Fallback ke default cache store jika tenant connection tidak tersedia.
     */
    protected function getTenantCacheStore(): CacheRepository
    {
        try {
            // Gunakan tenant DB connection yang sudah diaktifkan oleh TenantManager
            $tenantConnection = \Bale\Cms\Services\TenantConnectionService::connection();

            return Cache::store(
                $this->resolveTenantCacheDriver($tenantConnection)
            );
        } catch (\Throwable) {
            // Fallback ke default cache store
            return Cache::store();
        }
    }

    /**
     * Resolve cache driver yang menggunakan koneksi database tenant.
     * Membuat store "database" dengan connection tenant secara dinamis.
     */
    protected function resolveTenantCacheDriver(string $connection): string
    {
        $storeName = "umami_tenant_{$connection}";

        // Daftarkan cache store dinamis jika belum ada
        if (!config("cache.stores.{$storeName}")) {
            config([
                "cache.stores.{$storeName}" => [
                    'driver' => 'database',
                    'connection' => $connection,
                    'table' => 'cache',
                    'lock_connection' => $connection,
                ],
            ]);
        }

        return $storeName;
    }

    /**
     * Hitung startAt dan endAt dalam Unix timestamp milliseconds.
     * Tanggal dihitung berdasarkan $this->timezone agar sesuai dengan
     * tampilan di dashboard Umami (yang menggunakan timezone browser user).
     *
     * @return array{0: int, 1: int}
     */
    protected function getDateRange($days): array
    {
        if (is_array($days)) {
            return $days;
        }
        $now = now($this->timezone);
        $endAt = $now->copy()->endOfDay()->getTimestampMs();
        $startAt = $now->copy()->subDays((int)$days - 1)->startOfDay()->getTimestampMs();

        return [$startAt, $endAt];
    }
}
