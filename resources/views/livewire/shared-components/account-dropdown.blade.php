<div>
    <div>
        <div class="hs-dropdown relative inline-flex [--placement:bottom-right] [--auto-close:inside]">
            <button type="button" id="hs-account-dropdown"
                class="block rounded-full transition duration-300 bg-gray-100 text-gray-600 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[.05] dark:hover:border-white/[.1] dark:hover:text-white">
                <span class="inline-flex items-center justify-center group shrink-0 size-9">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-user-round-icon lucide-user-round shrink-0 size-4">
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                </span>
            </button>

            <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg p-2 dark:bg-gray-800 dark:border dark:border-gray-700"
                aria-labelledby="hs-account-dropdown">

                <div class="flex items-center justify-between px-5 py-3 -m-2 bg-gray-100 rounded-t-lg dark:bg-gray-700">
                    <div class="">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Signed in as</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ auth()->user()->name }}</p>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">{{ auth()->user()->username }}
                        </p>
                    </div>
                </div>

                <div class="py-2 mt-2 first:pt-0 last:pb-0">

                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf

                        <x-core::dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();"
                            class="flex items-center gap-x-3.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>

                            {{ __('Log Out') }}
                        </x-core::dropdown-link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>