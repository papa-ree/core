{{--
_login-form-inner.blade.php
Shared login form body — included by both mobile and desktop login layouts.

Variables:
$dark (bool) — true when rendered on the dark gradient (mobile), false on light bg (desktop)
--}}

@php
    $isDark = $dark ?? false;
    $labelClass = $isDark ? 'text-white/80' : 'text-gray-700 dark:text-gray-300';
    $labelErrCls = $isDark ? 'text-red-300' : 'text-red-600 dark:text-red-400';
    $inputBase = 'block w-full px-4 py-3 rounded-xl border text-sm transition-all duration-200 focus:outline-none';
    $inputNormal = $isDark
        ? 'bg-white/10 border-white/20 text-white placeholder-white/40 focus:border-purple-400 focus:ring-0 input-purple'
        : 'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 input-purple';
    $inputError = $isDark
        ? 'bg-red-500/10 border-red-400/60 text-white placeholder-red-300/50 input-red'
        : 'bg-red-50 dark:bg-red-900/20 border-red-400 dark:border-red-500 text-gray-900 dark:text-gray-100 input-red';
    $hasErrors = $errors->any();
@endphp

{{-- ── Error Banner ──────────────────────────────────────────── --}}
@if ($hasErrors)
    <div class="mb-5 rounded-2xl overflow-hidden shake"
        style="border: 1px solid rgba(239,68,68,0.35); background: {{ $isDark ? 'rgba(239,68,68,0.12)' : 'linear-gradient(135deg,rgba(254,242,242,.95),rgba(255,235,235,.8))' }};"
        x-data="{ open: true }" x-show="open">
        <div class="flex items-start gap-3 px-4 py-3.5">
            {{-- Warning icon --}}
            <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg mt-0.5"
                style="background: rgba(239,68,68,0.18);">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold {{ $isDark ? 'text-red-300' : 'text-red-700 dark:text-red-300' }} mb-0.5">
                    {{ __('Login Gagal') }}
                </p>
                @foreach ($errors->all() as $error)
                    <p class="text-xs {{ $isDark ? 'text-red-300/80' : 'text-red-600/80 dark:text-red-400/80' }}">{{ $error }}
                    </p>
                @endforeach

                {{-- Remaining attempts indicator --}}
                @php $attemptsRemaining = session('attempts_remaining') @endphp
                @if (!is_null($attemptsRemaining))
                    <div class="flex items-center gap-1.5 mt-2 pt-2" style="border-top: 1px solid rgba(239,68,68,0.22);">
                        <div class="flex gap-0.5">
                            @for ($i = 0; $i < 3; $i++)
                                <div
                                    class="w-4 h-1.5 rounded-full {{ $i < (3 - $attemptsRemaining) ? 'bg-red-400' : ($isDark ? 'bg-white/20' : 'bg-red-200') }}">
                                </div>
                            @endfor
                        </div>
                        <p class="text-xs font-medium {{ $isDark ? 'text-red-300' : '' }}"
                            style="{{ !$isDark ? 'color: ' . ($attemptsRemaining <= 1 ? '#dc2626' : '#b91c1c') : '' }};">
                            @if ($attemptsRemaining <= 0)
                                {{ __('Akun akan segera dikunci') }}
                            @else
                                {{ $attemptsRemaining }} {{ __('percobaan tersisa sebelum dikunci') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            {{-- Close button --}}
            <button type="button" @click="open = false"
                class="shrink-0 {{ $isDark ? 'text-red-300/70 hover:text-red-200' : 'text-red-400 hover:text-red-600' }} transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
{{-- ── END Error Banner ──────────────────────────────────────── --}}

<form method="POST" action="{{ route('login.store') }}" autocomplete="off">
    @csrf

    {{-- Username --}}
    <div class="mb-4">
        <label for="{{ $isDark ? 'username_m' : 'username_d' }}"
            class="block mb-1.5 text-xs font-semibold uppercase tracking-wide {{ $hasErrors ? $labelErrCls : $labelClass }}">
            Username
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                <svg class="w-4 h-4 {{ $isDark ? 'text-white/40' : 'text-gray-400 dark:text-gray-500' }}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <input type="text" name="username" id="{{ $isDark ? 'username_m' : 'username_d' }}"
                value="{{ old('username') }}" placeholder="{{ __('Masukkan username') }}" autofocus autocomplete="off"
                class="{{ $inputBase }} {{ $hasErrors ? $inputError : $inputNormal }} pl-10 {{ $hasErrors ? 'pr-10' : '' }}" />
            @if ($hasErrors)
                <div class="absolute inset-y-0 right-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            @endif
        </div>
    </div>

    {{-- Password --}}
    <div class="mb-6">
        <label for="{{ $isDark ? 'password_m' : 'password_d' }}"
            class="block mb-1.5 text-xs font-semibold uppercase tracking-wide {{ $hasErrors ? $labelErrCls : $labelClass }}">
            Password
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                <svg class="w-4 h-4 {{ $isDark ? 'text-white/40' : 'text-gray-400 dark:text-gray-500' }}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <input :type="showPassword ? 'text' : 'password'" name="password"
                id="{{ $isDark ? 'password_m' : 'password_d' }}" placeholder="{{ __('Masukkan password') }}"
                autocomplete="off" class="{{ $inputBase }} {{ $hasErrors ? $inputError : $inputNormal }} pl-10 pr-11" />
            {{-- Show/hide toggle --}}
            <button type="button" @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-3 flex items-center px-1 {{ $isDark ? 'text-white/40 hover:text-white/70' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300' }} transition-colors"
                :title="showPassword ? '{{ __('Sembunyikan') }}' : '{{ __('Tampilkan') }}'">
                {{-- Eye open --}}
                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{-- Eye closed --}}
                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            </button>
        </div>
    </div>

    {!! RecaptchaV3::field('login') !!}

    {{-- Submit / Loading --}}
    <div>
        {{-- Active submit --}}
        <button type="submit" :disabled="!recaptchaValue" x-show="recaptchaValue"
            class="w-full px-6 py-3.5 text-sm font-bold text-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-purple-500/40 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <span class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                {{ __('Masuk') }}
            </span>
        </button>

        {{-- Loading placeholder while reCAPTCHA initialises --}}
        <button type="button" disabled x-show="!recaptchaValue"
            class="w-full px-6 py-3.5 text-sm font-semibold rounded-2xl flex items-center justify-center gap-2 cursor-not-allowed
                       {{ $isDark ? 'bg-white/10 text-white/50 border border-white/15' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600' }}">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            {{ __('Memuat…') }}
        </button>
    </div>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t {{ $isDark ? 'border-white/10' : 'border-gray-200 dark:border-gray-700' }}">
            </div>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span
                class="px-2 {{ $isDark ? 'bg-transparent text-white/40' : 'bg-white dark:bg-gray-900 text-gray-500' }}">
                {{ __('Atau masuk dengan') }}
            </span>
        </div>
    </div>

    {{-- SSO Button --}}
    <div>
        <a href="/login"
            class="w-full px-6 py-3.5 text-sm font-semibold rounded-2xl flex items-center justify-center gap-3 transition-all duration-300
                  {{ $isDark
    ? 'bg-white/5 hover:bg-white/10 text-white border border-white/10 hover:border-white/20'
    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm' }}">
            <svg class="w-5 h-5 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                <polyline points="10 17 15 12 10 7"></polyline>
                <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            {{ __('Login SSO') }}
        </a>
    </div>


    {{-- Secure notice --}}
    <div class="mt-5 flex items-center justify-center gap-1.5">
        <svg class="w-3.5 h-3.5 {{ $isDark ? 'text-white/30' : 'text-gray-400 dark:text-gray-600' }}" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span class="text-xs {{ $isDark ? 'text-white/30' : 'text-gray-400 dark:text-gray-600' }}">
            {{ __('Koneksi terenkripsi · Protected by reCAPTCHA') }}
        </span>
    </div>
</form>