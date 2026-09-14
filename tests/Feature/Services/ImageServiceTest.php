<?php

namespace Bale\Core\Tests\Feature\Services;

use Bale\Core\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

beforeEach(function () {
    if (! function_exists('imagewebp')) {
        $this->markTestSkipped('GD imagewebp tidak tersedia.');
    }
});

it('mengonversi file path jpg menjadi webp di disk public', function () {
    $gd = imagecreatetruecolor(2000, 1000);
    imagefill($gd, 0, 0, imagecolorallocate($gd, 120, 180, 240));
    $path = tempnam(sys_get_temp_dir(), 'bale-test-');
    imagejpeg($gd, $path, 90);
    imagedestroy($gd);

    $directory = 'cms-test-'.Str::uuid();

    try {
        $stored = app(ImageService::class)->toWebp($path, directory: $directory);

        expect($stored)->toEndWith('.webp')
            ->and(Storage::disk('public')->exists($stored))->toBeTrue();

        $width = (new ImageManager(Driver::class))
            ->read(Storage::disk('public')->get($stored))
            ->width();

        expect($width)->toBe(1920);
    } finally {
        Storage::disk('public')->deleteDirectory($directory);
        @unlink($path);
    }
});

it('mengonversi UploadedFile menjadi webp dengan basename uuid', function () {
    $gd = imagecreatetruecolor(800, 600);
    $path = tempnam(sys_get_temp_dir(), 'bale-test-');
    imagejpeg($gd, $path, 90);
    imagedestroy($gd);

    $file = new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
    $directory = 'cms-test-'.Str::uuid();

    try {
        $stored = app(ImageService::class)->toWebp($file, directory: $directory);

        expect($stored)->toMatch('/^'.preg_quote($directory, '/').'\/[0-9a-f-]{36}\.webp$/')
            ->and(Storage::disk('public')->exists($stored))->toBeTrue();
    } finally {
        Storage::disk('public')->deleteDirectory($directory);
        @unlink($path);
    }
});

it('tidak menaikkan resolusi gambar berukuran kecil', function () {
    $gd = imagecreatetruecolor(100, 50);
    $path = tempnam(sys_get_temp_dir(), 'bale-test-');
    imagejpeg($gd, $path, 90);
    imagedestroy($gd);

    $directory = 'cms-test-'.Str::uuid();

    try {
        $stored = app(ImageService::class)->toWebp($path, directory: $directory);

        $size = (new ImageManager(Driver::class))
            ->read(Storage::disk('public')->get($stored));

        expect($size->width())->toBe(100)
            ->and($size->height())->toBe(50);
    } finally {
        Storage::disk('public')->deleteDirectory($directory);
        @unlink($path);
    }
});

it('convertExisting mengubah jpg di disk menjadi webp tanpa menyentuh file asli', function () {
    $gd = imagecreatetruecolor(640, 480);
    $tmp = tempnam(sys_get_temp_dir(), 'bale-test-');
    imagejpeg($gd, $tmp, 90);
    imagedestroy($gd);

    try {
        Storage::disk('public')->put('cms-test-old.jpg', file_get_contents($tmp));
        $newPath = app(ImageService::class)->convertExisting('cms-test-old.jpg', 'public');

        expect($newPath)->toBe('cms-test-old.webp')
            ->and(Storage::disk('public')->exists('cms-test-old.webp'))->toBeTrue()
            ->and(Storage::disk('public')->exists('cms-test-old.jpg'))->toBeTrue();
    } finally {
        Storage::disk('public')->delete(['cms-test-old.jpg', 'cms-test-old.webp']);
        @unlink($tmp);
    }
});

it('convertExisting mengembalikan null untuk file yang sudah webp', function () {
    $path = 'cms-test-already.webp';

    Storage::disk('public')->put($path, 'not-really-webp');

    try {
        expect(app(ImageService::class)->convertExisting($path, 'public'))->toBeNull();
    } finally {
        Storage::disk('public')->delete($path);
    }
});