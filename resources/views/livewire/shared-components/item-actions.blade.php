<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}>
    {{-- Custom Actions Slot --}}
    {{ $slot('actions') }}

    {{-- Edit Action --}}
    @if($editUrl)
        <a href="{{ $editUrl }}" wire:navigate.hover
            class="p-2 text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 rounded transition-all"
            title="Edit">
            <x-lucide-edit class="w-4 h-4" />
        </a>
    @endif

    {{-- Delete Action with Dropdown Confirmation --}}
    @if($deleteId)
        <div class="hs-dropdown relative inline-flex [--placement:bottom-right]"
            x-init="window.HSStaticMethods.autoInit(['dropdown'])">
            <button id="hs-delete-dropdown-{{ $deleteId }}" type="button"
                class="hs-dropdown-toggle p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 rounded transition-all"
                title="Delete">
                <x-lucide-trash-2 class="w-4 h-4" />
            </button>

            <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-xl rounded-xl p-4 mt-2 dark:bg-neutral-800 dark:border dark:border-neutral-700 z-70"
                aria-labelledby="hs-delete-dropdown-{{ $deleteId }}">
                <div class="text-center">
                    <div
                        class="size-10 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-lucide-alert-triangle class="size-5" />
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-neutral-200">
                        Confirm Delete
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-neutral-500 mt-1">
                        {{ $confirmMessage }}
                    </p>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <button wire:click="delete"
                        class="w-full py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-red-500 transition-all">
                        Yes, Delete Item
                    </button>
                    <button type="button"
                        class="hs-dropdown-toggle w-full py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 transition-all">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>