<?php

use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Services\MediaScanResult;
use Bale\Core\Services\MediaSecurityScanner;
use Illuminate\Support\Str;

if (! function_exists('bale_scan_jpeg')) {
    function bale_scan_jpeg(int $w = 40, int $h = 30): string
    {
        $gd = imagecreatetruecolor($w, $h);
        imagefill($gd, 0, 0, imagecolorallocate($gd, 120, 180, 240));
        ob_start();
        imagejpeg($gd, null, 90);
        $bytes = (string) ob_get_clean();
        imagedestroy($gd);

        return $bytes;
    }
}

if (! function_exists('bale_scan_png')) {
    function bale_scan_png(int $w = 40, int $h = 30): string
    {
        $gd = imagecreatetruecolor($w, $h);
        ob_start();
        imagepng($gd);
        $bytes = (string) ob_get_clean();
        imagedestroy($gd);

        return $bytes;
    }
}

if (! function_exists('bale_scan_mp4')) {
    function bale_scan_mp4(): string
    {
        return "\x00\x00\x00\x18".'ftyp'.'mp42'."\x00\x00\x00\x00".'mp42isom';
    }
}

beforeEach(function () {
    if (! function_exists('imagecreatetruecolor')) {
        $this->markTestSkipped('GD tidak tersedia.');
    }

    $this->scanner = new MediaSecurityScanner;
});

it('menerima gambar raster yang bersih', function () {
    $result = $this->scanner->scan(bale_scan_jpeg(), 'photo.jpg');

    expect($result->isClean())->toBeTrue()
        ->and($result->allowed)->toBeTrue()
        ->and($result->detectedType())->toBe('image/jpeg')
        ->and($result->meta['width'] ?? null)->toBe(40);
});

it('mendeteksi dan memblokir kode PHP yang disisipkan pada gambar', function () {
    $payload = bale_scan_jpeg().'<?php system($_GET["c"]); ?>';
    $result = $this->scanner->scan($payload, 'photo.jpg');

    expect($result->allowed)->toBeFalse()
        ->and($result->severity)->toBe(MediaScanResult::SEVERITY_HIGH)
        ->and($result->hasCode('php_open_tag'))->toBeTrue()
        ->and($result->hasCode('fn_system'))->toBeTrue();
});

it('mendeteksi tag script dan markup yang disisipkan', function () {
    $result = $this->scanner->scan(bale_scan_jpeg().'<script>alert(1)</script>', 'photo.jpg');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('script_tag'))->toBeTrue();
});

it('memblokir eksekutabel yang menyamar sebagai media', function () {
    $result = $this->scanner->scan("MZ\x90\x00".str_repeat("\x00", 200).'<?php', 'photo.jpg');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('executable_content'))->toBeTrue();
});

it('memblokir ekstensi file yang berbahaya', function () {
    $result = $this->scanner->scan(bale_scan_jpeg(), 'shell.php');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('dangerous_extension'))->toBeTrue();
});

it('mengenali polyglot dengan payload besar yang ditambahkan', function () {
    $result = $this->scanner->scan(bale_scan_png().str_repeat('A', 2048), 'photo.png');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('trailing_data'))->toBeTrue();
});

it('mendeteksi link tersembunyi namun tetap mengizinkannya (medium)', function () {
    $result = $this->scanner->scan(bale_scan_jpeg().'http://spam.example/track?id=1', 'photo.jpg');

    expect($result->hasCode('hidden_link'))->toBeTrue()
        ->and($result->allowed)->toBeTrue()
        ->and($result->severity)->toBe(MediaScanResult::SEVERITY_MEDIUM);
});

it('menerima video mp4 yang bersih', function () {
    $result = $this->scanner->scan(bale_scan_mp4(), 'clip.mp4', 'video');

    expect($result->allowed)->toBeTrue()
        ->and($result->isClean())->toBeTrue()
        ->and($result->detectedType())->toBe('video/mp4');
});

it('memblokir binari video yang tidak dikenali', function () {
    $result = $this->scanner->scan(str_repeat("\x00", 64), 'clip.mp4', 'video');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('unrecognized_video'))->toBeTrue();
});

it('memblokir video yang membawa kode PHP', function () {
    $result = $this->scanner->scan(bale_scan_mp4().'<?php echo 1;', 'clip.mp4', 'video');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('php_open_tag'))->toBeTrue();
});

it('memblokir file kosong', function () {
    $result = $this->scanner->scan('', 'empty.jpg');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('empty_file'))->toBeTrue();
});

