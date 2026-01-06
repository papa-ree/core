@props(['label' => 'label', 'items' => [], 'model' => 'livewireModel', 'labelField' => 'itemTitle', 'valueField' => 'itemSlug'])

<x-core::label value="{{ __($label) }}" />
<div class="hs-dropdown w-full relative inline-flex [--strategy:absolute] [--trigger:click]" {!! $attributes->merge([]) !!} x-data="{ title: '', value: '' }">
    <button id="select-page-dropdown-right-but-left-on-lg" type="button"
        class="inline-flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-900 placeholder-gray-500 transition-all duration-200 bg-white border border-gray-300 form-input dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent hs-dropdown-toggle gap-x-2 hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:hover:bg-gray-600">
        <span wire:text='{{ $model }}'></span>
        <svg class="hs-dropdown-open:rotate-180 size-6" xmlns="http://www.w3.org/2000/svg" width="28" height="28"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"></path>
        </svg>
    </button>

    <div class="hs-dropdown-menu transition-[opacity,margin] w-full duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg p-2 mt-2 z-40 dark:bg-neutral-800 dark:border dark:border-neutral-700 dark:divide-neutral-700 after:h-4 after:absolute after:-bottom-4 after:start-0 after:w-full before:h-4 before:absolute before:-top-4 before:start-0 before:w-full"
        aria-labelledby="select-page-dropdown-right-but-left-on-lg">
        @foreach ($items as $key => $item)
            <label for="{{ $key }}"
                class="flex w-full p-3 text-sm transition duration-200 ease-out bg-white hover:bg-gray-200 hover:rounded-lg dark:bg-neutral-900 hover:dark:border-neutral-700 dark:text-neutral-400"
                wire:key="{{ $key }}" @click="$wire.set({{'\'' . $model . '\''}}, {{ '\'' . $item[$valueField] . '\'' }})">
                <span class="text-sm text-gray-500 dark:text-neutral-400">{{ $item[$labelField] }}</span>
                <input type="radio" name="{{ $model }}" wire:model='{{ $model }}' value="{{ $item[$valueField] }}"
                    class="shrink-0 ms-auto mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                    id="{{ $key }}">
            </label>
        @endforeach
    </div>
</div>