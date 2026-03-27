<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Error' }} | Bale</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,500;1,500&family=Noto+Color+Emoji&family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Open+Sans:ital,wght@0,500;1,500&family=Quicksand&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(-12px) rotate(-2deg);
            }

            66% {
                transform: translateY(-6px) rotate(2deg);
            }
        }

        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        }

        .card-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .floatAnim {
            animation: float 6s ease-in-out infinite;
        }

        .slide-up {
            animation: slide-up 0.6s ease-out both;
        }

        .slide-up-d1 {
            animation: slide-up 0.6s 0.1s ease-out both;
        }

        .slide-up-d2 {
            animation: slide-up 0.6s 0.2s ease-out both;
        }

        .slide-up-d3 {
            animation: slide-up 0.6s 0.35s ease-out both;
        }
    </style>
</head>

<body
    class="min-h-screen overscroll-none scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-300 scrollbar-thumb-rounded-full scrollbar-track-rounded-full">

    {{-- Preloader from Rakaca --}}
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-white backdrop-blur-md dark:bg-slate-900"
        x-data="{ loader: true }" x-show="loader" x-init="setTimeout(() => loader = false, 600)"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-100">
        <div class="animate-spin inline-block size-10 border-[3px] border-current border-t-transparent text-gray-400 rounded-full"
            role="status" aria-label="loading">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <div class="min-h-screen gradient-bg flex items-center justify-center p-4 relative overflow-hidden">
        {{-- Ambient blobs --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full opacity-20"
                style="background: radial-gradient(circle, {{ $blobColor ?? '#667eea' }}, transparent 70%);"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full opacity-20"
                style="background: radial-gradient(circle, #f093fb, transparent 70%);"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full opacity-10"
                style="background: radial-gradient(circle, #764ba2, transparent 70%);"></div>
        </div>

        {{-- Floating particles --}}
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-[15%] left-[10%] w-1.5 h-1.5 bg-purple-400/40 rounded-full"
                style="animation: float 5s 0.2s ease-in-out infinite;"></div>
            <div class="absolute top-[30%] right-[12%] w-2 h-2 bg-pink-400/30 rounded-full"
                style="animation: float 7s 1s ease-in-out infinite;"></div>
            <div class="absolute bottom-[25%] left-[18%] w-1 h-1 bg-indigo-400/40 rounded-full"
                style="animation: float 6s 0.7s ease-in-out infinite;"></div>
            <div class="absolute bottom-[15%] right-[20%] w-2.5 h-2.5 bg-purple-300/20 rounded-full"
                style="animation: float 8s 0.5s ease-in-out infinite;"></div>
            <div class="absolute top-[60%] left-[5%] w-1.5 h-1.5 bg-fuchsia-400/30 rounded-full"
                style="animation: float 5.5s 1.5s ease-in-out infinite;"></div>
        </div>

        <div class="relative z-10 w-full max-w-xl">
            @yield('content')

            {{-- Footer --}}
            <p class="text-center text-white/25 text-xs mt-8 slide-up-d3">
                &copy; {{ date('Y') }} Dinas Kominfo dan Statistik Kabupaten Ponorogo
            </p>
        </div>
    </div>

    @livewireScripts
</body>

</html>