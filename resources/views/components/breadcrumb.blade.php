@props(['items' => [], 'active' => '', 'back' => false, 'href' => '', 'label' => ''])

<div class="mb-6">
    <nav {{ $attributes->merge(['class' => 'flex items-center text-sm']) }}>
        @if ($back && $href && $label)
            {{-- Back Link Mode --}}
            <a href="{{ $href }}" wire:navigate.hover
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-gray-500 hover:text-purple-600 dark:text-gray-400 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>{{ __('Back to') }} {{ $label }}</span>
            </a>
            @if ($active)
                <x-lucide-chevron-right class="w-4 h-4 text-gray-300 dark:text-gray-600 mx-1" />
                <span class="px-2.5 py-1 text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 rounded-lg font-medium">
                    {{ $active }}
                </span>
            @endif
        @else
            {{-- Standard Breadcrumb Mode --}}
            @foreach ($items as $index => $item)
                @if ($index > 0)
                    <x-lucide-chevron-right class="w-4 h-4 text-gray-300 dark:text-gray-600 mx-1" />
                @endif
                <a href="{{ route($item['route'], $item['params'] ?? []) }}" wire:navigate.hover
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-gray-500 hover:text-purple-600 dark:text-gray-400 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors">
                    @if (isset($item['icon']))
                        <x-dynamic-component :component="'lucide-' . $item['icon']" class="w-3.5 h-3.5" />
                    @endif
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            @if ($active)
                <x-lucide-chevron-right class="w-4 h-4 text-gray-300 dark:text-gray-600 mx-1" />
                <span class="px-2.5 py-1 text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 rounded-lg font-medium">
                    {{ $active }}
                </span>
            @endif
        @endif
    </nav>
</div>
