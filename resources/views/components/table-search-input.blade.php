@props(['placeholder'])
<div class="sm:flex items-center gap-x-3">
    <div class="relative">
        <div wire:loading wire:target='query'
            class="absolute inset-y-0 flex items-center pt-3 pointer-events-none start-0 ps-4">
            <div class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent text-emerald-600 rounded-full"
                role="status" aria-label="loading">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <input type="text" wire:model.live.debounce.500ms="query"
            class="block w-full px-4 py-3 text-sm bg-gray-100 border-transparent rounded-lg peer ps-11 focus:border-emerald-300 focus:ring-emerald-300 disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-700 dark:border-transparent dark:text-neutral-300 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
            placeholder="{{ $placeholder }}">
        <div wire:loading.remove wire:target='query'
            class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-4 peer-disabled:opacity-50 peer-disabled:pointer-events-none">
            <svg class="shrink-0 text-gray-500 size-4 dark:text-neutral-500" xmlns="http://www.w3.org/2000/svg"
                width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.3-4.3"></path>
            </svg>
        </div>
    </div>
</div>