{{-- ===================================================================
|  Section: Main Card
|  Contains the fatal error alert, then either the result or the form.
=================================================================== --}}
<div
    class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden"
    data-aos="fade-up" data-aos-delay="150"
    x-data="{ showAdvanced: false }"
>
    {{-- Card Header --}}
    <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-700
                bg-linear-to-r from-indigo-50/50 to-purple-50/50 dark:from-indigo-900/10 dark:to-purple-900/10">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-linear-to-br from-indigo-500 to-purple-600 rounded-lg shadow-md">
                <x-lucide-upload class="w-5 h-5 text-white" />
            </div>
            <div>
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ __('Upload & Configure') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Select your .sql dump file and target Bale tenant') }}</p>
            </div>
        </div>
    </div>

    {{-- Card Body --}}
    <div class="p-6 md:p-8 space-y-8">

        {{-- ── Fatal Error Alert ──────────────────────────────────── --}}
        @if($fatalError)
            <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0">
                <x-lucide-alert-triangle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400">{{ __('Migration Failed') }}</p>
                    <p class="text-sm text-red-600 dark:text-red-300 mt-1">{{ $fatalError }}</p>
                </div>
            </div>
        @endif

        {{-- ── Result or Form ─────────────────────────────────────── --}}
        @if($isDone)
            @include('core::livewire.pages.wp-tools.sql-migrator.section.result')
        @else
            @include('core::livewire.pages.wp-tools.sql-migrator.section.form')
        @endif

    </div>
</div>
