@extends('core::layouts.guest')

@section('content')

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

        @keyframes tick {

            0%,
            100% {
                transform: scaleY(1);
            }

            50% {
                transform: scaleY(0.92);
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

        .floatAnim {
            animation: float 6s ease-in-out infinite;
        }

        .pulse-ring {
            animation: pulse-ring 2s ease-out infinite;
        }

        .tick-anim {
            animation: tick 1s ease-in-out infinite;
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

        .shimmer-text {
            background: linear-gradient(90deg, #667eea, #f093fb, #667eea);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
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

        .digit-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .progress-track {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>

    <div x-data="lockoutPage()"
        class="min-h-screen gradient-bg flex items-center justify-center p-4 relative overflow-hidden">
        {{-- Ambient blobs --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full opacity-20"
                style="background: radial-gradient(circle, #667eea, transparent 70%);"></div>
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

        <div class="relative z-10 w-full max-w-lg">

            {{-- Illustration --}}
            <div class="flex justify-center mb-8 slide-up">
                <div class="relative floatAnim">
                    {{-- Pulse ring --}}
                    <div class="absolute inset-0 rounded-full pulse-ring" :class="{'opacity-0': secondsLeft <= 0}"
                        style="background: rgba(102,126,234,0.3);"></div>

                    {{-- Inline SVG Illustration --}}
                    <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="shieldGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#667eea" />
                                <stop offset="100%" stop-color="#764ba2" />
                            </linearGradient>
                            <linearGradient id="lockGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#f093fb" />
                                <stop offset="100%" stop-color="#667eea" />
                            </linearGradient>
                            <linearGradient id="clockGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#a78bfa" />
                                <stop offset="100%" stop-color="#60a5fa" />
                            </linearGradient>
                            <filter id="glow">
                                <feGaussianBlur stdDeviation="4" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>

                        {{-- Shield --}}
                        <path d="M90 12 L145 35 L145 85 C145 118 90 145 90 145 C90 145 35 118 35 85 L35 35 Z"
                            fill="url(#shieldGrad)" opacity="0.9" filter="url(#glow)" />
                        <path d="M90 24 L133 44 L133 82 C133 109 90 132 90 132 C90 132 47 109 47 82 L47 44 Z"
                            fill="rgba(255,255,255,0.08)" />

                        {{-- Lock body --}}
                        <rect x="68" y="80" width="44" height="36" rx="8" fill="url(#lockGrad)" opacity="0.95" />
                        <path d="M76 80 L76 68 C76 56 104 56 104 68 L104 80" stroke="url(#lockGrad)" stroke-width="7"
                            fill="none" stroke-linecap="round" opacity="0.9" />

                        {{-- Lock keyhole --}}
                        <circle cx="90" cy="96" r="6" fill="rgba(255,255,255,0.5)" />
                        <rect x="87" y="96" width="6" height="10" rx="3" fill="rgba(255,255,255,0.5)" />

                        {{-- Clock (bottom right of shield) --}}
                        <circle cx="130" cy="130" r="24" fill="url(#clockGrad)" opacity="0.9" filter="url(#glow)" />
                        <circle cx="130" cy="130" r="19" fill="rgba(15,12,41,0.7)" />
                        <circle cx="130" cy="130" r="2" fill="white" />

                        {{-- Clock hands --}}
                        <line x1="130" y1="130" x2="130" y2="116" stroke="white" stroke-width="2.5"
                            stroke-linecap="round" />
                        <line x1="130" y1="130" x2="139" y2="136" stroke="white" stroke-width="2" stroke-linecap="round"
                            opacity="0.8" />

                        {{-- Clock ticks --}}
                        <line x1="130" y1="113" x2="130" y2="116" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"
                            stroke-linecap="round" />
                        <line x1="130" y1="144" x2="130" y2="147" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"
                            stroke-linecap="round" />
                        <line x1="113" y1="130" x2="116" y2="130" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"
                            stroke-linecap="round" />
                        <line x1="144" y1="130" x2="147" y2="130" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"
                            stroke-linecap="round" />

                        {{-- Small floating lock top-left --}}
                        <g opacity="0.4" transform="translate(18, 22) scale(0.55)">
                            <rect x="2" y="14" width="20" height="16" rx="4" fill="#a78bfa" />
                            <path d="M6 14 L6 8 C6 2 18 2 18 8 L18 14" stroke="#a78bfa" stroke-width="3.5" fill="none"
                                stroke-linecap="round" />
                        </g>

                        {{-- Small floating lock far right --}}
                        <g opacity="0.3" transform="translate(148, 45) scale(0.4)">
                            <rect x="2" y="14" width="20" height="16" rx="4" fill="#f093fb" />
                            <path d="M6 14 L6 8 C6 2 18 2 18 8 L18 14" stroke="#f093fb" stroke-width="3.5" fill="none"
                                stroke-linecap="round" />
                        </g>

                        {{-- Stars / sparkles --}}
                        <circle cx="155" cy="25" r="2" fill="#f0f4ff" opacity="0.7" />
                        <circle cx="28" cy="155" r="1.5" fill="#f0f4ff" opacity="0.5" />
                        <circle cx="160" cy="75" r="1" fill="#f0f4ff" opacity="0.4" />
                    </svg>
                </div>
            </div>

            {{-- Card --}}
            <div class="card-glass rounded-3xl p-8 shadow-2xl slide-up-d1">

                {{-- Status badge --}}
                <div class="flex justify-center mb-6">
                    <span
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest"
                        :class="{
                                                  'bg-red-500/20 text-red-300 border border-red-500/30'   : lockType === 'ip_block',
                                                  'bg-orange-500/20 text-orange-300 border border-orange-500/30' : lockType === 'account_lock',
                                                  'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30' : lockType === 'rate_limit' || !lockType,
                                              }">
                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="{
                                                      'bg-red-400'   : lockType === 'ip_block',
                                                      'bg-orange-400': lockType === 'account_lock',
                                                      'bg-yellow-400': lockType === 'rate_limit' || !lockType,
                                                  }"></span>
                        <span x-show="lockType === 'ip_block'">IP Diblokir</span>
                        <span x-show="lockType === 'account_lock'">Akun Dikunci</span>
                        <span x-show="lockType === 'rate_limit' || !lockType">Akses Dibatasi</span>
                    </span>
                </div>

                {{-- Heading --}}
                <h1 class="text-center text-3xl font-extrabold text-white mb-2 slide-up-d1">
                    <span x-show="lockType === 'ip_block'">Akses Diblokir</span>
                    <span x-show="lockType === 'account_lock'">Akun Dikunci</span>
                    <span x-show="lockType === 'rate_limit' || !lockType">
                        <span class="shimmer-text">Terlalu Banyak<br>Percobaan Gagal</span>
                    </span>
                </h1>

                {{-- Reason message --}}
                <p class="text-center text-white/60 text-sm leading-relaxed mb-8 max-w-sm mx-auto slide-up-d2"
                    x-text="lockReason"></p>

                {{-- ====== COUNTDOWN (saat masih locked) ====== --}}
                <div x-show="secondsLeft > 0" class="slide-up-d2">

                    {{-- Label --}}
                    <p class="text-center text-white/40 text-xs uppercase tracking-widest font-semibold mb-4">
                        Silakan coba kembali dalam
                    </p>

                    {{-- Timer digits --}}
                    <div class="flex items-center justify-center gap-4 mb-6">
                        {{-- Jam (jika > 3600) --}}
                        <template x-if="initialSeconds >= 3600">
                            <div class="flex flex-col items-center">
                                <div class="digit-card w-20 h-20 rounded-2xl flex items-center justify-center tick-anim">
                                    <span class="text-3xl font-black text-white tabular-nums"
                                        x-text="String(Math.floor(secondsLeft / 3600)).padStart(2, '0')"></span>
                                </div>
                                <span class="text-white/40 text-xs mt-2 font-medium">jam</span>
                            </div>
                        </template>

                        <template x-if="initialSeconds >= 3600">
                            <span class="text-3xl font-black text-white/30 mb-5">:</span>
                        </template>

                        {{-- Menit --}}
                        <div class="flex flex-col items-center">
                            <div class="digit-card w-20 h-20 rounded-2xl flex items-center justify-center tick-anim">
                                <span class="text-3xl font-black text-white tabular-nums"
                                    x-text="String(Math.floor((secondsLeft % 3600) / 60)).padStart(2, '0')"></span>
                            </div>
                            <span class="text-white/40 text-xs mt-2 font-medium">menit</span>
                        </div>

                        <span class="text-3xl font-black text-white/30 mb-5">:</span>

                        {{-- Detik --}}
                        <div class="flex flex-col items-center">
                            <div class="digit-card w-20 h-20 rounded-2xl flex items-center justify-center tick-anim">
                                <span class="text-3xl font-black text-white tabular-nums"
                                    x-text="String(secondsLeft % 60).padStart(2, '0')"></span>
                            </div>
                            <span class="text-white/40 text-xs mt-2 font-medium">detik</span>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div class="progress-track rounded-full h-1.5 mb-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-1000" :style="progressBarStyle">
                        </div>
                    </div>
                    <div class="flex justify-between text-white/25 text-xs mb-6">
                        <span>Selesai</span>
                        <span x-text="`${Math.ceil((secondsLeft / initialSeconds) * 100)}% tersisa`"></span>
                    </div>

                    {{-- Info note --}}
                    <div class="flex items-start gap-3 rounded-2xl p-4"
                        style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 opacity-60" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" :class="{
                                                     'text-red-400'   : lockType === 'ip_block',
                                                     'text-orange-400': lockType === 'account_lock',
                                                     'text-purple-400': lockType === 'rate_limit' || !lockType,
                                                 }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-white/50 text-xs leading-relaxed">
                            <span x-show="lockType === 'ip_block'">Alamat IP Anda terdeteksi melakukan aktivitas
                                mencurigakan. Tunggu hingga pemblokiran berakhir atau hubungi administrator sistem.</span>
                            <span x-show="lockType === 'account_lock'">Akun ini dikunci karena terlalu banyak percobaan
                                gagal dari berbagai lokasi. Sistem akan membuka akses secara otomatis setelah waktu
                                berakhir.</span>
                            <span x-show="lockType === 'rate_limit' || !lockType">Untuk menjaga keamanan sistem, akses login
                                dibatasi sementara. Pastikan username dan password yang Anda gunakan sudah benar sebelum
                                mencoba kembali.</span>
                        </p>
                    </div>
                </div>

                {{-- ====== SETELAH COUNTDOWN BERAKHIR ====== --}}
                <div x-show="secondsLeft <= 0" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                    class="text-center">

                    {{-- Checkmark icon --}}
                    <div class="flex justify-center mb-5">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center"
                            style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-white mb-2">Akses Sudah Dapat Digunakan</h3>
                    <p class="text-white/50 text-sm mb-8">Waktu pemblokiran telah berakhir. Silakan masuk kembali.</p>

                    {{-- CTA Button --}}
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center gap-3 w-full px-6 py-4 rounded-2xl text-white font-semibold text-base shadow-2xl transition-all duration-300 hover:shadow-purple-500/30 hover:scale-[1.02] active:scale-[0.98]"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Masuk ke BALé CMS
                    </a>
                </div>
            </div>

            {{-- Footer --}}
            <p class="text-center text-white/25 text-xs mt-8 slide-up-d3">
                &copy; {{ date('Y') }} Dinas Kominfo dan Statistik Kabupaten Ponorogo
            </p>
        </div>
    </div>

    <script>
        function lockoutPage ()
        {
            const lockoutSeconds = {{ session('lockout_seconds', 0) }};
            const lockoutReason = @json(session('lockout_reason', 'Terlalu banyak percobaan gagal. Silakan coba beberapa saat lagi.'));
            const lockoutType = @json(session('lockout_type', 'rate_limit'));

            return {
                secondsLeft: lockoutSeconds,
                initialSeconds: lockoutSeconds,
                lockReason: lockoutReason,
                lockType: lockoutType,
                _timer: null,

                init ()
                {
                    if ( this.secondsLeft > 0 ) {
                        this._timer = setInterval( () =>
                        {
                            if ( this.secondsLeft > 0 ) {
                                this.secondsLeft--;
                            } else {
                                clearInterval( this._timer );
                            }
                        }, 1000 );
                    }
                },

                // Computed: progress bar inline style (color + width)
                get progressBarStyle ()
                {
                    const pct = this.initialSeconds > 0
                        ? Math.max( 0, ( this.secondsLeft / this.initialSeconds ) * 100 )
                        : 0;
                    const color = this.lockType === 'ip_block'
                        ? 'linear-gradient(to right, #ef4444, #f87171)'
                        : this.lockType === 'account_lock'
                            ? 'linear-gradient(to right, #f97316, #fb923c)'
                            : 'linear-gradient(to right, #a855f7, #ec4899)';
                    return `width: ${ pct }%; background: ${ color };`;
                },
            };
        }
    </script>

@endsection