@props(['item' => '', 'itemId' => '', 'route' => '', 'deleteButton' => true])

<div class="hs-dropdown shrink-0 relative inline-block select-none items-center gap-x-3 [--placement:bottom-right]">

    {{-- edit button --}}
    <a href="{{ route($route, $item) }}" wire:navigate.hover
        class="p-2.5 inline-flex justify-center cursor-pointer items-center gap-2 rounded-lg text-gray-700 hover:text-emerald-500 hover:bg-emerald-100/70 align-middle disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-emerald-300 transition-all text-sm dark:text-gray-200 dark:hover:text-white dark:focus:ring-offset-emerald-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="size-4 shrink-0 lucide lucide-square-pen-icon lucide-square-pen">
            <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
            <path
                d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
        </svg>
    </a>

    {{-- delete button --}}
    <button id="bale-delete-confirmation-dropdown-{{ $item }}" type="button"
        class="hs-dropdown-toggle p-2.5 inline-flex justify-center cursor-pointer items-center gap-2 rounded-lg text-gray-700 hover:text-red-500 hover:bg-red-100 align-middle disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-emerald-300 transition-all text-sm dark:text-gray-200 dark:hover:text-white dark:focus:ring-offset-emerald-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="size-4 shrink-0 lucide lucide-trash2-icon lucide-trash-2">
            <path d="M10 11v6" />
            <path d="M14 11v6" />
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
            <path d="M3 6h18" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
    </button>

    <div class="hs-dropdown-menu transition-[opacity,margin] border border-gray-200 dark:border-gray-700 duration hs-dropdown-open:opacity-100 z-20 opacity-0 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 dark:bg-gray-800"
        role="menu" aria-orientation="vertical" aria-labelledby="bale-delete-confirmation-dropdown-{{ $item }}">
        {{-- <div class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-center">
            <div
                class="justify-center flex items-center w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="text-red-400 size-4 shrink-0 lucide lucide-triangle-alert-icon lucide-triangle-alert">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{('Are you sure?')}}</p>
            <p class="text-sm font-medium text-gray-800 dark:text-gray-300">Data will delete from application</p>
        </div> --}}
        <div class="sm:flex sm:items-start py-3 px-4 border-b border-gray-200 dark:border-gray-700">
            <div
                class="flex items-center justify-center shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                <h3 class="text-sm font-semibold leading-6 text-gray-700 dark:text-white">
                    Delete this item?
                </h3>
                <div class="mt-1.5">
                    <p class="text-sm text-gray-500 dark:text-white">
                        This action cannot be undone.
                    </p>
                </div>
            </div>
        </div>
        <div class="p-1 space-y-0.5">
            <div wire:click="$dispatch('deleteItem', '{{ $itemId }}')"
                class="flex items-center justify-center text-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 dark:text-red-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:bg-gray-700">
                Yes!
            </div>
        </div>
    </div>
</div>

{{-- <div
    class="hs-dropdown shrink-0 relative inline-block select-none [--auto-close:inside] [--placement:bottom-right]"
    x-data="{showConfirmation: false}" x-cloak @click.away="showConfirmation=false">
    <button id="bale-option-dropdown-{{ $item }}" type="button"
        class="hs-dropdown-toggle py-1.5 px-2 inline-flex justify-center cursor-pointer items-center gap-2 rounded-lg text-gray-700 align-middle disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-emerald-300 transition-all text-sm dark:text-gray-200 dark:hover:text-white dark:focus:ring-offset-emerald-500">
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
            <circle cx="5" cy="12" r="1" />
        </svg>
    </button>
    <div class="hs-dropdown-menu transition-[opacity,margin] border border-gray-200 dark:border-gray-700 duration hs-dropdown-open:opacity-100 opacity-0 hidden divide-y divide-gray-200 min-w-40 z-20 bg-white shadow-2xl rounded-lg p-2 mt-2 dark:divide-gray-700 dark:bg-gray-800 dark:border"
        aria-labelledby="bale-option-dropdown-{{ $item }}">
        <div class="py-2 first:pt-0 last:pb-0">
            <div class="" x-show="!showConfirmation">
                <a class="flex items-center px-3 py-2 text-sm text-gray-800 rounded-lg gap-x-3 hover:bg-gray-100 focus:ring-2 focus:ring-emerald-500 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    href="{{ route($route, $item) }}" wire:navigate.hover>
                    Edit
                </a>
            </div>
        </div>
        @if ($deleteButton)
        <button id="bale-option-delete-{{ $item }}" @click="showConfirmation=!showConfirmation"
            x-show="!showConfirmation"
            class="flex items-center cursor-pointer w-full px-3 py-2 text-sm text-red-600 rounded-lg gap-x-3 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/50">
            Delete
        </button>
        @endif --}}

        {{--
        <div class="hs-dropdown shrink-0 relative block select-none [--placement:left]">


            <button id="bale-option-delete-{{ $item }}" x-show="showConfirmation"
                class=" flex items-center cursor-pointer w-full px-3 py-2 text-sm text-red-600 rounded-lg gap-x-3 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/50">
                Yes!
            </button> --}}

            {{-- <button id="bale-option-delete-{{ $item }}" @click="showConfirmation=!showConfirmation"
                class="hs-dropdown-toggle flex items-center cursor-pointer w-full px-3 py-2 text-sm text-red-600 rounded-lg gap-x-3 hover:bg-gray-100 focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/50">
                Delete
            </button> --}}

            {{-- <div
                class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden divide-y divide-gray-200 min-w-40 z-20 bg-white shadow-2xl rounded-lg p-2 mt-2 dark:divide-gray-700 dark:bg-red-900 dark:border dark:border-gray-700"
                aria-labelledby="bale-option-delete-{{ $item }}">
                <div class="py-2 first:pt-0 last:pb-0 z-20">
                    <div wire:click="$dispatch('deleteItem', '{{ $itemId }}')"
                        class="flex items-center cursor-pointer w-full px-3 py-2 text-sm text-red-600  rounded-lg gap-x-3 hover:bg-red-300/50 focus:ring-2 focus:ring-red-500 dark:text-red-200 dark:hover:bg-red-700/50">
                        Yes!
                    </div>
                </div>
            </div> --}}
            {{--
        </div> --}}
        {{--
    </div>
</div> --}}