@props(['label' => 'Button', 'variant' => 'primary'])

@php
    $baseClasses = 'inline-flex items-center cursor-pointer justify-center gap-x-2 px-6 py-3.5 text-sm font-bold transition-all duration-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none capitalize hover:scale-[1.02] active:scale-[0.98]';
    
    $variantClasses = match($variant) {
        'primary' => 'text-white border border-transparent shadow-lg hover:shadow-purple-500/40 focus:ring-purple-500',
        'secondary' => 'text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 shadow-sm focus:ring-gray-200 dark:focus:ring-slate-700 hover:shadow-md',
        'success' => 'text-white border border-transparent bg-linear-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-lg hover:shadow-emerald-500/40 focus:ring-emerald-500',
        'danger' => 'text-white border border-transparent bg-linear-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-lg hover:shadow-red-500/40 focus:ring-red-500',
        default => 'text-white border border-transparent shadow-lg hover:shadow-purple-500/40 focus:ring-purple-500',
    };
    
    $classes = $baseClasses . ' ' . $variantClasses;

    $style = in_array($variant, ['primary', 'default']) || !in_array($variant, ['secondary', 'success', 'danger'])
        ? 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'
        : '';
@endphp

<div x-data="{ disabledButton: false }">
    @if ($attributes->has('link') || $attributes->has('link-reload'))
        <a
            {{ $attributes->merge([
                'type' => 'submit',
                'class' => $classes,
                'style' => $style,
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
                'style' => $style,
            ]) }}>
            @isset($icon)
                {{ $icon }}
            @endisset
            {{ __($label) }}
        </button>
    @endif
</div>