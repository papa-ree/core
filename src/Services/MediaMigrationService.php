<?php

namespace Bale\Core\Services;

use Bale\Core\Support\Cdn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * MediaMigrationService
 *
 * Handles migration of media referenced inside an EditorJS content payload
 * that was previously produced by {@see PostCleanerService}. Placeholders
 * (`image-placeholder` / `media-placeholder`) are downloaded from the legacy
 * WordPress host, converted to WebP via {@see ImageService}, uploaded to the
 * active storage disk (public on dev / s3 on production) and replaced with
 * native EditorJS blocks pointing at the CDN.
 *
 * Media can be sourced from either:
 *   - the live legacy WordPress site (HTTP download), or
 *   - a copied `wp-content/uploads` directory (`--source-dir`), which is useful
 *     when the Bale server cannot reach the legacy host.
 *
 * This service is intentionally decoupled from the WP SQL migrator so that the
 * migrator only has to create placeholders — all media heavy-lifting lives here.
 *
 * Safety: every downloaded binary is inspected by {@see MediaSecurityScanner}
 * *before* it touches the storage disk. Backdoors, embedded/injected code and
 * hidden links cause the file to be rejected (the placeholder is kept so the
 * run stays retryable) and, when configured, quarantined for manual review.
 */
class MediaMigrationService
{
    /**
     * Optional base URL used to resolve relative / protocol-relative media URLs
     * (e.g. legacy WordPress site root: https://dindik.ponorogo.go.id).
     */
    protected ?string $baseUrl = null;

    /**
     * Optional local path to a copied `wp-content/uploads` directory. When set,
     * media is read from disk first and only falls back to HTTP when missing.
     */
    protected ?string $sourceDir = null;

    /**
     * Optional directory used to quarantine files rejected by the security
     * scanner (raw payload + a JSON report) for later manual inspection.
     */
    protected ?string $quarantineDir = null;

    /**
     * When true, any finding (even low/medium severity) blocks the file.
     */
    protected bool $strict = false;

    /**
     * Result of the most recent security scan, exposed for reporting.
     */
    protected ?MediaScanResult $lastScan = null;

    public function __construct(
        protected ?ImageService $images = null,
        protected ?MediaSecurityScanner $scanner = null,
    ) {
        $this->images ??= app(ImageService::class);
        $this->scanner ??= app(MediaSecurityScanner::class);
    }

    /**
     * Set the legacy base URL used to resolve relative media paths.
     */
    public function setBaseUrl(?string $baseUrl): static
    {
        $this->baseUrl = $baseUrl ? rtrim($baseUrl, '/') : null;

        return $this;
    }

    /**
     * Set a local directory containing the legacy WordPress uploads. The path
     * may point at the uploads root or at its parent `wp-content` directory.
     */
    public function setSourceDir(?string $sourceDir): static
    {
        $this->sourceDir = $sourceDir ? rtrim($sourceDir, '/\\') : null;

        return $this;
    }

    /**
     * Directory where files rejected by the security scanner are written for
     * manual review. Set to null to discard them (they are always logged).
     */
    public function setQuarantineDir(?string $quarantineDir): static
    {
        $this->quarantineDir = $quarantineDir ? rtrim($quarantineDir, '/\\') : null;

        return $this;
    }

    /**
     * When enabled, any security finding (low/medium/high) blocks a file.
     */
    public function setStrict(bool $strict = true): static
    {
        $this->strict = $strict;

        return $this;
    }

    /**
     * Result of the most recent security scan performed by this service.
     */
    public function lastScan(): ?MediaScanResult
    {
        return $this->lastScan;
    }

    /**
     * Storage disk used for media (mirrors {@see ImageService::disk()}).
     */
    public function disk(): string
    {
        return app()->isProduction() ? 's3' : 'public';
    }

