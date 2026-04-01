@extends('core::layouts.guest')

<style>
    /* ── Hide reCAPTCHA badge ── */
    .grecaptcha-badge {
        visibility: hidden !important;
    }

    /* ── Shake animation for error banner ── */
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

    /* ── Shimmer effect on brand heading ── */
    @keyframes shimmer {
        0% {
            background-position: -200% center;
        }

        100% {
            background-position: 200% center;
        }
    }

    .shimmer-brand {
        background: linear-gradient(90deg, #c4b5fd, #ffffff, #e9d5ff, #ffffff, #c4b5fd);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: shimmer 4s linear infinite;
    }

    /* ── Floating particle blobs ── */
    @keyframes floatBlob {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-18px) scale(1.04);
        }
    }

    .blob-float {
        animation: floatBlob 7s ease-in-out infinite;
    }

    .blob-float-slow {
        animation: floatBlob 10s ease-in-out infinite reverse;
    }

    /* ── Subtle glow pulse on logo icon ── */
    @keyframes glowPulse {

        0%,
        100% {
            box-shadow: 0 0 18px 4px rgba(167, 139, 250, .45);
        }

        50% {
            box-shadow: 0 0 36px 10px rgba(196, 167, 255, .65);
        }
    }

    .logo-glow {
        animation: glowPulse 3.5s ease-in-out infinite;
    }

    /* ── Input focus glow ── */
    .input-purple:focus {
        border-color: #7c3aed !important;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, .18);
        outline: none;
    }

    .input-red:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .18);
        outline: none;
    }

    /* ── Glass card (mobile) ── */
    .glass-card {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .dark .glass-card {
        background: rgba(15, 12, 41, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.10);
    }

    /* ── Hide x-cloak elements before Alpine init ── */
    [x-cloak] {
        display: none !important;
    }
</style>

<script>
    /* Reload if reCAPTCHA script fails to load */
    window.addEventListener( 'error', function ( e )
    {
        if ( e.message && e.message.includes( 'grecaptcha is not defined' ) ) {
            window.location.reload();
        }
    } );
</script>

{!! RecaptchaV3::initJs() !!}

<div x-data="loginPage()" class="min-h-screen" x-cloak>

    {{-- ════════════════════════════════════════════════════
    FLOATING CONTROLS — Dark Mode + Language (top-right)
    ════════════════════════════════════════════════════ --}}
    <div class="fixed top-4 right-4 z-50 flex items-center gap-2">
        {{-- Dark Mode Toggle --}}
        <x-core::dark-mode-toggle />

        {{-- Language Switcher --}}
        <livewire:core.shared-components.locale-dropdown />
    </div>

    {{-- ════════════════════════════════════════════════════
    MOBILE LAYOUT (< lg) Full-screen gradient + floating glass card ════════════════════════════════════════════════════
        --}} <div
        class="lg:hidden relative min-h-screen flex flex-col items-center justify-center px-5 py-14 overflow-hidden"
        style="background: linear-gradient(140deg, #0f0c29 0%, #302b63 45%, #24243e 100%);">

        {{-- Decorative blobs --}}
        <div class="blob-float absolute top-0 -left-16 w-72 h-72 rounded-full opacity-20 pointer-events-none"
            style="background: radial-gradient(circle, #667eea, #764ba2);"></div>
        <div class="blob-float-slow absolute bottom-0 -right-16 w-56 h-56 rounded-full opacity-20 pointer-events-none"
            style="background: radial-gradient(circle, #f093fb, #f5576c);"></div>
        <div class="absolute top-1/3 right-8 w-24 h-24 rounded-full opacity-10 pointer-events-none"
            style="background: #a78bfa;"></div>

        {{-- Brand header --}}
        <div class="relative z-10 text-center mb-8">
            <div class="logo-glow inline-flex items-center justify-center w-16 h-16 mb-5 rounded-2xl"
                style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22);">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h1 class="shimmer-brand text-3xl font-extrabold tracking-tight">BALé CMS</h1>
            <p class="text-white/60 text-sm mt-1">{{ __('Content Management System') }}</p>
        </div>

        {{-- Glass Login Card --}}
        <div class="relative z-10 w-full max-w-sm glass-card rounded-3xl p-7 shadow-2xl">
            @include('core::auth._login-form-inner', ['dark' => true])
        </div>

        {{-- Footer --}}
        <p class="relative z-10 mt-8 text-xs text-white/40 text-center">
            &copy; {{ date('Y') }} Dinas Kominfo dan Statistik Kabupaten Ponorogo
        </p>
</div>

