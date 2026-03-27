@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Sedang Dalam Pemeliharaan"
        description="{{ ($exception && $exception->getMessage()) ? $exception->getMessage() : 'Layanan sedang tidak tersedia saat ini karena pemeliharaan terjadwal atau gangguan sementara. Kami akan segera kembali online.' }}"
        shimmerClass="shimmer-text-amber">
        <x-slot name="illustration">
            <svg width="170" height="170" viewBox="0 0 170 170" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="g503a" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#fbbf24" />
                        <stop offset="100%" stop-color="#f59e0b" />
                    </linearGradient>
                    <linearGradient id="g503b" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#667eea" />
                        <stop offset="100%" stop-color="#764ba2" />
                    </linearGradient>
                    <filter id="glow503">
                        <feGaussianBlur stdDeviation="4" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <rect x="32" y="45" width="96" height="80" rx="8" fill="url(#g503b)" opacity="0.35" />
                <rect x="36" y="50" width="88" height="16" rx="5" fill="rgba(255,255,255,0.12)" />
                <circle cx="108" cy="58" r="4" fill="#10b981" opacity="0.9" />
                <circle cx="118" cy="58" r="4" fill="#ef4444" opacity="0.9" class="blink-anim" />
                <g class="wrench-anim" style="transform-origin: 135px 110px;">
                    <path
                        d="M120 95 L148 123 C151 126 151 131 148 134 C145 137 140 137 137 134 L109 106 C119 100 126 97 120 95 Z"
                        fill="url(#g503a)" opacity="0.9" filter="url(#glow503)" />
                    <circle cx="124" cy="99" r="10" fill="url(#g503a)" opacity="0.9" filter="url(#glow503)" />
                    <circle cx="119" cy="104" r="6" fill="rgba(15,12,41,0.7)" />
                </g>
                <path d="M30 145 L45 110 L60 145 Z" fill="url(#g503a)" opacity="0.7" />
                <rect x="25" y="143" width="40" height="8" rx="3" fill="url(#g503a)" opacity="0.5" />
            </svg>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-amber-500/20 text-amber-300 border border-amber-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                503 — Layanan Tidak Tersedia
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-amber-500/7 !border-amber-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Tim teknis sedang bekerja keras untuk menyelesaikan pemeliharaan. Silakan coba kembali dalam beberapa menit.
            </p>
        </x-slot>

        <x-slot name="actions">
            <button onclick="window.location.reload()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white font-semibold text-sm shadow-xl transition-all duration-300 hover:shadow-amber-500/30 hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
                style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Coba Lagi
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
        .shimmer-text-amber {
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #fbbf24);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }

        @keyframes wrench {

            0%,
            100% {
                transform: rotate(-20deg);
            }

            50% {
                transform: rotate(20deg);
            }
        }

        .wrench-anim {
            animation: wrench 1.5s ease-in-out infinite;
            transform-origin: center bottom;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        .blink-anim {
            animation: blink 2s ease-in-out infinite;
        }
    </style>
@endsection