    /**
     * Does the given EditorJS payload still contain a media placeholder?
     *
     * @param  array<string, mixed>  $content
     */
    public function hasPlaceholders(array $content): bool
    {
        foreach ($content['blocks'] ?? [] as $block) {
            if (in_array($block['type'] ?? null, ['image-placeholder', 'media-placeholder'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Migrate every placeholder found in the given EditorJS content array.
     *
     * @param  array<string, mixed>  $content  Decoded EditorJS payload.
     * @param  string  $slug  Tenant/organization slug used as the storage directory
     *                        (e.g. "dindik").
     * @return array{content: array<string, mixed>, migrated: int, failed: int, skipped: int, blocked: int, errors: array<int, string>, security: array<int, string>}
     */
    public function migrateContent(array $content, string $slug): array
    {
        $stats = ['migrated' => 0, 'failed' => 0, 'skipped' => 0, 'blocked' => 0, 'errors' => [], 'security' => []];
        $blocks = $content['blocks'] ?? [];

        if (! is_array($blocks)) {
            return ['content' => $content] + $stats;
        }

        $newBlocks = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;

            if ($type === 'image-placeholder') {
                $replacement = $this->migrateImageBlock($block, $slug, $stats);
                // Keep the placeholder on failure so the run stays retryable.
                $newBlocks[] = $replacement ?? $block;

                continue;
            }

            if ($type === 'media-placeholder' && ($block['data']['kind'] ?? null) === 'video') {
                $replacement = $this->migrateVideoBlock($block, $slug, $stats);
                $newBlocks[] = $replacement ?? $block;

                continue;
            }

            if (in_array($type, ['image', 'paragraph', 'embed', 'raw', 'list', 'header', 'quote'], true)) {
                $stats['skipped']++;
            }

            $newBlocks[] = $block;
        }

        $content['blocks'] = $newBlocks;

        return ['content' => $content] + $stats;
    }

    /**
     * Convert an `image-placeholder` block into a native EditorJS `image` block.
     *
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>|null
     */
    protected function migrateImageBlock(array $block, string $slug, array &$stats): ?array
    {
        $src = $block['data']['wp_src'] ?? null;
        $caption = $block['data']['caption'] ?? '';

        if (empty($src)) {
            $stats['skipped']++;

            return null;
        }

        $url = $this->storeImageFromUrl($src, $slug);

        if ($url === null) {
            if ($this->lastScan?->isBlocked()) {
                $stats['blocked']++;
                $stats['security'][] = 'Image blocked: '.Str::limit($src, 80).' — '.$this->lastScan->summary();
            } else {
                $stats['failed']++;
                $stats['errors'][] = 'Image failed: '.Str::limit($src, 90);
            }

            return null;
        }

        $stats['migrated']++;

        return [
            'type' => 'image',
            'data' => [
                'file' => ['url' => $url],
                'caption' => $caption,
                'stretched' => false,
                'withBorder' => false,
                'withBackground' => false,
            ],
        ];
    }

    /**
     * Convert a `media-placeholder` (video) block into an EditorJS `embed` block.
     *
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>|null
     */
    protected function migrateVideoBlock(array $block, string $slug, array &$stats): ?array
    {
        $src = $block['data']['wp_src'] ?? null;

        if (empty($src)) {
            $stats['skipped']++;

            return null;
        }

        $url = $this->storeVideoFromUrl($src, $slug);

        if ($url === null) {
            if ($this->lastScan?->isBlocked()) {
                $stats['blocked']++;
                $stats['security'][] = 'Video blocked: '.Str::limit($src, 80).' — '.$this->lastScan->summary();
            } else {
                $stats['failed']++;
                $stats['errors'][] = 'Video failed: '.Str::limit($src, 90);
            }

            return null;
        }

        $stats['migrated']++;

        return [
            'type' => 'embed',
            'data' => [
                'service' => 'wp-video',
                'source' => $this->normalizeUrl($src),
                'embed' => $url,
                'width' => 580,
                'height' => 320,
                'caption' => '',
            ],
        ];
    }

    /**
     * Download an image from a legacy URL, re-encode it to WebP and upload it.
     *
     * WordPress keeps several resized variants (`name-1024x576.jpg`). We prefer
     * the full-size original (suffix stripped) and fall back to the URL as-is.
     *
     * @return string|null CDN URL on success, null on failure.
     */
    public function storeImageFromUrl(string $url, string $slug): ?string
    {
        $this->lastScan = null;

        foreach ($this->candidateImageUrls($url) as $candidate) {
            $tmp = null;

            try {
                $binary = $this->fetchBinary($candidate);

                if ($binary === null || $binary === '') {
                    continue;
                }

                // Security first: reject backdoors / embedded code / hidden
                // links before anything touches the storage disk.
                $scan = $this->scanner->scan($binary, $candidate);

                if ($scan->isBlocked() || ($this->strict && ! $scan->isClean())) {
                    $this->lastScan = $scan;
                    $this->quarantine($binary, $candidate, $scan);
                    Log::warning('WP media blocked by security scanner', [
                        'url' => $candidate,
                        'severity' => $scan->severity,
                        'findings' => $scan->codes(),
                    ]);

                    return null;
                }

                if (@getimagesizefromstring($binary) === false) {
                    Log::warning('WP media is not a valid image', ['url' => $candidate]);

                    continue;
                }

                // Record a non-blocking scan (warnings) on the last attempt.
                $this->lastScan = $scan;

                // ImageService expects a path; use a temp file for reliability.
                $tmp = tempnam(sys_get_temp_dir(), 'bale-wp-img');
                file_put_contents($tmp, $binary);

                $storedPath = $this->images->toWebp(
                    $tmp,
                    quality: 80,
                    maxWidth: 1200,
                    directory: trim($slug, '/').'/images',
                );

                if ($storedPath === null || $storedPath === '') {
                    continue;
                }

                return Cdn::url('images/'.basename($storedPath));
            } catch (Throwable $e) {
                Log::warning('WP media migration error', ['url' => $candidate, 'error' => $e->getMessage()]);
            } finally {
                if ($tmp && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }

        return null;
    }

    /**
     * Download a WordPress featured image and store it as a Bale thumbnail.
     *
     * Thumbnails mirror what `EditPost::uploadThumbnail()` does: WebP quality
     * 80, maxWidth 800, directory `{slug}/thumbnails`, and only the basename is
     * written to `posts.thumbnail`.
     *
     * The source host is allow-listed against the configured base URL: a dump
     * is untrusted input, and `guid` is just a string — without this check the
     * migrator would happily download from any host an attacker put there.
     *
     * @return string|null File name (basename) on success, null on failure/block.
     */
    public function storeThumbnailFromUrl(string $url, string $slug): ?string
    {
        $this->lastScan = null;

        foreach ($this->candidateImageUrls($url) as $candidate) {
            if (! $this->isAllowedSource($candidate)) {
                Log::warning('WP thumbnail source rejected (host not allowed)', ['url' => $candidate]);

                continue;
            }

            $tmp = null;

            try {
                $binary = $this->fetchBinary($candidate);

                if ($binary === null || $binary === '') {
                    continue;
                }

                $scan = $this->scanner->scan($binary, $candidate);

                if ($scan->isBlocked() || ($this->strict && ! $scan->isClean())) {
                    $this->lastScan = $scan;
                    $this->quarantine($binary, $candidate, $scan);
                    Log::warning('WP thumbnail blocked by security scanner', [
                        'url' => $candidate,
                        'severity' => $scan->severity,
                        'findings' => $scan->codes(),
                    ]);

                    return null;
                }

                if (@getimagesizefromstring($binary) === false) {
                    Log::warning('WP thumbnail is not a valid image', ['url' => $candidate]);

                    continue;
                }

                $this->lastScan = $scan;

                $tmp = tempnam(sys_get_temp_dir(), 'bale-wp-thumb');
                file_put_contents($tmp, $binary);

                $storedPath = $this->images->toWebp(
                    $tmp,
                    quality: 80,
                    maxWidth: 800,
                    directory: trim($slug, '/').'/thumbnails',
                );

                if ($storedPath === null || $storedPath === '') {
                    continue;
                }

                return basename($storedPath);
            } catch (Throwable $e) {
                Log::warning('WP thumbnail migration error', ['url' => $candidate, 'error' => $e->getMessage()]);
            } finally {
                if ($tmp && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }

        return null;
    }

    /**
     * Is this URL safe to download from?
     *
     * A local uploads mirror always wins (no network involved). Otherwise the
     * host must match `setBaseUrl()`, so a tampered dump cannot turn the
     * migration into an SSRF vector.
     */
    protected function isAllowedSource(string $absolute): bool
    {
        if ($this->sourceDir !== null && $this->localPathFor($absolute) !== null) {
            return true;
        }

        if ($this->baseUrl === null) {
            return false;
        }

        $baseHost = parse_url($this->baseUrl, PHP_URL_HOST);
        $host = parse_url($absolute, PHP_URL_HOST);

        return is_string($baseHost) && is_string($host) && strcasecmp($baseHost, $host) === 0;
    }

    /**
     * Download a (video) file from a legacy URL and re-upload it as-is.
     *
     * @return string|null CDN URL on success, null on failure.
     */
    public function storeVideoFromUrl(string $url, string $slug): ?string
    {
        $this->lastScan = null;

        try {
            $binary = $this->fetchBinary($this->normalizeUrl($url));

            if ($binary === null || $binary === '') {
                return null;
            }

            $hint = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            $scan = $this->scanner->scan($binary, 'file'.($hint !== '' ? '.'.$hint : ''), 'video');
            $this->lastScan = $scan;

            if ($scan->isBlocked() || ($this->strict && ! $scan->isClean())) {
                $this->quarantine($binary, $url, $scan);
                Log::warning('WP video blocked by security scanner', [
                    'url' => $url,
                    'severity' => $scan->severity,
                    'findings' => $scan->codes(),
                ]);

                return null;
            }

            // Prefer the extension proven by the file content, never the URL.
            $extension = $this->scanner->safeVideoExtension($binary, $hint) ?? 'mp4';
            $filename = Str::uuid().'.'.$extension;
            $path = trim($slug, '/').'/images/'.$filename;

            Storage::disk($this->disk())->put($path, $binary, ['visibility' => 'public']);

            return Cdn::url('images/'.$filename);
        } catch (Throwable $e) {
            Log::warning('WP video migration error', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Persist a rejected file (and a JSON report) for manual inspection.
     *
     * The payload is always written with a neutral `.bin` extension so it can
     * never be executed if the quarantine directory happens to be web-visible.
     */
    protected function quarantine(string $binary, string $source, MediaScanResult $scan): void
    {
        if ($this->quarantineDir === null) {
            return;
        }

        try {
            if (! is_dir($this->quarantineDir)) {
                @mkdir($this->quarantineDir, 0775, true);
            }

            $base = date('Ymd-His').'-'.substr(sha1($source.microtime(true)), 0, 12);
            $file = $this->quarantineDir.DIRECTORY_SEPARATOR.$base.'.bin';

            @file_put_contents($file, $binary);

            @file_put_contents($file.'.json', json_encode([
                'source' => $source,
                'scanned_at' => date('c'),
                'scan' => $scan->toArray(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            Log::warning('Failed to quarantine rejected media', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch a media binary either from the local uploads mirror (preferred when
     * configured and present) or from the legacy host over HTTP.
     */
    protected function fetchBinary(string $url): ?string
    {
        if ($this->sourceDir && ($local = $this->localPathFor($url)) !== null) {
            $binary = @file_get_contents($local);

            return $binary === false ? null : $binary;
        }

        $absolute = $this->normalizeUrl($url);

        try {
            $response = Http::timeout(120)->retry(2, 500)->get($absolute);

            if (! $response->successful()) {
                Log::warning('WP media download failed', ['url' => $absolute, 'status' => $response->status()]);

                return null;
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::warning('WP media download error', ['url' => $absolute, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Map a legacy `/wp-content/uploads/...` URL to a file inside the locally
     * mirrored uploads directory, if it exists.
     */
    protected function localPathFor(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $path = str_replace('\\', '/', $path);

        if (! preg_match('#/uploads/(.+)$#', $path, $m)) {
            return null;
        }

        $relative = $m[1];

        // If the configured dir is the wp-content parent, step into uploads.
        $candidates = [
            $this->sourceDir.'/uploads/'.$relative,
            $this->sourceDir.'/'.$relative,
        ];

        foreach ($candidates as $candidate) {
            $candidate = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Build the list of candidate absolute URLs to try for an image, preferring
     * the full-size original over a WordPress resized variant.
     *
     * @return array<int, string>
     */
    protected function candidateImageUrls(string $url): array
    {
        $url = $this->normalizeUrl($url);

        $candidates = [];

        $fullSize = preg_replace('#-\d+x\d+(\.[a-zA-Z0-9]+)$#', '$1', $url);
        if (is_string($fullSize) && $fullSize !== $url) {
            $candidates[] = $fullSize;
        }

        $candidates[] = $url;

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Resolve relative / protocol-relative URLs against the configured base URL.
     */
    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return ($this->baseUrl ?? '').$url;
        }

        return $url;
    }
}
