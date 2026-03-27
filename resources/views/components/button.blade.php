@props(['label' => 'Button', 'variant' => 'primary'])

@php
    $baseClasses = 'inline-flex items-center cursor-pointer justify-center gap-x-2 px-6 py-3 text-sm font-semibold transition-all duration-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none capitalize';
    
    $variantClasses = match($variant) {
        'primary' => 'text-white cursor-pointer border border-transparent bg-linear-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 shadow-lg hover:shadow-xl focus:ring-purple-500',
        'secondary' => 'text-gray-700 cursor-pointer dark:text-gray-300 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 shadow-sm focus:ring-gray-200 dark:focus:ring-slate-700',
        'success' => 'text-white cursor-pointer border border-transparent bg-linear-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-lg hover:shadow-xl focus:ring-emerald-500',
        'danger' => 'text-white cursor-pointer border border-transparent bg-linear-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-lg hover:shadow-xl focus:ring-red-500',
        default => 'text-white cursor-pointer border border-transparent bg-linear-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 shadow-lg hover:shadow-xl focus:ring-purple-500',
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