it('menentukan ekstensi video yang aman dari isi file, bukan dari URL', function () {
    expect($this->scanner->safeVideoExtension(bale_scan_mp4()))->toBe('mp4')
        ->and($this->scanner->safeVideoExtension("\x1A\x45\xDF\xA3".str_repeat("\x00", 16)))->toBe('webm')
        ->and($this->scanner->safeVideoExtension("\x00\x00\x00\x18ftypqt  \x00\x00\x00\x00qt  ", 'mov'))->toBe('mov')
        ->and($this->scanner->safeVideoExtension('not-a-video'))->toBeNull();
});

it('menolak gambar berbahaya dan mengarantinanya dari service migrasi', function () {
    $dir = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'bale-quarantine-'.Str::uuid();

    $service = new class extends MediaMigrationService
    {
        public string $payload = '';

        protected function fetchBinary(string $url): ?string
        {
            return $this->payload;
        }
    };

    $service->payload = bale_scan_jpeg().'<?php system("id"); ?>';
    $service->setQuarantineDir($dir);

    try {
        $url = $service->storeImageFromUrl('https://legacy.example/wp-content/uploads/evil.jpg', 'dindik');

        expect($url)->toBeNull()
            ->and($service->lastScan()?->isBlocked())->toBeTrue()
            ->and($service->lastScan()?->hasCode('php_open_tag'))->toBeTrue();

        $files = glob($dir.DIRECTORY_SEPARATOR.'*') ?: [];
        expect($files)->not->toBeEmpty();
    } finally {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
});

it('menolak video dengan ekstensi berbahaya pada service migrasi', function () {
    $service = new class extends MediaMigrationService
    {
        public string $payload = '';

        protected function fetchBinary(string $url): ?string
        {
            return $this->payload;
        }
    };

    $service->payload = bale_scan_mp4();

    $url = $service->storeVideoFromUrl('https://legacy.example/uploads/clip.php', 'dindik');

    expect($url)->toBeNull()
        ->and($service->lastScan()?->hasCode('dangerous_extension'))->toBeTrue();
});

it('mengabaikan tag <?= yang hanya kebetulan muncul di data terkompresi', function () {
    // Di dalam data gambar terkompresi, tiga byte `<?=` muncul secara acak
    // kira-kira sekali per megabyte. Data di sekitarnya bukan teks, jadi ini
    // bukan payload dan gambar harus tetap diizinkan.
    $nonTextual = str_repeat("\x80\x90\xA0\xB0", 60);
    $payload = substr(bale_scan_jpeg(), 0, 128).$nonTextual.'<?='.$nonTextual;

    $result = $this->scanner->scan($payload, 'photo.jpg');

    expect($result->hasCode('php_echo_tag'))->toBeFalse()
        ->and($result->allowed)->toBeTrue();
});

it('tetap mendeteksi tag <?= yang berada di area teks', function () {
    $payload = bale_scan_jpeg().str_repeat(' ', 64).'<?= system("id"); ?>';
    $result = $this->scanner->scan($payload, 'photo.jpg');

    expect($result->allowed)->toBeFalse()
        ->and($result->hasCode('php_echo_tag'))->toBeTrue();
});

it('melaporkan daftar kode temuan', function () {
    $result = $this->scanner->scan(bale_scan_jpeg().'<?php system("id"); ?>', 'photo.jpg');

    expect($result->codes())->toContain('php_open_tag')->toContain('fn_system');
});

it('berhenti pada kandidat pertama yang diblokir, tidak jatuh ke varian berikutnya', function () {
    // Sebuah varian WordPress (`-1024x683.jpg`) membuat dua kandidat: file asli
    // dan varian hasil resize. Bila file asli diblokir, blok media harus gagal —
    // bukan diam-diam menyimpan varian resize-nya.
    $dir = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'bale-quarantine-'.Str::uuid();

    $service = new class extends MediaMigrationService
    {
        /** @var array<string, string> */
        public array $payloads = [];

        public int $fetches = 0;

        protected function fetchBinary(string $url): ?string
        {
            $this->fetches++;

            return $this->payloads[$url] ?? null;
        }
    };

    $full = 'https://legacy.example/wp-content/uploads/evil.jpg';
    $variant = 'https://legacy.example/wp-content/uploads/evil-1024x683.jpg';

    $service->payloads = [
        $full => bale_scan_jpeg().'<?php system("id"); ?>',
        $variant => bale_scan_jpeg(),
    ];
    $service->setQuarantineDir($dir);

    try {
        $url = $service->storeImageFromUrl($variant, 'dindik');

        expect($url)->toBeNull()
            ->and($service->fetches)->toBe(1)
            ->and($service->lastScan()?->hasCode('php_open_tag'))->toBeTrue();

        expect(glob($dir.DIRECTORY_SEPARATOR.'*') ?: [])->not->toBeEmpty();
    } finally {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
});