{{-- ════════════════════════════════════════════════════
DESKTOP LAYOUT (≥ lg)
Left: branding panel | Right: login form
════════════════════════════════════════════════════ --}}
<div class="hidden lg:flex min-h-screen bg-gray-50 dark:bg-[#0f0c29]">

    {{-- ── LEFT: Branding Panel ────────────────────────── --}}
    <div class="relative flex w-[55%] xl:w-3/5 overflow-hidden"
        style="background: linear-gradient(140deg, #0f0c29 0%, #302b63 50%, #24243e 100%);">

        {{-- Decorative circles --}}
        <div class="blob-float absolute -top-24 -left-24 w-96 h-96 rounded-full opacity-25 pointer-events-none"
            style="background: radial-gradient(circle, #667eea, #764ba2);"></div>
        <div class="blob-float-slow absolute -bottom-24 -right-24 w-80 h-80 rounded-full opacity-20 pointer-events-none"
            style="background: radial-gradient(circle, #f093fb, #f5576c);"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[520px] h-[520px] rounded-full opacity-[0.07] pointer-events-none border border-white/20"
            style="background: radial-gradient(circle, rgba(167,139,250,0.3), transparent 70%);"></div>
        <div class="absolute top-20 right-20 w-32 h-32 rounded-full opacity-10 pointer-events-none"
            style="background: #a78bfa;"></div>
        <div class="absolute bottom-32 left-16 w-20 h-20 rounded-full opacity-10 pointer-events-none"
            style="background: #60a5fa;"></div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col justify-center px-14 xl:px-20 text-white w-full">

            {{-- Logo + Name --}}
            <div class="mb-10">
                <div class="logo-glow inline-flex items-center justify-center w-20 h-20 mb-7 rounded-3xl"
                    style="background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18);">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>

                <h1 class="shimmer-brand text-5xl xl:text-6xl font-extrabold tracking-tight leading-tight mb-4">
                    BALé CMS
                </h1>

                <p class="text-white/70 text-lg xl:text-xl max-w-lg leading-relaxed">
                    {{ __('Content Management System yang modern dan powerful dari Dinas Kominfo dan Statistik Kabupaten Ponorogo') }}
                </p>
            </div>

            {{-- Feature list --}}
            <div class="space-y-6 max-w-md">
                @foreach([
                        [
                            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                            'title' => 'Multi-tenant Support',
                            'desc' => 'Kelola berbagai website dalam satu platform terpusat'
                        ],
                        // [
                        //     'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                        //     'title' => 'Peningkatan Keamanan',
                        //     'desc' => 'Multi-layer authentication & rate-limit protection'
                        // ],
                        [
                            'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                            'title' => 'Peningkatan Performa',
                            'desc' => 'Interface responsif & optimasi konten otomatis'
                        ],
                    ] as $feature)
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 flex items-center justify-center w-9 h-9 rounded-xl mt-0.5"
                             style="background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.15);">
                            <svg class="w-4 h-4 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                          <p class="font-semibold text-white/90 text-sm leading-tight mb-1">{{ $feature['title'] }}</p>
                            <p class="text-white/55 text-xs leading-relaxed max-w-[280px]">{{ $feature['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
                {{-- Bottom badge --}}
            <div class="mt-14">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest"
                      style="background: rgba(167,139,250,0.15); border: 1px solid rgba(167,139,250,0.30); color: #c4b5fd;">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    {{ __('BALé CMS on Dinas Kominfo dan Statistik Kabupaten Ponorogo') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Login Form Panel ─────────────────────── --}}
        <div class="flex items-center justify-center w-[45%] xl:w-2/5 px-8 xl:px-14 bg-white dark:bg-gray-900">
        <div class="w-full max-w-md">
            {{-- Card header --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ __('Selamat Datang') }}</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Masuk ke akun Anda untuk melanjutkan') }}</p>
            </div>

                @include('core::auth._login-form-inner', ['dark' => false])

                {{-- Footer --}}
                <p class="mt-8 text-xs text-center text-gray-400 dark:text-gray-600">
                    &copy; {{ date('Y') }} Dinas Kominfo dan Statistik Kabupaten Ponorogo
                </p>
            </div>
        </div>
    </div>
 
           
 
</div>
  
  <script>
    function loginPage() {
        return {
             recaptchaValue: '',
              showPassword: false,

            init() {
                const observer = new MutationObserver(() => {
                    const input = document.querySelector('input[name="g-recaptcha-response"]');
                    if (input) {
                        this.recaptchaValue = input.value || '';
                    }
                });
                observer.observe(document.body, { subtree: true, attributes: true });
            },
        };
    }
</script>