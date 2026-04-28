@props([
    'disabled' => false,
    'autofocus' => false,
    'rows' => 3,
])

@if ($attributes->has('label'))
    <x-core::label :value="$attributes['label']" />
@endif

<textarea
    {{ $disabled ? 'disabled' : '' }}
    {{ $autofocus ? 'autofocus' : '' }}
    rows="{{ $rows }}"
    {!! $attributes->merge([
        'class' => 'block w-full py-3 px-4
                    text-gray-900 placeholder-gray-500
                    transition-all duration-200
                    bg-white border border-gray-300 form-input rounded-xl
                    dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400
                    focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
    ]) !!}
>{{ $slot }}</textarea>
