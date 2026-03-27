@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Server Mengalami Masalah"
        description="Terjadi kesalahan internal di sisi server saat memproses permintaan Anda. Tim teknis kami sedang bekerja untuk memperbaikinya."
        shimmerClass="shimmer-text-red-500">
        <x-slot name="illustration">
            <div class="relative shake-anim">
                <svg width="160" height="160" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g500a" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#ef4444" />
                            <stop offset="100%" stop-color="#dc2626" />
                        </linearGradient>
                        <linearGradient id="g500b" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#f97316" />
                            <stop offset="100%" stop-color="#ea580c" />
                        </linearGradient>
                        <filter id="glow500">
                            <feGaussianBlur stdDeviation="4" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>
                    <circle cx="80" cy="80" r="36" fill="url(#g500a)" opacity="0.85" filter="url(#glow500)" />
                    <circle cx="80" cy="80" r="26" fill="rgba(15,12,41,0.7)" />
                    <rect x="72" y="38" width="16" height="14" rx="5" fill="url(#g500a)" opacity="0.85" />
                    <rect x="72" y="108" width="16" height="14" rx="5" fill="url(#g500a)" opacity="0.85" />
                    <rect x="38" y="72" width="14" height="16" rx="5" fill="url(#g500a)" opacity="0.85" />
                    <rect x="108" y="72" width="14" height="16" rx="5" fill="url(#g500a)" opacity="0.85" />
                    <path d="M87 62 L73 82 L82 82 L73 98 L91 76 L82 76 Z" fill="rgba(255,255,255,0.85)" opacity="0.9" />
                    <circle cx="130" cy="36" r="8" fill="url(#g500b)" opacity="0.9" filter="url(#glow500)" />
                    <line x1="127" y1="33" x2="133" y2="39" stroke="white" stroke-width="2" stroke-linecap="round" />
                    <line x1="133" y1="33" x2="127" y2="39" stroke="white" stroke-width="2" stroke-linecap="round" />
                </svg>
            </div>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-red-500/20 text-red-300 border border-red-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                500 — Kesalahan Server Internal
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-red-500/7 !border-red-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-red-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Ini adalah masalah di sisi server, bukan kesalahan dari tindakan Anda. Silakan coba muat ulang halaman atau
                kembali ke dashboard.
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
            <button onclick="window.location.reload()"
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-white/80 font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
                style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Coba Lagi
            </button>
        </x-slot>
    </x-core::error-card>

    <style>
        .shimmer-text-red-500 {
            background: linear-gradient(90deg, #f87171, #ef4444, #f87171);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-4px) rotate(-1deg);
            }

            40% {
                transform: translateX(4px) rotate(1deg);
            }

            60% {
                transform: translateX(-3px);
            }

            80% {
                transform: translateX(3px);
            }
        }

        .shake-anim {
            animation: shake 0.8s ease-in-out 1s both;
        }
    </style>
@endsection