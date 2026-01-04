@extends('core::layouts.guest')

<style>
    .grecaptcha-badge {
        visibility: hidden !important;
    }
</style>
{!! RecaptchaV3::initJs() !!}

<div x-data="recaptchaHandler()" class="min-h-screen bg-gray-50 dark:bg-gray-900" x-cloak>
    <div class="flex justify-center min-h-screen">
        {{-- Left Side - Gradient Banner --}}
        <div class="hidden lg:flex lg:w-1/2 xl:w-2/3 relative overflow-hidden">
            {{-- Purple Gradient Background --}}
            <div class="absolute inset-0"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);"></div>

            {{-- Decorative Elements --}}
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-32 -mb-32"></div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 text-white">
                <div class="mb-8">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-2xl bg-white/20 backdrop-blur-md">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
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
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Multi-tenant Support</p>
                            <p class="text-white/80 text-sm">Kelola berbagai website dalam satu platform</p>
                        </div>
                    </div>
                    {{-- <div class="flex items-start gap-3">
                        <div class="shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center mt-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Secure & Reliable</p>
                            <p class="text-white/80 text-sm">Keamanan tingkat enterprise dengan backup otomatis</p>
                        </div>
                    </div> --}}
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center mt-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
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
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
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

                    <form method="POST" action="{{ route('login') }}" autocomplete="off">
                        @csrf

                        {{-- Username Field --}}
                        <div class="mb-5">
                            <label for="username"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Username
                            </label>
                            <input type="text" name="username" id="username" placeholder="Masukkan username" autofocus
                                autocomplete="off"
                                class="block w-full px-4 py-3 text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-lg dark:placeholder-gray-500 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 dark:focus:ring-purple-400/20 focus:outline-none transition-colors" />
                            <x-core::input-error for="username" />
                        </div>

                        {{-- Password Field --}}
                        <div class="mb-6">
                            <label for="password"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Password
                            </label>
                            <input type="password" name="password" id="password" placeholder="Masukkan password"
                                autocomplete="off"
                                class="block w-full px-4 py-3 text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-lg dark:placeholder-gray-500 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 dark:focus:ring-purple-400/20 focus:outline-none transition-colors" />
                            <x-core::input-error for="password" />
                        </div>

                        {!! RecaptchaV3::field('login') !!}
                        <x-core::input-error for="g-recaptcha-response" />

                        {{-- Submit Button --}}
                        <div class="mt-6">
                            <button type="submit" :disabled="!recaptchaValue" x-show="recaptchaValue"
                                class="w-full px-6 py-3 text-sm font-semibold text-white transition-all duration-300 rounded-lg shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                Masuk
                            </button>

                            <button type="button" :disabled="recaptchaValue" x-show="!recaptchaValue"
                                class="w-full px-6 py-3 text-sm font-semibold text-gray-500 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 cursor-not-allowed flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-gray-600 dark:text-gray-400"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
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
    function recaptchaHandler ()
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
                observer.observe( document.body, {
                    subtree: true,
                    attributes: true
                } );
            }
        };
    }
</script>