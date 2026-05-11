{{-- ===================================================================
|  Section: Migration Result (shown after successful import)
=================================================================== --}}
<div
    class="p-6 bg-linear-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20
           border border-emerald-200 dark:border-emerald-800 rounded-2xl"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
>
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-4">
        <div class="p-3 bg-emerald-500 rounded-xl shadow-lg">
            <x-lucide-check-circle-2 class="w-7 h-7 text-white" />
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Migration Complete!') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('The WordPress posts have been successfully imported.') }}</p>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4">

        {{-- Imported --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-emerald-100 dark:border-emerald-900 text-center shadow-sm">
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $importedCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Imported') }}</p>
        </div>

        {{-- Filtered --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-amber-100 dark:border-amber-900 text-center shadow-sm">
            <p class="text-3xl font-bold text-amber-500 dark:text-amber-400">{{ $filteredCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Filtered') }}</p>
            <p class="text-[9px] text-gray-400 mt-0.5">{{ __('(Draft/Page/Revision)') }}</p>
        </div>

        {{-- Errors --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-red-100 dark:border-red-900 text-center shadow-sm">
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $skippedCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Errors') }}</p>
        </div>

        {{-- Total --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-indigo-100 dark:border-indigo-900 text-center shadow-sm">
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $totalFoundCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Total Rows') }}</p>
        </div>
    </div>

    {{-- Row-level errors --}}
    @if(count($importErrors) > 0)
        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-2">
                <x-lucide-alert-circle class="w-4 h-4" />
                {{ __('Some rows were skipped due to errors:') }}
            </p>
            <ul class="space-y-1 max-h-40 overflow-y-auto scrollbar-thin scrollbar-thumb-amber-400 scrollbar-track-amber-100">
                @foreach($importErrors as $err)
                    <li class="text-xs text-amber-800 dark:text-amber-300 font-mono">• {{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Reset Button --}}
    <div class="mt-5 flex justify-end">
        <button
            wire:click="resetForm"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                   text-sm font-semibold text-gray-700 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400
                   transition-all duration-200 shadow-sm"
        >
            <x-lucide-refresh-cw class="w-4 h-4" />
            {{ __('Migrate Another File') }}
        </button>
    </div>
</div>
