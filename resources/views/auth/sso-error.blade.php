@extends('core::layouts.login')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-5 py-12"
        style="background: linear-gradient(140deg, #0f0c29 0%, #302b63 50%, #24243e 100%);">

        <div class="w-full max-w-md rounded-3xl p-8 shadow-2xl"
            style="background: rgba(255,255,255,0.10); backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,0.20);">

            <div class="flex justify-center mb-5">
                <div class="w-14 h-14 rounded-full flex items-center justify-center"
                    style="background: rgba(239,68,68,0.18);">
                    <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
            </div>

            <h1 class="text-xl font-bold text-white text-center mb-2">{{ __('Login SSO Gagal') }}</h1>
            <p class="text-white/60 text-sm text-center mb-8">
                {{ __('Sesi login tidak dapat diselesaikan. Silakan coba lagi.') }}
            </p>

            <div class="space-y-3">
                <a href="{{ route('login') }}"
                    class="block w-full px-6 py-3.5 text-center text-sm font-bold text-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-purple-500/40 hover:scale-[1.02] active:scale-[0.98]"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    {{ __('Coba Lagi') }}
                </a>

                <a href="{{ route('force.login') }}"
                    class="block w-full px-6 py-3.5 text-center text-sm font-semibold text-white/80 rounded-2xl transition-colors duration-300 hover:text-white"
                    style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
                    {{ __('Login Paksa') }}
                </a>
            </div>

            <p class="mt-8 text-xs text-center text-gray-400 dark:text-gray-600">
                &copy; {{ date('Y') }} Dinas Kominfo dan Statistik Kabupaten Ponorogo
            </p>
        </div>
    </div>
@endsection
