@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Sesi Anda Habis"
        description="Sesi halaman Anda telah kedaluwarsa karena token CSRF tidak valid atau sudah habis masa berlakunya. Muat ulang halaman dan coba lagi."
        shimmerClass="shimmer-text-orange">
        <x-slot name="illustration">
            <svg width="160" height="160" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="g419a" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#fb923c" />
                        <stop offset="100%" stop-color="#ea580c" />
                    </linearGradient>
                    <linearGradient id="g419b" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#a78bfa" />
                        <stop offset="100%" stop-color="#60a5fa" />
                    </linearGradient>
                    <filter id="glow419">
                        <feGaussianBlur stdDeviation="4" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <path d="M50 25 L110 25 L80 80 L110 135 L50 135 L80 80 Z" fill="url(#g419a)" opacity="0.85"
                    filter="url(#glow419)" />
                <path d="M55 30 L105 30 L80 78 L55 30 Z" fill="rgba(255,255,255,0.15)" />
                <path d="M80 82 L105 130 L55 130 Z" fill="rgba(255,255,255,0.08)" />
                <circle cx="128" cy="36" r="22" fill="url(#g419b)" opacity="0.9" filter="url(#glow419)" />
                <circle cx="128" cy="36" r="17" fill="rgba(15,12,41,0.7)" />
                <circle cx="128" cy="36" r="2" fill="white" />
                <line x1="128" y1="36" x2="128" y2="22" stroke="white" stroke-width="2.5" stroke-linecap="round" />
                <line x1="128" y1="36" x2="137" y2="41" stroke="white" stroke-width="2" stroke-linecap="round"
                    opacity="0.8" />
            </svg>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-orange-500/20 text-orange-300 border border-orange-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-400 animate-pulse"></span>
                419 — Sesi Berakhir
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-orange-500/7 !border-orange-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-orange-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Token keamanan halaman sudah tidak berlaku. Ini terjadi setelah meninggalkan halaman terlalu lama. Silakan
                kembali dan ulangi tindakan Anda.
            </p>
        </x-slot>

        <x-slot name="actions">
            <button onclick="window.location.reload()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white font-semibold text-sm shadow-xl transition-all duration-300 hover:shadow-orange-500/30 hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
                style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Muat Ulang Halaman
            </button>
            @php $dashboardUrl = app('router')->has('dashboard') ? route('dashboard') : '/'; @endphp
            <a href="{{ $dashboardUrl }}" wire:navigate
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white/80 font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Dashboard
            </a>
        </x-slot>
    </x-core::error-card>

    <style>
        .shimmer-text-orange {
            background: linear-gradient(90deg, #fb923c, #f97316, #fb923c);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }
    </style>
@endsection