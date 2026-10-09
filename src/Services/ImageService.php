<?php

namespace Bale\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\EncodedImage;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * ImageService
 *
 * Layanan pemrosesan gambar terpusat berbasis Intervention Image v3.
 * Semua upload gambar di Bale di-encode ulang menjadi WebP (quality 80,
 * max width 1920, tidak pernah memperbesar) lalu disimpan via Storage facade
 * ke disk `public` (dev) / `s3` (prod) — per Resource: docs/cms/PRD.md.
 *
 * Catatan: Laravel 13 menyediakan `Illuminate\Support\Facades\Image` tetapi
 * membutuhkan `intervention/image:^4.0`. Monorepo ini menginstal `^3.11`
 * (constraint `^4.0` gagal resolve dan `intervention/image-laravel` bentrok
 * dengan `illuminate/support`), sehingga API v3 digunakan langsung:
 * `(new ImageManager(driver))->read(...)->orient()->scaleDown(width:1920)->toWebp(quality:80)`.
 */
class ImageService
{
    /**
     * Nama disk tujuan: `public` saat dev, `s3` saat production.
     */
    public function disk(): string
    {
        return app()->isProduction() ? 's3' : 'public';
    }

    /**
     * Root direktori penyimpanan default (relatif terhadap disk).
     */
    public function rootDirectory(): string
    {
        return 'cms';
    }

    /**
     * Cek apakah driver aktif mendukung output WebP.
     */
    public function supportsWebp(): bool
    {
        if (function_exists('imagewebp')) {
            return true;
        }

        return class_exists(\Imagick::class);
    }

    /**
     * Membuat instance ImageManager sesuai driver yang dikonfigurasi
     * (`IMAGE_DRIVER` = gd | imagick, default gd). Fallback ke gd bila
     * driver yang diminta tidak tersedia.
     */
    protected function manager(): ImageManager
    {
        $driver = strtolower((string) (config('images.driver', config('images.default', env('IMAGE_DRIVER', 'gd')))));

        $class = ($driver === 'imagick' && class_exists(\Imagick::class))
            ? Driver::class
            : \Intervention\Image\Drivers\Gd\Driver::class;

        return new ImageManager($class);
    }

    /**
     * Konversi file upload (Livewire TemporaryUploadedFile, UploadedFile, path,
     * atau binary) menjadi WebP dan simpan ke storage.
     *
     * @param  UploadedFile|string|resource  $file  File yang akan dikonversi
     * @param  int  $quality  Kualitas output WebP (1–100, default 80)
     * @param  int  $maxWidth  Lebar maksimum gambar (proportional, tidak pernah diperbesar)
     * @param  string|null  $directory  Sub-directory relatif disk (default "cms")
     * @return string Path file berakhiran `.webp` (mis. "cms/{uuid}.webp")
     */
    public function toWebp(UploadedFile|string $file, int $quality = 80, int $maxWidth = 1920, ?string $directory = null): string
    {
        $directory = $directory ?: $this->rootDirectory();
        $disk = $this->disk();

        // Fallback: jika webp tidak didukung, simpan file asli + peringatan log.
        // Per DoD: hanya webp — fallback ini dipertahankan sebagai jaring pengaman.
        if (! $this->supportsWebp()) {
            Log::warning('WebP encoding is not supported; saving original image instead.', [
                'directive' => 'ImageService::toWebp',
            ]);

            return Storage::disk($disk)->putFile($directory, $file);
        }

        $temp = $this->toTempFile($file);
        $path = $directory.'/'.Str::uuid().'.webp';

        try {
            $image = $this->manager()
                ->read($temp);

            if (function_exists('exif_read_data')) {
                $image = $image->orient();
            }

            $encoded = $image
                ->scaleDown(width: $maxWidth)
                ->toWebp(quality: $quality);

            return $this->storeEncoded($encoded, $path);
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }

    /**
     * Konversi file lama (jpg/jpeg/png) yang sudah tersimpan menjadi WebP.
     *
     * @param  string  $oldPath  Path relatif di dalam disk
     * @param  string|null  $disk  Disk tempat file berada (default sama dgn disk aktif)
     * @return string|null Path `.webp` baru, atau null bila gagal / bukan gambar raster
     */
    public function convertExisting(string $oldPath, ?string $disk = null, int $quality = 80, int $maxWidth = 1920): ?string
    {
        $disk = $disk ?: $this->disk();

        if (Str::endsWith(strtolower($oldPath), '.webp')) {
            return null;
        }

        $stream = null;

        try {
            $stream = Storage::disk($disk)->readStream($oldPath);

            if (! is_resource($stream)) {
                return null;
            }

            $image = $this->manager()->read($stream);

            if (function_exists('exif_read_data')) {
                $image = $image->orient();
            }

            $encoded = $image
                ->scaleDown(width: $maxWidth)
                ->toWebp(quality: $quality);

            $newPath = Str::beforeLast($oldPath, '.').'.webp';

            return $this->storeEncoded($encoded, $newPath);
        } catch (Throwable $e) {
            Log::warning('Image conversion failed.', [
                'path' => $oldPath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Simpan hasil encode ke storage dengan visibility public.
     */
    protected function storeEncoded(EncodedImage $encoded, string $path): string
    {
        Storage::disk($this->disk())->put($path, (string) $encoded, ['visibility' => 'public']);

        return $path;
    }

    /**
     * Salin input menjadi file sementara di disk lokal agar aman dibaca
     * oleh Intervention terlepas dari jenis input (upload Livewire, s3, dll).
     *
     * @param  UploadedFile|string|resource  $file
     */
    protected function toTempFile(UploadedFile|string $file): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'bale-img');

        if ($file instanceof UploadedFile) {
            file_put_contents($temp, $file->get());
        } elseif (is_resource($file)) {
            $target = fopen($temp, 'wb');
            if ($target) {
                stream_copy_to_stream($file, $target);
                fclose($target);
            }
            rewind($file);
        } elseif (is_file($file)) {
            copy($file, $temp);
        } else {
            file_put_contents($temp, $file);
        }

        return $temp;
    }
}
