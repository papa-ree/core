<div>
    @if($isLoggedIn)
        <div x-data="{ open: true }" x-show="open" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="fixed z-40 bottom-0 left-0 right-0 p-3 sm:bottom-auto sm:top-20 sm:left-auto sm:right-4 sm:w-80 sm:p-0">
            {{-- Card --}}
            <div class="relative overflow-hidden rounded-2xl shadow-2xl"
                style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 60%, #a855f7 100%);">

                {{-- Decorative blobs --}}
                <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full" style="background: rgba(255,255,255,0.12);">
                </div>
                <div class="absolute -bottom-4 -left-4 w-16 h-16 rounded-full" style="background: rgba(255,255,255,0.08);">
                </div>

                <div class="relative p-4 sm:p-5">
                    {{-- Header row --}}
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            {{-- Shield icon --}}
                            <div
                                class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            {{-- SSO Badge --}}
                            <span
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-300 animate-pulse inline-block"></span>
                                {{ __('SSO Active') }}
                            </span>
                        </div>
                        {{-- Close button --}}
                        <button @click="open = false"
                            class="ml-2 shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors duration-150"
                            aria-label="{{ __('Close') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="mb-4">
                        <p class="text-xs text-white/70 mb-0.5">{{ __('Welcome back,') }}</p>
                        <p class="text-base font-bold text-white leading-tight truncate">{{ $userName }}</p>
                        <p class="text-xs text-white/60 mt-0.5">{{ __('Login via SSO') }}</p>
                    </div>

                    {{-- Dashboard button --}}
                    <button type="button" onclick="window.location.href='{{ $dashboardUrl }}'"
                        class="flex items-center cursor-pointer justify-center gap-2 w-full px-4 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 bg-white text-indigo-700 hover:bg-indigo-50 hover:shadow-lg active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                            <path
                                d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                        </svg>
                        {{ __('Open Dashboard') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>