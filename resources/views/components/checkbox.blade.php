@props([
    'disabled' => false,
])

<input type="checkbox" {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => 'peer sr-only']) }}>

<span class="shrink-0 flex items-center justify-center size-4.5 rounded-md
             border-2 transition-all duration-200
             border-gray-300 dark:border-gray-600
             peer-checked:border-purple-500 peer-checked:bg-purple-500
             dark:peer-checked:border-purple-500 dark:peer-checked:bg-purple-500
             text-transparent peer-checked:text-white
             {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}">
    <x-lucide-check class="size-2.5" />
</span>
