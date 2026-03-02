@extends('core::layouts.guest')

<style>
    .grecaptcha-badge {
        visibility: hidden !important;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        15% {
            transform: translateX(-6px);
        }

        30% {
            transform: translateX(6px);
        }

        45% {
            transform: translateX(-4px);
        }

        60% {
            transform: translateX(4px);
        }

        75% {
            transform: translateX(-2px);
        }
    }

    .shake {
        animation: shake 0.45s ease;
    }
</style>
{!! RecaptchaV3::initJs() !!}

<div x-data="loginPage()" class="min-h-screen bg-gray-50 dark:bg-gray-900" x-cloak>
    <div class="flex justify-center min-h-screen">

        {{-- Left Side - Gradient Banner --}}
        <div class="hidden lg:flex lg:w-1/2 xl:w-2/3 relative overflow-hidden">
            <div class="absolute inset-0"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-32 -mb-32"></div>

            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 text-white">
                <div class="mb-8">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-2xl bg-white/20 backdrop-blur-md">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h1 class="text-4xl xl:text-5xl font-bold mb-4">BALé CMS</h1>
                    <p class="text-xl text-white/90 max-w-xl">
                        Content Management System yang modern dan powerful untuk Dinas Kominfo Ponorogo
                    </p>
                </div>

                <div class="space-y-4 max-w-md">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center mt-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Multi-tenant Support</p>
                            <p class="text-white/80 text-sm">Kelola berbagai website dalam satu platform</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center mt-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Easy to Use</p>
                            <p class="text-white/80 text-sm">Interface intuitif untuk pengelolaan konten</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Login Form --}}
        <div class="flex items-center justify-center w-full lg:w-1/2 xl:w-1/3 px-6 py-12">
            <div class="w-full max-w-md">

                {{-- Logo Mobile --}}
                <div class="lg:hidden mb-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-2xl"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">BALé CMS</h2>
                </div>

                {{-- Login Card --}}
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">

                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Selamat Datang</h2>
                        <p class="text-gray-600 dark:text-gray-400">Masuk ke akun Anda untuk melanjutkan</p>
                    </div>

                    {{-- ============================================================
                    ERROR BANNER — tampil jika ada error validasi dari Laravel
                    ============================================================ --}}
                    @if ($errors->any())
                        <div class="mb-6 rounded-xl overflow-hidden shake"
                            style="border: 1px solid rgba(239,68,68,0.3); background: linear-gradient(135deg, rgba(254,242,242,0.9), rgba(255,235,235,0.7));"
                            x-data="{ open: true }" x-show="open">
                            <div class="flex items-start gap-3 px-4 py-3.5">
                                {{-- Ikon peringatan --}}
                                <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg mt-0.5"
                                    style="background: rgba(239,68,68,0.15);">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-0.5">
                                        Login Gagal
                                    </p>
                                    @foreach ($errors->all() as $error)
                                        <p class="text-xs text-red-600/80 dark:text-red-400/80">{{ $error }}</p>
                                    @endforeach

                                    {{-- Sisa percobaan sebelum dikunci --}}
                                    @php $attemptsRemaining = session('attempts_remaining') @endphp
                                    @if (!is_null($attemptsRemaining))
                                        <div class="flex items-center gap-1.5 mt-2 pt-2"
                                            style="border-top: 1px solid rgba(239,68,68,0.2);">
                                            {{-- 3 dot: merah = sudah gagal, abu = tersisa --}}
                                            <div class="flex gap-0.5">
                                                @for ($i = 0; $i < 3; $i++)
                                                    <div
                                                        class="w-4 h-1.5 rounded-full {{ $i < (3 - $attemptsRemaining) ? 'bg-red-500' : 'bg-red-200' }}">
                                                    </div>
                                                @endfor
                                            </div>
                                            <p class="text-xs font-medium"
                                                style="color: {{ $attemptsRemaining <= 1 ? '#dc2626' : '#b91c1c' }};">
                                                @if ($attemptsRemaining <= 0)
                                                    Akun akan segera dikunci
                                                @else
                                                    {{ $attemptsRemaining }} percobaan tersisa sebelum dikunci
                                                @endif
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Tombol tutup --}}
                                <button type="button" @click="open = false"
                                    class="shrink-0 text-red-400 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                    {{-- END ERROR BANNER --}}

                    <form method="POST" action="{{ route('login.store') }}" autocomplete="off">
                        @csrf

                        {{-- Username Field --}}
                        <div class="mb-5">
                            <label for="username"
                                class="block mb-2 text-sm font-medium {{ $errors->has('username') ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                Username
                            </label>
                            <div class="relative">
                                <input type="text" name="username" id="username" value="{{ old('username') }}"
                                    placeholder="Masukkan username" autofocus autocomplete="off"
                                    class="block w-full px-4 py-3 text-gray-900 placeholder-gray-400 bg-white border rounded-lg
                                              dark:placeholder-gray-500 dark:bg-gray-900 dark:text-gray-100
                                              focus:outline-none focus:ring-2 transition-colors
                                              {{ $errors->any()
    ? 'border-red-400 dark:border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:focus:ring-red-500/20'
    : 'border-gray-300 dark:border-gray-600 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-purple-500/20 dark:focus:ring-purple-400/20' }}" />
                                @if ($errors->any())
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Password Field --}}
                        <div class="mb-6">
                            <label for="password"
                                class="block mb-2 text-sm font-medium {{ $errors->has('username') ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password" placeholder="Masukkan password"
                                    autocomplete="off"
                                    class="block w-full px-4 py-3 text-gray-900 placeholder-gray-400 bg-white border rounded-lg
                                              dark:placeholder-gray-500 dark:bg-gray-900 dark:text-gray-100
                                              focus:outline-none focus:ring-2 transition-colors
                                              {{ $errors->any()
    ? 'border-red-400 dark:border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:focus:ring-red-500/20'
    : 'border-gray-300 dark:border-gray-600 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-purple-500/20 dark:focus:ring-purple-400/20' }}" />
                                @if ($errors->any())
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {!! RecaptchaV3::field('login') !!}

                        {{-- Submit Button --}}
                        <div class="mt-6">
                            <button type="submit" :disabled="!recaptchaValue" x-show="recaptchaValue"
                                class="w-full px-6 py-3 text-sm font-semibold text-white transition-all duration-300 rounded-lg shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                Masuk
                            </button>

                            <button type="button" disabled x-show="!recaptchaValue"
                                class="w-full px-6 py-3 text-sm font-semibold text-gray-500 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 cursor-not-allowed flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-gray-600 dark:text-gray-400"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                Memuat...
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Footer Text --}}
                <p class="mt-8 text-xs text-center text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }} Dinas Kominfo dan Statistik Kabupaten Ponorogo
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function loginPage ()
    {
        return {
            recaptchaValue: '',

            init ()
            {
                const observer = new MutationObserver( () =>
                {
                    const input = document.querySelector( 'input[name="g-recaptcha-response"]' );
                    if ( input ) {
                        this.recaptchaValue = input.value || '';
                    }
                } );
                observer.observe( document.body, { subtree: true, attributes: true } );
            },
        };
    }
</script>