@props([
    'label' => '',
    'value' => null,
    'icon' => 'activity',
    'tone' => 'teal',
    'badge' => null,
    'hint' => null,
])

@php
    // Varian gradient dari `x-core::stat-card`, mengikuti kartu KPI
    // `bale/srikandi/overview-metrics`: ikon gradient, badge sewarna, dan
    // lingkaran dekoratif dengan opasitas rendah.
    $tones = [
        'teal' => [
            'icon' => 'from-teal-500 to-teal-600 shadow-teal-500/30 dark:shadow-teal-500/20',
            'badge' => 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
            'glow' => 'bg-teal-500/5',
        ],
        'emerald' => [
            'icon' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/30 dark:shadow-emerald-500/20',
            'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            'glow' => 'bg-emerald-500/5',
        ],
        'sky' => [
            'icon' => 'from-sky-500 to-sky-600 shadow-sky-500/30 dark:shadow-sky-500/20',
            'badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
            'glow' => 'bg-sky-500/5',
        ],
        'amber' => [
            'icon' => 'from-amber-500 to-amber-600 shadow-amber-500/30 dark:shadow-amber-500/20',
            'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
            'glow' => 'bg-amber-500/5',
        ],
        'rose' => [
            'icon' => 'from-rose-500 to-rose-600 shadow-rose-500/30 dark:shadow-rose-500/20',
            'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
            'glow' => 'bg-rose-500/5',
        ],
        'indigo' => [
            'icon' => 'from-indigo-500 to-indigo-600 shadow-indigo-500/30 dark:shadow-indigo-500/20',
            'badge' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
            'glow' => 'bg-indigo-500/5',
        ],
    ];

    $palette = $tones[$tone] ?? $tones['teal'];
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-white/[0.06] dark:bg-gray-900']) }}>
    <div class="flex items-start justify-between">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br shadow-md {{ $palette['icon'] }}">
            <x-dynamic-component :component="'lucide-'.$icon" class="size-5 text-white" />
        </div>

        @if($badge)
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $palette['badge'] }}">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="mt-4">
        <div class="text-3xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $value }}</div>
        <div class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
        @if($hint)
            <div class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">{{ $hint }}</div>
        @endif
    </div>

    <div class="pointer-events-none absolute -bottom-4 -right-4 h-20 w-20 rounded-full transition-all duration-300 group-hover:scale-150 {{ $palette['glow'] }}"></div>
</div>
