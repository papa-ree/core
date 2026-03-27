@props(['label' => 'Button'])

@php
    $classes = 'inline-flex items-center justify-center gap-x-2 px-6 py-3 text-sm font-semibold transition-all duration-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none capitalize text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 shadow-sm focus:ring-gray-200 dark:focus:ring-slate-700';
@endphp

<div x-data="{ disabledButton: false }">
    @if ($attributes->has('link'))
        <a wire:navigate.hover {{ $attributes->merge(['class' => $classes]) }}>
            {{ __($label) }}
        </a>
    @elseif ($attributes->has('link-reload'))
        <a {{ $attributes->merge(['class' => $classes]) }}>
            {{ __($label) }}
        </a>
    @else
        <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }} :disabled="disabledButton"
            x-on:disabling-button.window="disabledButton=$event.detail.params">
            @isset($icon)
                {{ $icon }}
            @endisset
            {{ __($label) }}
        </button>
    @endif
</div>