@props([
    'label' => '',
    'sortBy' => null,
    'sortField' => null,
    'sortDirection' => 'asc',
    'align' => 'left',
])

@php
    $alignmentClasses = match ($align) {
        'center' => 'text-center justify-center',
        'right' => 'text-right justify-end',
        default => 'text-left justify-start',
    };
@endphp

<th scope="col" {{ $attributes->merge(['class' => 'px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-white']) }}>
    @if ($sortBy)
        <button wire:click="sort('{{ $sortBy }}')" type="button"
            class="group inline-flex items-center gap-x-2 {{ $alignmentClasses }} w-full focus:outline-none focus:text-emerald-600 dark:focus:text-emerald-400">
            <span class="text-xs font-semibold tracking-wide text-gray-800 uppercase dark:text-gray-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                {{ $label ?: $slot }}
            </span>
            <span class="flex-none rounded text-gray-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-400">
                @if ($sortField === $sortBy)
                    @if ($sortDirection === 'asc')
                        <x-lucide-chevron-up class="size-4" />
                    @else
                        <x-lucide-chevron-down class="size-4" />
                    @endif
                @else
                    <x-lucide-chevrons-up-down class="size-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                @endif
            </span>
        </button>
    @else
        <div class="flex items-center gap-x-2 {{ $alignmentClasses }}">
            <span class="text-xs font-semibold tracking-wide text-gray-800 uppercase dark:text-gray-200">
                {{ $label ?: $slot }}
            </span>
        </div>
    @endif
</th>
