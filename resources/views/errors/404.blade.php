@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Halaman Menghilang"
        description="Halaman yang Anda cari tidak dapat ditemukan. Mungkin sudah dipindah, dihapus, atau URL yang Anda masukkan salah."
        shimmerClass="shimmer-text-blue">
        <x-slot name="illustration">
            <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="g404a" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#60a5fa" />
                        <stop offset="100%" stop-color="#3b82f6" />
                    </linearGradient>
                    <linearGradient id="g404b" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#818cf8" />
                        <stop offset="100%" stop-color="#6366f1" />
                    </linearGradient>
                    <filter id="glow404">
                        <feGaussianBlur stdDeviation="4" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <circle cx="90" cy="90" r="48" fill="url(#g404a)" opacity="0.9" filter="url(#glow404)" />
                <circle cx="90" cy="90" r="38" fill="rgba(15,12,41,0.7)" />
                <text x="90" y="103" text-anchor="middle" font-size="42" font-weight="900" fill="rgba(255,255,255,0.85)"
                    font-family="Nunito, Arial, sans-serif">?</text>
                <ellipse cx="90" cy="90" rx="70" ry="18" fill="none" stroke="url(#g404b)" stroke-width="4" opacity="0.5" />
                <circle cx="90" cy="72" r="5" fill="#c7d2fe" opacity="0.9" filter="url(#glow404)">
                    <animateTransform attributeName="transform" type="rotate" from="0 90 90" to="360 90 90" dur="5s"
                        repeatCount="indefinite" />
                </circle>
            </svg>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-blue-500/20 text-blue-300 border border-blue-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                404 — Halaman Tidak Ditemukan
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-blue-500/7 !border-blue-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-blue-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Periksa kembali URL yang Anda ketikkan, atau gunakan tombol di bawah untuk kembali ke halaman utama.
            </p>
        </x-slot>

        <x-slot name="actions">
            @php $dashboardUrl = app('router')->has('dashboard') ? route('dashboard') : '/'; @endphp
            <a href="{{ $dashboardUrl }}" wire:navigate
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white font-semibold text-sm shadow-xl transition-all duration-300 hover:shadow-purple-500/30 hover:scale-[1.02] active:scale-[0.98]"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Dashboard
            </a>
            <a href="javascript:history.back()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white/80 font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </x-slot>
    </x-core::error-card>

    <style>
        .shimmer-text-blue {
            background: linear-gradient(90deg, #60a5fa, #818cf8, #60a5fa);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }
    </style>
@endsection