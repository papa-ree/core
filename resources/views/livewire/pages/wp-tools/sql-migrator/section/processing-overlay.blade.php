{{-- ===================================================================
|  Section: Processing Overlay
|  Full-screen spinner shown while wire:target="import" is loading.
=================================================================== --}}
<div
    wire:loading
    wire:target="import"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 flex flex-col items-center gap-5 max-w-sm mx-4">
        <div class="relative">
            <div class="w-16 h-16 rounded-full border-4 border-indigo-100 dark:border-indigo-900"></div>
            <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-transparent border-t-indigo-500 animate-spin"></div>
        </div>
        <div class="text-center">
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Migrating Posts…') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('Parsing SQL, cleaning HTML, and writing to the tenant database. Please wait.') }}
            </p>
        </div>
        {{-- Progress pulse --}}
        <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-full">
            <div class="w-2 h-2 bg-indigo-600 rounded-full animate-pulse"></div>
            <span class="text-xs font-medium text-indigo-700 dark:text-indigo-400">{{ __('In progress') }}</span>
        </div>
    </div>
</div>
