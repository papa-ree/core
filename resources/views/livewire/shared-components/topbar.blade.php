<header
    class="sticky top-0 inset-x-0 z-48 w-full border-b backdrop-blur-md bg-white/80 dark:bg-slate-900/80 border-slate-200 dark:border-slate-700/60 lg:pl-72 transition-all duration-300">
    <nav class="flex items-center w-full px-4 sm:px-6 md:px-8 py-2.5 sm:py-3" aria-label="Global">
        {{-- ========== Mobile Actions (Left) ========== --}}
        <div class="flex items-center gap-x-3 lg:hidden">
            <button type="button"
                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-500 hover:text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-300 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                data-hs-overlay="#application-sidebar" aria-controls="application-sidebar"
                aria-label="Toggle navigation">
                <span class="sr-only">Toggle Navigation</span>
                <x-lucide-menu class="shrink-0 w-6 h-6" />
            </button>

            {{-- Mobile Tenant Logo/Name --}}
            @if ($this->activeBale)
                <div
                    class="flex items-center gap-2 px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg max-w-[140px] xs:max-w-none">
                    <div
                        class="shrink-0 w-6 h-6 rounded-md bg-linear-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                        <x-lucide-building-2 class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">
                        {{ $this->activeBale->name }}
                    </span>
                </div>
            @else
                <div class="lg:hidden flex items-center gap-2">
                    <span
                        class="text-sm font-bold bg-linear-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">
                        {{ config('app.name') }}
                    </span>
                </div>
            @endif
        </div>

        {{-- ========== Desktop Content (Center/Left) ========== --}}
        <div class="hidden lg:flex items-center gap-x-4">
            {{-- Placeholder for Page Specific Content if needed --}}
        </div>

        {{-- ========== Right Side Actions ========== --}}
        <div class="flex items-center justify-end flex-1 gap-x-1.5 sm:gap-x-3">
            <div class="flex items-center gap-x-1 sm:gap-x-2">
                {{-- Theme Toggle --}}
                <div class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <x-core::dark-mode-toggle />
                </div>

                {{-- Locale Dropdown --}}
                <div class="hidden sm:block">
                    <livewire:core.shared-components.locale-dropdown />
                </div>

                {{-- Account Dropdown --}}
                <div class="pl-2 sm:pl-3 border-l border-slate-200 dark:border-slate-700 ml-1">
                    <livewire:core.shared-components.account-dropdown />
                </div>
            </div>
        </div>
    </nav>
</header>