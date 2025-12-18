@props(['value'])

<label {{ $attributes->merge(['class' => 'block capitalize text-sm font-medium mb-2 dark:text-white']) }}>
    {{ $value ?? $slot }}
</label>
