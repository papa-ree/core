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
        $this->baseUrl = rtrim(config('core.analytics.umami.url', ''), '/');
        $this->apiKey = config('core.analytics.umami.api_key', '');
        $this->cacheTtl = (int) config('core.analytics.umami.cache_ttl', 300);
        $this->timezone = config('core.analytics.umami.timezone', 'Asia/Jakarta');
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
     * @return array|null  Null jika tidak dikonfigurasi atau Umami tidak tersedia
     */
    public function getStats(int $days = 7): ?array
    {
        $config = $this->getAnalyticsConfig();

        if (!$config) {
            return null;
        }

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning('[UmamiService] Konfigurasi Umami tidak lengkap (UMAMI_URL / UMAMI_API_KEY).');
            return null;
        }

        $cacheKey = "umami_stats_{$config->bale_id}_{$days}d";
        $cacheStore = $this->getTenantCacheStore();

        return $cacheStore->remember($cacheKey, $this->cacheTtl, function () use ($config, $days) {
            [$startAt, $endAt] = $this->getDateRange($days);

            try {
                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/websites/{$config->website_id}/stats", [
                        'startAt' => $startAt,
                        'endAt' => $endAt,
                    ]);

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
     * @param  int  $days  Jumlah hari terakhir
     * @return array|null  Null jika tidak dikonfigurasi atau Umami tidak tersedia
     */
    public function getPageviews(int $days = 7): ?array
    {
        $config = $this->getAnalyticsConfig();

        if (!$config) {
            return null;
        }

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning('[UmamiService] Konfigurasi Umami tidak lengkap (UMAMI_URL / UMAMI_API_KEY).');
            return null;
        }

        $cacheKey = "umami_pageviews_{$config->bale_id}_{$days}d";
        $cacheStore = $this->getTenantCacheStore();

        return $cacheStore->remember($cacheKey, $this->cacheTtl, function () use ($config, $days) {
            [$startAt, $endAt] = $this->getDateRange($days);

            try {
                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/websites/{$config->website_id}/pageviews", [
                        'startAt' => $startAt,
                        'endAt' => $endAt,
                        'unit' => 'day',
                        'timezone' => $this->timezone,
                    ]);

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
    protected function getDateRange(int $days): array
    {
        $now = now($this->timezone);
        $endAt = $now->copy()->endOfDay()->getTimestampMs();
        $startAt = $now->copy()->subDays($days - 1)->startOfDay()->getTimestampMs();

        return [$startAt, $endAt];
    }
}
