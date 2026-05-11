{{-- ===================================================================
|  Section: Info / Guide Banner
=================================================================== --}}
<div
    class="mb-6 p-5 bg-linear-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20
            border border-amber-200 dark:border-amber-800 rounded-2xl"
    data-aos="fade-up" data-aos-delay="100"
>
    <div class="flex items-start gap-4">
        <div class="p-3 bg-amber-600 rounded-xl shadow-lg shrink-0">
            <x-lucide-info class="w-6 h-6 text-white" />
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                {{ __('How it works') }}
            </h3>
            <div class="grid gap-2 md:grid-cols-2">
                <div class="flex items-start gap-2">
                    <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __("Only imports rows where post_type = 'post' AND post_status = 'publish'") }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Strips all HTML, images, and links from post content') }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Content is converted to EditorJS paragraph format') }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Slug is auto-generated from post_title using Str::slug()') }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('No external database connection — SQL is parsed as a text file') }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Table prefix is auto-detected or configurable below') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
