@extends('core::layouts.error')

@section('content')
    <x-core::error-card title="Anda Tidak Diizinkan"
        description="{{ $exception?->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman atau sumber daya yang diminta.' }}"
        shimmerClass="shimmer-text-red">
        <x-slot name="illustration">
            <div class="relative">
                <div class="absolute inset-0 rounded-full animate-pulse-ring" style="background: rgba(239,68,68,0.3);">
                </div>
                <svg width="160" height="160" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g403a" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#ef4444" />
                            <stop offset="100%" stop-color="#dc2626" />
                        </linearGradient>
                        <filter id="glow403">
                            <feGaussianBlur stdDeviation="4" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>
                    <path d="M80 14 L128 34 L128 78 C128 108 80 134 80 134 C80 134 32 108 32 78 L32 34 Z" fill="url(#g403a)"
                        opacity="0.9" filter="url(#glow403)" />
                    <path d="M80 26 L116 43 L116 76 C116 101 80 122 80 122 C80 122 44 101 44 76 L44 43 Z"
                        fill="rgba(255,255,255,0.07)" />
                    <line x1="64" y1="64" x2="96" y2="96" stroke="white" stroke-width="8" stroke-linecap="round"
                        opacity="0.9" />
                    <line x1="96" y1="64" x2="64" y2="96" stroke="white" stroke-width="8" stroke-linecap="round"
                        opacity="0.9" />
                </svg>
            </div>
        </x-slot>

        <x-slot name="badge">
            <span
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest bg-red-500/20 text-red-300 border border-red-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                403 — Akses Ditolak
            </span>
        </x-slot>

        <x-slot name="info" class="!bg-red-500/7 !border-red-500/15">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-red-400 opacity-70" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <p class="text-white/50 text-xs leading-relaxed">
                Jika Anda merasa ini adalah kesalahan, hubungi administrator untuk meminta hak akses yang sesuai dengan
                peran Anda.
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
        .shimmer-text-red {
            background: linear-gradient(90deg, #ef4444, #f87171, #ef4444);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 0.8;
            }

            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        .animate-pulse-ring {
            animation: pulse-ring 2s ease-out infinite;
        }
    </style>
@endsection