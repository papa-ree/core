{{--
    Daftar periksa (checklist) status pekerjaan sebuah package.

    Sengaja berada di `bale/core` supaya bisa dipakai package mana pun tanpa
    setiap package menulis ulang markup badge yang sama.

    Bentuk `$data` (lihat `bale/wara/src/status.php` sebagai contoh):

        [
            'title'    => 'Status bale/wara',
            'subtitle' => '...',
            'sections' => [
                [
                    'title' => 'Fase implementasi',
                    'items' => [
                        [
                            'label'   => 'Fase 0 — Rekonsiliasi repo',
                            'status'  => 'done',            // done | open | blocked
                            'note'    => 'opsional',
                            'meta'    => 'opsional',        // teks pendek, mis. "2026-09-29"
                        ],
                    ],
                ],
            ],
        ]

    Status yang tidak dikenal TIDAK diam-diam dianggap selesai — ia jatuh ke
    gaya netral dengan label mentahnya, supaya salah ketik kelihatan saat
    reviewing halaman, bukan hilang saja.
--}}

@php
    $statusStyle = [
        'done' => [
            'chip' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800/40',
            'icon' => 'circle-check',
            'label' => 'Selesai',
        ],
        'open' => [
            'chip' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/40',
            'icon' => 'clock',
            'label' => 'Direncanakan',
        ],
        'blocked' => [
            'chip' => 'bg-red-50 text-red-700 border-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800/40',
            'icon' => 'triangle-alert',
            'label' => 'Terhalang',
        ],
    ];

    $sections = $data['sections'] ?? [];

    $allItems = collect($sections)->flatMap(fn ($section) => $section['items'] ?? []);

    $doneCount = $allItems->where('status', 'done')->count();
    $openCount = $allItems->where('status', 'open')->count();
    $blockedCount = $allItems->where('status', 'blocked')->count();

    $percent = $allItems->isEmpty()
        ? 0
        : (int) round($doneCount / $allItems->count() * 100);
@endphp

<div class="space-y-8">
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    {{ $data['title'] ?? __('Status') }}
                </h2>

                @if (filled($data['subtitle'] ?? null))
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        {{ $data['subtitle'] }}
                    </p>
                @endif

                {{--
                    Tanggal pembaruan sengaja ditampilkan di dalam komponen,
                    bukan di halaman pemanggil.

                    Awalnya `updated_at` dirender oleh Blade halaman DAN
                    `title`/`subtitle` oleh komponen ini — hasilnya judul yang
                    sama tampil dua kali (h1 + h2) di halaman yang sama. Komponen
                    sekarang memegang seluruh kepala checklist supaya halaman
                    pemanggil cukup menyisipkan komponennya saja.
                --}}
                @if (filled($data['updated_at'] ?? null))
                    <p class="mt-1 text-xs text-gray-400">
                        {{ __('Terakhir diperbarui') }} {{ $data['updated_at'] }}
                    </p>
                @endif
            </div>

            <div class="shrink-0 text-right">
                <div class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                    {{ $percent }}<span class="text-base font-normal text-gray-400">%</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $doneCount }} dari {{ $allItems->count() }} selesai
                </div>
            </div>
        </div>

        <div
            class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"
            role="progressbar"
            aria-valuenow="{{ $percent }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="{{ __('Progres') }}">
            <div
                class="h-full rounded-full bg-emerald-500 transition-all duration-500 dark:bg-emerald-400"
                style="width: {{ $percent }}%"></div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-xs">
            @if ($doneCount > 0)
                <span
                    class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 font-medium {{ $statusStyle['done']['chip'] }}">
                    {{ $doneCount }} selesai
                </span>
            @endif

            @if ($openCount > 0)
                <span
                    class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 font-medium {{ $statusStyle['open']['chip'] }}">
                    {{ $openCount }} direncanakan
                </span>
            @endif

            @if ($blockedCount > 0)
                <span
                    class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 font-medium {{ $statusStyle['blocked']['chip'] }}">
                    {{ $blockedCount }} terhalang
                </span>
            @endif
        </div>
    </div>

    @foreach ($sections as $section)
        @php
            $items = $section['items'] ?? [];
        @endphp

        @if ($items === [])
            @continue
        @endif

        <section>
            <h3 class="text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                {{ $section['title'] ?? '' }}
            </h3>

            <ul class="mt-3 space-y-2">
                @foreach ($items as $item)
                    @php
                        $status = $item['status'] ?? 'open';
                        $style = $statusStyle[$status] ?? null;
                    @endphp

                    <li
                        class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                        <x-dynamic-component
                            :component="'lucide-'.($style['icon'] ?? 'circle-dashed')"
                            class="mt-0.5 h-4 w-4 shrink-0 {{ $status === 'done' ? 'text-emerald-500' : ($status === 'blocked' ? 'text-red-500' : 'text-amber-500') }}" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $item['label'] ?? '' }}
                                </span>

                                @if ($style)
                                    <span
                                        class="inline-flex items-center rounded-md border px-1.5 py-0.5 text-[11px] font-medium {{ $style['chip'] }}">
                                        {{ $style['label'] }}
                                    </span>
                                @else
                                    {{-- Status tak dikenal: tampilkan apa adanya, jangan disamarkan. --}}
                                    <span
                                        class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[11px] font-medium text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        {{ $status }}
                                    </span>
                                @endif

                                @if (filled($item['meta'] ?? null))
                                    <span class="font-mono text-[11px] text-gray-400">
                                        {{ $item['meta'] }}
                                    </span>
                                @endif
                            </div>

                            @if (filled($item['note'] ?? null))
                                <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                    {!! $item['note'] !!}
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
