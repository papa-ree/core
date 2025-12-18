<div>
    <header
        class="sticky top-0 inset-x-0 flex flex-wrap sm:justify-start sm:flex-nowrap z-48 w-full bg-white border-b border-gray-300 text-sm py-2.5 sm:py-4 lg:pl-64 dark:bg-gray-800 dark:border-gray-700">
        <nav class="flex items-center w-full px-4 mx-auto basis-full sm:px-6 md:px-8" aria-label="Global">
            <div class="flex items-center mr-5 space-x-3 lg:mr-0 lg:hidden">
                <button type="button" class="text-gray-500 hover:text-gray-600" data-hs-overlay="#application-sidebar"
                    aria-controls="application-sidebar" aria-label="Toggle navigation">
                    <span class="sr-only">Toggle Navigation</span>
                    <svg class="w-5 h-5" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
                    </svg>
                </button>
                {{-- <a class="flex-none text-xl font-semibold dark:text-white" href="#" aria-label="Brand">{{
                    env('APP_NAME') }}</a> --}}
            </div>

            <div class="flex items-center justify-end w-full ml-auto sm:justify-between sm:gap-x-3 sm:order-3">
                {{-- <div class="sm:hidden">
                    <button type="button"
                        class="inline-flex flex-shrink-0 justify-center items-center gap-2 h-[2.375rem] w-[2.375rem] rounded-full font-medium bg-white text-gray-700 align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-white transition-all text-xs dark:bg-gray-800 dark:hover:bg-slate-800 dark:text-gray-400 dark:hover:text-white dark:focus:ring-gray-700 dark:focus:ring-offset-gray-800">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                        </svg>
                    </button>
                </div> --}}

                <div class="hidden sm:block">
                    {{-- <label for="icon" class="sr-only">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 z-20 flex items-center pl-4 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                fill="currentColor" viewBox="0 0 16 16">
                                <path
                                    d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                            </svg>
                        </div>
                        <input type="text" id="icon" name="icon"
                            class="block w-full px-4 py-2 text-sm border-gray-200 rounded-md shadow-sm pl-11 focus:z-10 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                            placeholder="Search">
                    </div> --}}
                </div>

                <div class="flex flex-row items-center justify-end gap-2">

                    {{-- get from package --}}
                    <x-core::dark-mode-toggle />
                    {{-- <livewire:shared-components.volt.topbar-notification-dropdown />
                    <livewire:shared-components.volt.topbar-locale-dropdown />
                    --}}
                    <livewire:core.shared-components.account-dropdown />

                </div>
            </div>
        </nav>
    </header>
</div>