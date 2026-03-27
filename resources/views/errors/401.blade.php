@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Identitas Tidak Dikenali"
        description="Anda belum masuk atau sesi login Anda telah berakhir. Silakan login terlebih dahulu untuk mengakses halaman ini."
        shimmerClass="shimmer-text-yellow">
        <x-slot name="illustration">
            <svg width="160" height="160" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="g401a" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#fbbf24" />
                        <stop offset="100%" stop-color="#f59e0b" />
                    </linearGradient>
                    <linearGradient id="g401b" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#667eea" />
                        <stop offset="100%" stop-color="#764ba2" />
                    </linearGradient>
                    <filter id="glow401">
                        <feGaussianBlur stdDeviation="4" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <circle cx="80" cy="80" r="60" fill="url(#g401b)" opacity="0.25" />
                <circle cx="80" cy="80" r="48" fill="rgba(255,255,255,0.06)" />
                <circle cx="66" cy="72" r="20" fill="url(#g401a)" opacity="0.9" filter="url(#glow401)" />
                <circle cx="66" cy="72" r="13" fill="rgba(15,12,41,0.75)" />
                <rect x="82" y="69" width="32" height="8" rx="4" fill="url(#g401a)" opacity="0.9" />
                <rect x="104" y="77" width="8" height="10" rx="3" fill="url(#g401a)" opacity="0.75" />
                <rect x="94" y="77" width="8" height="14" rx="3" fill="url(#g401a)" opacity="0.75" />
            </svg>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-yellow-500/20 text-yellow-300 border border-yellow-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                401 — Autentikasi Diperlukan
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-yellow-500/7 !border-yellow-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-yellow-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Jika Anda merasa sudah login, coba muat ulang halaman atau hubungi administrator sistem untuk mendapatkan
                akses.
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
            @php $loginUrl = app('router')->has('login') ? route('login') : '/login'; @endphp
            <a href="{{ $loginUrl }}"
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white/80 font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Masuk / Login
            </a>
        </x-slot>
    </x-core::error-card>

    <style>
        .shimmer-text-yellow {
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #fbbf24);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }
    </style>
@endsection