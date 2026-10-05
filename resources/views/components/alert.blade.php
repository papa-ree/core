{{--
    Banner peringatan / informasi.

    🔴 Kenapa komponen ini di `bale/core`: dipakai di `bale/wara` (tiga banner
    di `device-header.blade.php`) DAN di `bale/srikandi` (banner error client,
    banner "kredensial diganti tapi belum login", alert naskah basi). Kalau
    masing-masing package menulis sendiri, ada lima banner yang warnanya
    berbeda satu piksel dan tidak ada yang salah sampai keduanya dibandingkan.

    Bentuknya sengaja mengikuti `checklist`: judulnya `<h4>`, isinya slot
    default, dan ada slot `actions` terpisah supaya tombol tidak ikut tercampur
    ke teks peringatan.

    ⚠️ Slot `actions` harus diikuti spasi/newline sebelum isi slot default:

        <x-core::alert title="Sesi habis">
            <x-slot:actions>
                <x-core::button>Perpanjang</x-core::button>
            </x-slot:actions>

            Sesi terakhir melewati batas.
        </x-core::alert>

    Tanpa spasi itu `</x-slot:actions>` menempel ke teks berikutnya dan
    `@endslot` tidak dikompilasi. Detail alasannya ada di blok `@props` bawah.

    @param string      $variant  info|warning|danger
    @param string|null $title    judul banner
    @param string|null $icon     slot ikon opsional
--}}
@props([
    'variant' => 'info',
    'title' => null,
    'icon' => null,
    /*
     * 🔴 `actions` SENGAJA TIDAK ada di @props -- dia named slot, bukan atribut.
     * Kalau ditulis di sini, Blade memakainya sebagai prop dan membuang slot-nya,
     * jadi div tombol selalu kosong.
     *
     * ⚠️ PEMAKAI: HARUS ada spasi atau newline setelah `</x-slot:actions>`:
     *
     *   BENAR:  </x-slot:actions>\n  Isi
     *   SALAH:  </x-slot:actions>Isi
     *
     * Di kasus salah, regex Blade `\B@(\w+)` menelan `@endslot` + `Isi` jadi satu
     * token `@endslotIsi`, jadi tidak dikompilasi dan bocor mentah ke badan banner.
     * Gejalanya bukan error -- banner terlihat benar, tombolnya hilang, dan tidak
     * ada yang memberitahu kenapa.
     */
])

@php
    /*
     * 🔴 `warning` dan `danger` sengaja memakai warna yang sama (amber) karena
     * keduanya berarti "perhatian", bukan "berhenti". Yang membedakan hanya
     * ikon. Kalau nanti ada yang butuh merah untuk "kritis", tambahkan ke peta
     * ini -- jangan menulis kelas sendiri di view package.
     */
    $palettes = [
        'info' => [
            'wrap' => 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50',
            'icon' => 'text-gray-500 dark:text-gray-400',
            'title' => 'text-gray-900 dark:text-gray-200',
            'body' => 'text-gray-700 dark:text-gray-300',
            'note' => 'text-gray-600 dark:text-gray-400',
            'defaultIcon' => 'info',
        ],
        'warning' => [
            'wrap' => 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'title' => 'text-amber-900 dark:text-amber-200',
            'body' => 'text-amber-800 dark:text-amber-300',
            'note' => 'text-amber-700 dark:text-amber-400',
            'defaultIcon' => 'alert-triangle',
        ],
        'danger' => [
            'wrap' => 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20',
            'icon' => 'text-red-600 dark:text-red-400',
            'title' => 'text-red-900 dark:text-red-200',
            'body' => 'text-red-800 dark:text-red-300',
            'note' => 'text-red-700 dark:text-red-400',
            'defaultIcon' => 'alert-triangle',
        ],
    ];

    /*
     * 🔴 Variant tidak dikenal jatuh ke `info`, bukan `warning`.
     *
     * Alasannya kebalikan dari `badge`: di banner, default yang terlalu serius
     * membuat orang mengabaikan semua peringatan (alarm fatigue), sementara
     * default yang terlalu ringan hanya menurunkan seriousness satu tampilan.
     * Yang tidak boleh terjadi diam-diam: `danger` salah ketik jadi `info` dan
     * adminnya lalu mengira itu informasi biasa.
     */
    $style = $palettes[$variant] ?? $palettes['info'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border p-4 ' . $style['wrap']]) }}>
    <div class="flex items-start gap-3">
        <span class="shrink-0 mt-0.5 {{ $style['icon'] }}">
            @if (filled($icon))
                {{ $icon }}
            @else
                {{-- Ikon default pakai komponen yang sama seperti halaman lain, supaya tidak ada satu banner dengan bentuk ikon berbeda. --}}
                @if ($style['defaultIcon'] === 'alert-triangle')
                    <x-lucide-alert-triangle class="size-5" />
                @else
                    <x-lucide-info class="size-5" />
                @endif
            @endif
        </span>

        <div class="min-w-0 grow">
            @if (filled($title))
                <h4 class="text-sm font-semibold {{ $style['title'] }}">
                    {{ $title }}
                </h4>
            @endif

            {{-- @class, bukan ternary di dalam string, supaya tidak ada spasi ganda
                 saat tidak ada judul (`class="  text-sm ..."`). --}}
            <div @class(['text-sm ' . $style['body'], 'mt-2 space-y-1' => filled($title)])>
                {{ $slot }}
            </div>

            @isset($actions)
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs {{ $style['note'] }}">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</div>