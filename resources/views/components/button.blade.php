@props(['label' => 'Button', 'variant' => 'primary'])

@php
    $baseClasses = 'px-4 py-3 min-w-28 text-center items-center justify-center select-none border cursor-pointer text-white text-sm antialiased items-center flex tracking-wide transition-all duration-300 rounded-lg capitalize';
    
    $variantClasses = match($variant) {
        'primary' => 'gap-x-3 min-w-40 border-transparent bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 hover:ring-2 hover:ring-purple-300 dark:hover:ring-purple-500 shadow-md hover:shadow-lg',
        'secondary' => 'gap-x-3 min-w-40 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:ring-2 hover:ring-gray-200 dark:hover:ring-gray-600',
        'success' => 'gap-x-3 min-w-40 border-transparent bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 hover:ring-2 hover:ring-emerald-300 dark:hover:ring-emerald-500 shadow-md hover:shadow-lg',
        'danger' => 'gap-x-3 min-w-40 border-transparent bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 hover:ring-2 hover:ring-red-300 dark:hover:ring-red-500 shadow-md hover:shadow-lg',
        default => 'gap-x-3 min-w-40 border-purple-300 dark:border-purple-300/70 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 hover:ring-2 hover:ring-purple-300 dark:hover:ring-purple-500 shadow-md hover:shadow-lg',
    };
    
    $classes = $baseClasses . ' ' . $variantClasses;
@endphp

<div x-data="{ disabledButton: false }">
    @if ($attributes->has('link') || $attributes->has('link-reload'))
        <a
            {{ $attributes->merge([
                'type' => 'submit',
                'class' => $classes,
                'wire:navigate.hover' => $attributes->has('link'),
            ]) }}>
            @isset($icon)
                {{ $icon }}
            @endisset
            {{ __($label) }}
        </a>
    @else
        <button :disabled="disabledButton" x-on:disabling-button.window="disabledButton = $event.detail.params"
            {{ $attributes->merge([
                'type' => 'submit',
                'class' => $classes,
            ]) }}>
            @isset($icon)
                {{ $icon }}
            @endisset
            {{ __($label) }}
        </button>
    @endif
</div>