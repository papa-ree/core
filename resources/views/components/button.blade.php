@props(['label' => 'Button'])

@php
    $classes =
        'px-4 py-3 min-w-28 text-center items-center justify-center select-none border cursor-pointer border-blue-300 dark:border-blue-300/70 text-white text-sm antialiased items-center flex tracking-wide transition-all duration-300 rounded-lg bg-gradient-to-tl from-blue-500 via-teal-500 to-emerald-500 hover:ring-2 hover:ring-emerald-300 capitalize';
@endphp
<div x-data="{ disabledButton: false }">
{{-- bg-gradient-to-tl from-blue-500 via-teal-500 to-emerald-500 --}}
    @if ($attributes->has('link') || $attributes->has('link-reload'))
        <a
            {{ $attributes->merge([
                'type' => 'submit',
                'class' => $classes,
                'wire:navigate.hover' => $attributes->has('link'), // Add wire:navigate.hover if has link
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