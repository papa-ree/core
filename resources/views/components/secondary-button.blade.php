@props(['label' => 'Button'])

@php
    $classes = 'capitalize inline-flex items-center py-3 px-4 text-sm font-medium text-gray-700 transition-all bg-gray-200
                     border border-gray-300 hover:drop-shadow-md rounded-lg hs-accordion-toggle hover:bg-gray-200/40 hover:text-gray-900 focus:outline-hidden
                     focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-700 dark:text-gray-300
                     dark:hover:bg-gray-900/20 dark:hover:text-white dark:focus:bg-gray-800/20 dark:focus:text-white
            ';
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