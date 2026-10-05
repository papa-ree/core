<?php

use Illuminate\Support\Facades\Blade;

/**
 * Test untuk komponen `x-core::badge` dan `x-core::alert`.
 *
 * Kedua komponen ini lahir dari kebutuhan bersama: `bale/wara` dan
 * `bale/srikandi` akan memakai warna badge dan bentuk banner yang sama. Kalau
 * masing-masing package menulis kelasnya sendiri, hasilnya badge yang warnanya
 * berbeda satu piksel dan tidak ada yang salah sampai keduanya dibandingkan.
 *
 * Karena itu test-nya milik `bale/core` -- sama seperti `ChecklistComponentTest`.
 *
 * 🔴 Test yang paling penting di file ini bukan "warna benar", tapi dua kasus
 * fallback. Warna salah ketik (`amberm`) dan data yang nilainya tidak dikenal
 * (`status_berkas = proses`) itu dua masalah berbeda yang kebetulan sama-sama
 * "warna tidak ditemukan di peta". Kalau keduanya jatuh ke perilaku yang sama,
 * salah ketik akan terlihat berhasil dan tidak akan pernah ditemukan.
 */
describe('x-core::badge', function () {
    it('merender label dengan warna yang diminta', function () {
        $html = Blade::render('<x-core::badge color="amber" label="Sesi terakhir" />');

        expect($html)
            ->toContain('Sesi terakhir')
            ->toContain('bg-amber-50')
            ->toContain('text-amber-700')
            ->toContain('dark:bg-amber-900/20');
    });

    it('memakai warna gray sebagai netral untuk data yang tidak dikenal', function () {
        // Ini kasus yang akan sering muncul: `status_berkas` bentuknya
        // tidak dijamin. Nilai yang tidak dikenal harus TETAP tampil,
        // bukan hilang.
        $html = Blade::render('<x-core::badge color="gray" label="proses" />');

        expect($html)->toContain('proses')->toContain('bg-gray-50');
    });

    it('TIDAK diam-diam memakai warna netral saat nama warna salah ketik', function () {
        // Kalau ini jatuh ke gray, salah ketik `amberm` akan terlihat
        // "berhasil" dengan badge abu-abu dan tidak ada yang tahu kenapa
        // badge itu tidak kuning.
        $html = Blade::render('<x-core::badge color="amberm" label="Uji" />');

        expect($html)
            ->toContain('Uji')
            ->toContain('bg-gray-50')
            ->not->toContain('bg-amber-50');
    });

    it('merender slot sebagai pengganti label', function () {
        $html = Blade::render('<x-core::badge color="red">Kustom</x-core::badge>');

        expect($html)->toContain('Kustom');
    });

    it('merender konten slot apa adanya, termasuk HTML ikon', function () {
        // Slot, bukan prop `$icon`: sebagai prop, Blade meng-escape isinya
        // dan SVG jadi teks yang terlihat di layar.
        $html = Blade::render('<x-core::badge color="blue"><x-lucide-check class="size-3" />Uji</x-core::badge>');

        expect($html)
            ->toContain('<svg')
            ->toContain('Uji')
            ->not->toContain('&lt;svg');
    });

    it('meneruskan class tambahan dari pemanggil', function () {
        $html = Blade::render('<x-core::badge color="blue" label="Uji" class="mr-2 uppercase" />');

        expect($html)->toContain('mr-2 uppercase');
    });

    it('tidak error saat tanpa label dan tanpa slot', function () {
        $html = Blade::render('<x-core::badge color="gray" />');

        expect($html)->toContain('inline-flex');
    });
});

