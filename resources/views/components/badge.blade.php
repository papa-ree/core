{{--
    Badge / pill status.

    🔴 Kenapa komponen ini ada di `bale/core` dan bukan ditulis terpisah di
    setiap package: dipakai minimal di empat tempat yang sudah diketahui, dan
    setiap tempat itu akan menulis kelas Tailwind sendiri. Hasilnya empat pill
    yang warnanya berbeda satu piksel — dan tidak ada yang salah sampai ada yang
    membandingkan dua halaman bersebelahan.

    Pemakaian saat ini (4 Okt 2026):
      - `bale/wara`  `log-row.blade.php`      badge `SESSION_LAST` (amber)
      - `bale/wara`  `client-row.blade.php`   badge readiness
      - `bale/srikandi` daftar client        badge status + badge error
      - `bale/srikandi` daftar naskah       badge `status_berkas`

Warna ditentukan di VIEW, bukan di model. Alasannya: `status_berkas`
    bentuknya tidak dijamin (bisa string, bisa JSON, bisa string kosong), dan
    model tidak boleh memutuskan bahwa nilai tak dikenal berarti "merah" —
    itu keputusan presentasi. Default selalu `gray`, jadi nilai yang tidak
    dikenali tampil apa adanya, bukan dipetakan ke status lain.

    @param string      $color  gray|amber|red|emerald|blue|indigo|sky
    @param string|null $label  teks badge
--}}
@props([
    'color' => 'gray',
    'label' => null,
])

@php
    /*
     * 🔴 Peta warna di bawah adalah satu-satunya definisi warna badge di seluruh
     * sistem. Kalau ada package yang butuh warna lain, tambahkan DI SINI --
     * jangan menulis kelas Tailwind sendiri di view package itu.
     *
     * Warna yang TIDAK dipakai: `yellow`, `green`, `purple`. Tailstone punya
     * padanannya, tapi kelas yang sudah dipakai di repo adalah `amber`,
* `emerald`, `red`. Menambah sinonim di sini hanya menambah satu
     * pemeliharaan: dua nama untuk warna yang sama.
     */
    $palettes = [
        'gray' => 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800/50 dark:text-gray-300 dark:border-gray-700',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
        'red' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
        'blue' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
        'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/20 dark:text-indigo-300 dark:border-indigo-800',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800',
    ];

    /*
     * 🔴 Warna tidak dikenal TIDAK diam-diam jatuh ke `gray`.
     *
     * Kalau iya, salah ketik `amberm` akan terlihat "berhasil" dengan badge
     * abu-abu, dan tidak ada yang tahu kenapa badge itu tidak kuning.
     * Sebaliknya nilai data yang tidak dikenal (`status_berkas` = `proses`)
     * HARUS bisa tampil -- itu kasus yang berbeda, dan ditangani pemanggil
     * dengan memilih warna yang cocok, bukan dengan nama warna yang salah.
     */
    $classes = $palettes[$color] ?? $palettes['gray'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-1 rounded-md border px-2 py-0.5 text-xs font-medium ' . $classes]) }}>
    {{-- Kalau perlu ikon, taruh di slot: `<x-core::badge><x-lucide-x />Label</x-core::badge>`.
         Tidak ada prop `$icon` karena isinya selalu HTML, dan sebagai prop
         Blade akan ikut ter-escape -- jadi pemanggil harus `->toHtml()`
         dulu. Slot tidak punya masalah itu. --}}
    {{ $label ?? $slot }}
</span>