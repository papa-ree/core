@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Batas Akses Tercapai"
        description="Anda telah melakukan terlalu banyak permintaan dalam waktu singkat. Sistem membatasi akses sementara untuk menjaga stabilitas."
        shimmerClass="shimmer-text-purple">
        <x-slot name="illustration">
            <svg width="160" height="160" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="g429a" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#c084fc" />
                        <stop offset="100%" stop-color="#a855f7" />
                    </linearGradient>
                    <linearGradient id="g429b" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f093fb" />
                        <stop offset="100%" stop-color="#667eea" />
                    </linearGradient>
                    <filter id="glow429">
                        <feGaussianBlur stdDeviation="4" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <rect x="22" y="105" width="18" height="30" rx="5" fill="url(#g429a)" opacity="0.5"
                    filter="url(#glow429)" />
                <rect x="48" y="85" width="18" height="50" rx="5" fill="url(#g429a)" opacity="0.7" filter="url(#glow429)" />
                <rect x="74" y="62" width="18" height="73" rx="5" fill="url(#g429a)" opacity="0.9" filter="url(#glow429)" />
                <rect x="100" y="42" width="18" height="93" rx="5" fill="url(#g429b)" opacity="0.9"
                    filter="url(#glow429)" />
                <circle cx="126" cy="42" r="22" fill="url(#g429b)" opacity="0.9" filter="url(#glow429)" />
                <circle cx="126" cy="42" r="17" fill="rgba(15,12,41,0.7)" />
                <rect x="122" y="28" width="8" height="14" rx="4" fill="white" opacity="0.9" />
                <circle cx="126" cy="49" r="4" fill="white" opacity="0.9" />
            </svg>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-purple-500/20 text-purple-300 border border-purple-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                429 — Terlalu Banyak Permintaan
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-purple-500/7 !border-purple-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-purple-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Tunggu beberapa menit sebelum mencoba kembali. Jika masalah berlanjut, hubungi tim bantuan kami untuk
                mendapatkan informasi lebih lanjut.
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
        .shimmer-text-purple {
            background: linear-gradient(90deg, #c084fc, #a855f7, #c084fc);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }
    </style>
@endsection