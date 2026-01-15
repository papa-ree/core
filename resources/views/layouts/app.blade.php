<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Page Title' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,500;1,500&family=Noto+Color+Emoji&family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Open+Sans:ital,wght@0,500;1,500&family=Quicksand&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

</head>

{{-- Layout For Livewire Admin Panel --}}

<body
    class="min-h-screen bg-gray-100 dark:bg-slate-900 scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-300 scrollbar-thumb-rounded-full scrollbar-track-rounded-full overscroll-none">

    <div class="fixed inset-x-0 top-0 z-40 w-full h-full px-4 pb-10 bg-white backdrop-blur-md dark:bg-slate-900 sm:px-6 md:px-8 lg:pl-72"
        x-data="{ loader: true }" x-show="loader" x-init="setTimeout(() => loader = false, 600)"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-100">
        <div class="flex items-center justify-center h-screen mx-auto">
            <div class="animate-spin inline-block size-10 border-[3px] border-current border-t-transparent text-gray-400 rounded-full"
                role="status" aria-label="loading">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <span class="sr-only">preloader</span>
    </div>

    <livewire:core.shared-components.topbar />

    @can('dashboard')
        <livewire:rakaca.shared-components.rakaca-landlord-sidebar />
    @endcan

    <div class="w-full px-4 pt-5 pb-10 sm:px-6 md:px-8 lg:pl-72">
        <main>
            {{ $slot }}
        </main>
    </div>
    @livewireScripts

    <x-core::toast />

</body>

</html>