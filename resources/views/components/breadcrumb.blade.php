@props(['items' => [], 'active' => '', 'back' => false, 'href' => '', 'label' => ''])

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm">
        @if ($back && $href && $label)
            {{-- Back Link Mode (Simulating breadcrumb style within new UI) --}}
            <a href="{{ $href }}" wire:navigate.hover
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-gray-600 hover:text-purple-600 dark:text-gray-400 dark:hover:text-purple-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-purple-300 dark:hover:border-purple-600 transition-all">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Back to {{ $label }}</span>
            </a>
            @if ($active)
                <x-lucide-chevron-right class="w-4 h-4 text-gray-400" />
                <span
                    class="px-3 py-1.5 text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg font-medium">
                    {{ $active }}
                </span>
            @endif
        @else
            {{-- Standard Breadcrumb Mode --}}
            @foreach ($items as $item)
                <a href="{{ route($item['route'], $item['params'] ?? []) }}" wire:navigate.hover
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-gray-600 hover:text-purple-600 dark:text-gray-400 dark:hover:text-purple-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-purple-300 dark:hover:border-purple-600 transition-all">
                    @if (isset($item['icon']))
                        <x-dynamic-component :component="'lucide-' . $item['icon']" class="w-3.5 h-3.5" />
                    @endif
                    <span>{{ $item['label'] }}</span>
                </a>
                <x-lucide-chevron-right class="w-4 h-4 text-gray-400" />
            @endforeach

            @if ($active)
                <span
                    class="px-3 py-1.5 text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg font-medium">
                    {{ $active }}
                </span>
            @endif
        @endif
    </div>
</div>