describe('x-core::alert', function () {
    it('merender judul dan isi', function () {
        $html = Blade::render('<x-core::alert title="Sesi habis">Perpanjang sesi.</x-core::alert>');

        expect($html)
            ->toContain('Sesi habis')
            ->toContain('Perpanjang sesi.')
            ->toContain('<h4');
    });

    it('memakai ikon berbeda untuk info dan warning', function () {
        $info = Blade::render('<x-core::alert variant="info">Isi</x-core::alert>');
        $warning = Blade::render('<x-core::alert variant="warning">Isi</x-core::alert>');

        // `info` = lingkaran + tanda seru. `warning` = segitiga.
        // Bedanya harus terlihat tanpa membaca teks.
        expect($info)->toContain('<circle cx="12" cy="12" r="10"/>')
            ->and($warning)->toContain('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>');
    });

    it('memakai ikon warning yang sama untuk danger, dibedakan dari warna teks saja', function () {
        $danger = Blade::render('<x-core::alert variant="danger">Isi</x-core::alert>');
        $warning = Blade::render('<x-core::alert variant="warning">Isi</x-core::alert>');

        // Keduanya "perhatian", bukan "berhenti". Kalau nanti ada yang butuh
        // merah khusus untuk "kritis", itu penambahan baru ke peta -- bukan
        // alasan untuk menulis kelas sendiri di view package.
        expect($danger)->toContain('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>')
            ->and($danger)->toContain('bg-red-50')
            ->and($danger)->not->toContain('bg-amber-50')
            ->and($warning)->toContain('bg-amber-50');
    });

    it('menjelaskan kenapa slot actions butuh whitespace setelah tag penutup', function () {
        // Characterisation test, bukan regression test.
        //
        // Dua caller di bawah bedanya cuma satu spasi, dan hasil kompilasinya
        // sama sekali berbeda:
        //
        //   BENAR (ada spasi): Bladeijen `@endslot` jadi `$__env->endSlot()`,
        //                       slot actions terisi, tombol muncul.
        //   SALAH (tanpa spasi): regex Blade `\B@(\w+)` menelan `@endslot` +
        //                       `Isi` jadi satu token `@endslotIsi`, jadi
        //                       TIDAK dikompilasi. akibatnya bocor mentah ke
        //                       badan banner, slot actions kosong, dan
        //                       output buffer menggantung.
        //
        // 🔴 Kasus yang SALAH sengaja diperiksa di level kompilator, bukan
        // dirender. Merendernya menghasilkan "Test code did not close its own
        // output buffers" -- karena `@endslot` yang tak terkompilasi membuat
        // `startComponent()` tidak pernah dipasangkan `endComponent()`.
        // Itu justru bukti kedua dari bug yang sama, tapi membuat test suite
        // menandai dirinya "risky".
        //
        // Test ini mengunci behaviour Framework supaya keputusan "wajib spasi"
        // di docblock punya bukti, bukan justaPc belief. Kalau Laravel versi
        // berikutnya memperbaiki ini, test ini yang gagal dan docblock bisa
        // dilonggarkan.
        $compiler = app('blade.compiler');

        $benar = $compiler->compileString(
            '<x-core::alert><x-slot:actions><button>P</button></x-slot:actions> Isi</x-core::alert>'
        );
        $salah = $compiler->compileString(
            '<x-core::alert><x-slot:actions><button>P</button></x-slot:actions>Isi</x-core::alert>'
        );

        // Bentuk benar: `@endslot` jadi panggilan endSlot().
        expect($benar)
            ->toContain('$__env->slot(')
            ->toContain('$__env->endSlot()')
            ->not->toContain('@endslotIsi');

        // Bentuk salah: token `@endslotIsi` utuh, tidak dikompilasi sama sekali.
        expect($salah)
            ->toContain('@endslotIsi')
            ->not->toContain('$__env->endSlot()');
    });

    it('menaruh isi slot default terpisah dari slot actions', function () {
        // Pemeriksaan sisi render untuk bentuk yang BENAR saja, supaya kita
        // tahu tombol benar-benar mendarat di div actions dan bukan di badan.
        $html = Blade::render(
            "<x-core::alert title=\"Judul\"><x-slot:actions><button>Perpanjang</button></x-slot:actions>\nIsi</x-core::alert>"
        );

        expect($html)
            ->toContain('Judul')
            ->toContain('Perpanjang')
            ->toContain('Isi')
            ->not->toContain('@endslot')
            ->not->toContain('<x-slot:actions>');

        // Slot actions dirender SETELAH badan teks, bukan di dalamnya.
        $bodyPos = strpos($html, 'min-w-0 grow');
        $actionsPos = strpos($html, 'flex-wrap');

        expect($bodyPos)->not->toBeFalse()
            ->and($actionsPos)->not->toBeFalse()
            ->and($actionsPos)->toBeGreaterThan($bodyPos);
    });

    it('TIDAK menaikkan severity saat nama variant salah ketik', function () {
        // Default yang terlalu serius membuat orang mengabaikan semua
        // peringatan, jadi variant tak dikenal jatuh ke `info`.
        $html = Blade::render('<x-core::alert variant="dangerus">Isi</x-core::alert>');

        expect($html)
            ->toContain('Isi')
            ->toContain('bg-gray-50')
            ->not->toContain('bg-red-50');
    });

    it('merender slot actions terpisah dari teks peringatan', function () {
        $html = Blade::render(
            "<x-core::alert title=\"Judul\"><x-slot:actions><button>Perpanjang</button></x-slot:actions>\nIsi</x-core::alert>"
        );

        expect($html)
            ->toContain('Judul')
            ->toContain('Perpanjang')
            ->toContain('Isi');
    });

    it('lewatkan slot actions kalau tidak diberikan', function () {
        $html = Blade::render('<x-core::alert title="Judul">Isi</x-core::alert>');

        expect($html)->toContain('Judul')->not->toContain('<button');
    });

    it('lewatkan margin atas pada isi kalau tidak ada judul', function () {
        $withTitle = Blade::render('<x-core::alert title="Judul">Isi</x-core::alert>');
        $withoutTitle = Blade::render('<x-core::alert>Isi</x-core::alert>');

        expect($withTitle)->toContain('mt-2')
            ->and($withoutTitle)->not->toContain('mt-2 space-y-1');
    });

    it('meneruskan class tambahan dari pemanggil', function () {
        $html = Blade::render('<x-core::alert class="mb-4">Isi</x-core::alert>');

        expect($html)->toContain('mb-4');
    });
});
