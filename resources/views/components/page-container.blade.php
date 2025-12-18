<div class="relative">
    <div {{ $attributes->merge([
    'class' =>
        'p-4 antialiased text-gray-800 bg-white rounded-xl sm:p-6 dark:bg-gray-800 dark:text-white border border-gray-200 dark:border-gray-700',
]) }}>
        {{ $slot }}

    </div>

</div>