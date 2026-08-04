<div>
    <div x-data="{
            activeGroup: null,

            open(key) {
                this.activeGroup = key;
            },

            close() {
                this.activeGroup = null;
            },

            isActive(key) {
                return this.activeGroup === key;
            }
        }" @keydown.escape.window="close()">

        {{-- =====================================================
        PRIMARY SIDEBAR
        Fixed narrow column on the left.
        Desktop: always visible (w-[72px])
        Mobile : hidden by default, toggled via topbar button
        ====================================================== --}}
        <aside id="application-sidebar" class="hs-overlay hs-overlay-open:translate-x-0 -translate-x-full
                   transition-all duration-300 transform
                   fixed top-0 left-0 bottom-0 z-50
                   w-64
                   flex flex-col
                   bg-linear-to-b from-slate-900 via-slate-900 to-slate-800
                   border-r border-slate-700/50
                   overflow-visible
                   hidden lg:flex lg:translate-x-0">

            {{-- ========== Branding ========== --}}
            <div class="shrink-0 px-4 pt-6 pb-4">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-linear-to-br from-indigo-500 to-purple-600
                                flex items-center justify-center shadow-lg">
                        <x-lucide-award class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-widest leading-none mb-1">
                            {{ __('Landlord') }}
                        </p>
                        <p class="text-sm font-bold text-white tracking-tight">Rakaca Dash</p>
                    </div>
                </div>
            </div>

            {{-- ========== Divider ========== --}}
            <div class="mx-3 mb-3 h-px bg-slate-700/60"></div>

            {{-- ========== Group Buttons ========== --}}
            <nav class="flex-1 flex flex-col gap-0.5 px-3 overflow-y-auto
                        scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                @foreach ($this->menuGroups as $group)
                    <button type="button" @click="open('{{ $group['key'] }}')" :class="isActive('{{ $group['key'] }}')
                                            ? 'bg-indigo-600/25 border border-indigo-500/40 text-white'
                                            : 'text-slate-400 hover:text-white hover:bg-white/8 border border-transparent'"
                        class="group relative w-full flex items-center gap-3
                                               px-3 py-2.5 rounded-lg text-sm font-medium
                                               transition-all duration-150 ease-in-out cursor-pointer">
                        {{-- Icon --}}
                        <span class="shrink-0 transition-colors duration-150"
                            :class="isActive('{{ $group['key'] }}') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400'">
                            <x-dynamic-component :component="'lucide-' . ($group['icon'] ?? 'box')" class="w-5 h-5" />
                        </span>

                        {{-- Full Label --}}
                        <span class="flex-1 text-left capitalize tracking-wide truncate">
                            {{ $group['label'] }}
                        </span>

                        {{-- Chevron indicator --}}
                        <span class="shrink-0 transition-all duration-150"
                            :class="isActive('{{ $group['key'] }}') ? 'text-indigo-400 rotate-180' : 'text-slate-600 group-hover:text-slate-400 rotate-0'">
                            <x-lucide-chevron-right class="w-3.5 h-3.5" />
                        </span>
                    </button>
                @endforeach
            </nav>

            {{-- ========== Bottom Spacer ========== --}}
            <div class="shrink-0 pb-6"></div>

        </aside>
        {{-- END Primary Sidebar --}}


        {{-- =====================================================
        SECONDARY SIDEBAR
        Appears to the right of the primary sidebar on desktop.
        On mobile: appears above the primary sidebar (overlay).
        ====================================================== --}}

        {{-- Backdrop: full-screen.
        Mobile : z-54 (di atas primary sidebar z-50), gelap + blur, menutup primary sidebar & konten.
        Desktop: z-40, transparan, hanya menutup area konten utama untuk outside-click dismiss. --}}
        <div x-show="activeGroup !== null" x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="close()"
            class="fixed inset-0 z-54 lg:z-40 bg-slate-900/40 backdrop-blur-[2px] lg:bg-transparent lg:backdrop-blur-none"
            aria-hidden="true"></div>

        {{-- Secondary Panel:
        Mobile : w-64 (sama dengan primary), menutupi primary sidebar (z-55), full height.
        Desktop: w-60, nempel di kanan primary sidebar (left-64). --}}
        <aside x-show="activeGroup !== null" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-4" class="fixed z-55 w-64 lg:w-60 flex flex-col
                   bg-slate-900/95 backdrop-blur-sm
                   border-r border-slate-700/60
                   shadow-2xl shadow-slate-900/50
                   overflow-hidden
                   top-0 bottom-0 left-0 lg:left-64
                   rounded-none">

            @foreach ($this->menuGroups as $group)
                <div x-show="activeGroup === '{{ $group['key'] }}'" x-transition:enter="transition duration-150"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="flex flex-col h-full">
                    {{-- ===== Header ===== --}}
                    <div class="shrink-0 flex items-center justify-between px-4 pt-5 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-indigo-600/20 border border-indigo-500/30
                                                         flex items-center justify-center">
                                <x-dynamic-component :component="'lucide-' . ($group['icon'] ?? 'box')"
                                    class="w-3.5 h-3.5 text-indigo-400" />
                            </span>
                            <h3 class="text-sm font-semibold text-white tracking-tight">
                                {{ $group['label'] }}
                            </h3>
                        </div>

                        {{-- Close / Back button (Mobile: Back arrow, Desktop: X close) --}}
                        <button type="button" @click="close()" class="w-7 h-7 flex items-center justify-center rounded-lg
                                                   text-slate-500 hover:text-white hover:bg-white/10
                                                   transition-colors duration-150 cursor-pointer"
                            aria-label="{{ __('Close menu') }}">
                            <x-lucide-arrow-left class="w-4 h-4 lg:hidden" />
                            <x-lucide-x class="w-4 h-4 hidden lg:block" />
                        </button>
                    </div>

                    {{-- Divider --}}
                    <div class="shrink-0 mx-4 mb-2 h-px bg-slate-700/60"></div>

                    {{-- ===== Menu Items ===== --}}
                    <nav class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-700
                                                scrollbar-track-transparent px-3 pb-6">
                        <ul class="space-y-0.5">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a href="/{{ $item['url'] }}" wire:navigate.hover class="group flex items-center gap-3 px-3 py-2.5 rounded-lg
                                                                               text-sm font-medium text-slate-400
                                                                               hover:text-white hover:bg-white/8
                                                                               transition-all duration-150 ease-in-out"
                                        wire:current="bg-indigo-600/25 border border-indigo-500/40 text-white shadow-xs">
                                        {{-- Icon --}}
                                        <span class="shrink-0 text-slate-500 group-hover:text-indigo-400
                                                                                     transition-colors duration-150">
                                            <x-dynamic-component :component="'lucide-' . ($item['icon'] ?? 'box')"
                                                class="w-4 h-4" />
                                        </span>

                                        {{-- Label --}}
                                        <span class="capitalize tracking-wide truncate">
                                            {{ __($item['label']) }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </div>
            @endforeach

        </aside>
        {{-- END Secondary Sidebar --}}

    </div>
</div>