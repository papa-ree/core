<?php

use Illuminate\Support\Facades\Blade;

/**
 * Test untuk komponen `x-core::checklist`.
 *
 * Komponen ini dipakai bersama oleh beberapa package, jadi test-nya milik
 * `bale/core` — bukan oleh package yang kebetulan memakainya lebih dulu.
 */
describe('x-core::checklist', function () {
    it('merender judul, subjudul, dan bar progres', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => [
                'title' => 'Judul Uji',
                'subtitle' => 'Subjudul uji.',
                'sections' => [
                    ['title' => 'Bagian', 'items' => [
                        ['label' => 'Item A', 'status' => 'done'],
                    ]],
                ],
            ],
        ]);

        expect($html)->toContain('Judul Uji')
            ->toContain('Subjudul uji.')
            ->toContain('role="progressbar"')
            ->toContain('aria-valuenow="100"');
    });

    it('menghitung persentase hanya dari item selesai', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => [
                ['title' => 'Bagian', 'items' => [
                    ['label' => 'A', 'status' => 'done'],
                    ['label' => 'B', 'status' => 'done'],
                    ['label' => 'C', 'status' => 'open'],
                    ['label' => 'D', 'status' => 'blocked'],
                ]],
            ]],
        ]);

        expect($html)->toContain('aria-valuenow="50"')
            ->toContain('2 dari 4 selesai');
    });

    it('memakai label status yang benar untuk tiap status', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => [
                ['title' => 'Bagian', 'items' => [
                    ['label' => 'A', 'status' => 'done'],
                    ['label' => 'B', 'status' => 'open'],
                    ['label' => 'C', 'status' => 'blocked'],
                ]],
            ]],
        ]);

        expect($html)->toContain('Selesai')
            ->toContain('Direncanakan')
            ->toContain('Terhalang');
    });

    it('TIDAK Diam-diam menganggap status tak dikenal sebagai selesai', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => [
                ['title' => 'Bagian', 'items' => [
                    ['label' => 'A', 'status' => 'selesai-typo'],
                ]],
            ]],
        ]);

        // Persentase harus 0 karena tidak ada yang berstatus `done`, dan nilai
        // mentahnya harus terlihat supaya salah ketik ketahuan saat reviewing.
        expect($html)->toContain('aria-valuenow="0"')
            ->toContain('selesai-typo')
            ->toContain('0 dari 1 selesai');
    });

    it('tidak error saat sections kosong', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => []],
        ]);

        expect($html)->toContain('aria-valuenow="0"')
            ->toContain('0 dari 0 selesai');
    });

    it('melewatkan bagian tanpa item', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => [
                ['title' => 'Kosong', 'items' => []],
                ['title' => 'Berisi', 'items' => [['label' => 'X', 'status' => 'done']]],
            ]],
        ]);

        // Judul bagian kosong tidak boleh muncul, dan bagian berisi tetap.
        expect($html)->not->toContain('Kosong')
            ->toContain('Berisi');
    });

    it('merender note dan meta kalau ada', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => [
                ['title' => 'Bagian', 'items' => [
                    [
                        'label' => 'Item',
                        'status' => 'done',
                        'meta' => '§1',
                        'note' => 'Catatan tambahan.',
                    ],
                ]],
            ]],
        ]);

        expect($html)->toContain('§1')
            ->toContain('Catatan tambahan.');
    });

    it('merender tanggal pembaruan kalau ada', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => [
                'title' => 'Judul',
                'updated_at' => '2026-10-01',
                'sections' => [
                    ['title' => 'Bagian', 'items' => [['label' => 'A', 'status' => 'done']]],
                ],
            ],
        ]);

        expect($html)->toContain('2026-10-01');
    });

    it('tidak merender tanggal pembaruan kalau tidak diisi', function () {
        $html = Blade::render('<x-core::checklist :data="$data" />', [
            'data' => ['sections' => [
                ['title' => 'Bagian', 'items' => [['label' => 'A', 'status' => 'done']]],
            ]],
        ]);

        expect($html)->not->toContain('Terakhir diperbarui');
    });

    /*
     * Regresi: judul pernah tampil dua kali (h1 dari halaman + h2 dari
     * komponen) karena kedua pihak merender `title` sendiri. Sekarang halaman
     * pemanggil hanya menyisipkan komponen, dan komponen memegang seluruh
     * kepala — termasuk `updated_at`.
     */
    it('halaman pemanggil tidak perlu merender judul sendiri', function () {
        $wara = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'wara';
        $srikandi = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'srikandi';

        $views = [
            $wara.DIRECTORY_SEPARATOR.'resources/views/livewire/pages/landlord/status/index.blade.php',
            $srikandi.DIRECTORY_SEPARATOR.'resources/views/livewire/pages/landlord/status/index.blade.php',
        ];

        foreach ($views as $view) {
            $content = (string) file_get_contents($view);

            // Tidak boleh ada heading manual: `<h1`, atau teks terjemahan
            // "Terakhir diperbarui" yang berarti halaman merender sendiri.
            expect($content)->not->toContain('<h1')
                ->and($content)->not->toContain('Terakhir diperbarui')
                ->and($content)->toContain('x-core::checklist');
        }
    });
});
