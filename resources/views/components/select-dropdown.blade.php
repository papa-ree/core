@props([
    'label'      => '',
    'items'      => [],
    'model'      => 'livewireModel',
    'labelField' => 'itemTitle',
    'valueField' => 'itemSlug',
    'placeholder' => 'Pilih...',
])

<div
    x-data="{
        open: false,
        selected: $wire.entangle('{{ $model }}'),
        get selectedLabel() {
            const items = {{ Js::from($items) }};
            const found = items.find(i => i['{{ $valueField }}'] === this.selected);
            return found ? found['{{ $labelField }}'] : '{{ $placeholder }}';
        }
    }"
    class="w-full"
    x-on:keydown.escape="open = false"
    x-on:click.outside="open = false"
>
    {{-- Label --}}
    @if($label)
        <x-core::label :value="__($label)" />
    @endif

    {{-- Trigger Button --}}
    <div class="relative">
        <button
            type="button"
            x-on:click="open = !open"
            :aria-expanded="open"
            class="w-full flex items-center justify-between px-4 py-3 text-sm
                   bg-white dark:bg-gray-800
                   border border-gray-300 dark:border-gray-600
                   rounded-xl
                   text-gray-900 dark:text-white
                   transition-all duration-200
                   hover:border-purple-400 dark:hover:border-purple-500
                   focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                   disabled:opacity-50 disabled:pointer-events-none"
            :class="open ? 'border-purple-500 ring-2 ring-purple-500/30 dark:border-purple-500' : ''"
        >
            <span
                class="truncate"
                :class="selected ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
                x-text="selectedLabel"
            ></span>

            {{-- Chevron Icon --}}
            <svg
                class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500 transition-transform duration-200 ml-2"
                :class="open ? 'rotate-180 text-purple-500' : ''"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
            >
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>

        {{-- Dropdown Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
            class="absolute z-50 mt-1.5 w-full
                   bg-white dark:bg-gray-800
                   border border-gray-200 dark:border-gray-700
                   rounded-xl shadow-xl shadow-gray-200/50 dark:shadow-black/30
                   overflow-hidden"
            style="display: none;"
        >
            <div class="p-1.5 space-y-0.5 max-h-56 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
                @foreach ($items as $key => $item)
                    <button
                        type="button"
                        wire:key="{{ $key }}"
                        x-on:click="selected = '{{ $item[$valueField] }}'; $wire.set('{{ $model }}', '{{ $item[$valueField] }}'); open = false;"
                        class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-lg
                               text-gray-700 dark:text-gray-300
                               transition-colors duration-150
                               hover:bg-purple-50 dark:hover:bg-purple-900/20
                               hover:text-purple-700 dark:hover:text-purple-400"
                        :class="selected === '{{ $item[$valueField] }}'
                            ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 font-semibold'
                            : ''"
                    >
                        <span>{{ $item[$labelField] }}</span>

                        {{-- Check icon for selected --}}
                        <svg
                            x-show="selected === '{{ $item[$valueField] }}'"
                            class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round"
                            style="display: none;"
                        >
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>