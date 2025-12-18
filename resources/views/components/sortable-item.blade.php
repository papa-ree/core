@props(['sortableItems' => [], 'itemLabel' => 'title', 'route' => ''])

<div id="bale-nested-sortable">
    <ul class="nested-sortable space-y-1 select-none" wire:sort="sortItem">
        @foreach ($sortableItems as $key => $item)
            <li class="nested-1 space-y-1 dark:bg-transparent" wire:key="sort-{{ $item->id }}"
                wire:sort:item="{{ $item->id }}">
                <div
                    class="p-3 flex items-center justify-between gap-x-3 cursor-grab bg-white border border-gray-200 rounded-lg font-medium text-sm text-gray-800 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-200">
                    <div class="cursor-pointer hover:text-emerald-400 flex items-center gap-x-3">
                        <svg class="shrink-0 size-4 ms-auto text-gray-500 dark:text-gray-500"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="12" r="1"></circle>
                            <circle cx="9" cy="5" r="1"></circle>
                            <circle cx="9" cy="19" r="1"></circle>
                            <circle cx="15" cy="12" r="1"></circle>
                            <circle cx="15" cy="5" r="1"></circle>
                            <circle cx="15" cy="19" r="1"></circle>
                        </svg>
                        <a href="{{ route($route, $item->slug) }}" wire:navigate.hover>
                            {{$item["$itemLabel"] }}
                        </a>
                    </div>
                    {{-- option icon here --}}
                    <x-core::option wire:key="{{ $item->id }}" :item="$item->slug" :itemId="$item->id" :route="$route" />
                </div>

                @if ($item->children)
                    <ul class="ps-10 space-y-1 nested-sortable dark:bg-transparent" wire:sort="sortItemChild">
                        @foreach ($item->children as $child)
                            <li class="nested-2 dark:bg-transparent" wire:key="{{ $child->id }}" wire:sort:item="{{ $child->id }}">
                                <div
                                    class="p-3 flex items-center justify-between gap-x-3 cursor-grab bg-white border border-gray-200 rounded-lg font-medium text-sm text-gray-800 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-200">
                                    <div class="cursor-pointer hover:text-emerald-400 flex items-center gap-x-3">
                                        <svg class="shrink-0 size-4 ms-auto text-gray-500 dark:text-gray-500"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <circle cx="9" cy="12" r="1"></circle>
                                            <circle cx="9" cy="5" r="1"></circle>
                                            <circle cx="9" cy="19" r="1"></circle>
                                            <circle cx="15" cy="12" r="1"></circle>
                                            <circle cx="15" cy="5" r="1"></circle>
                                            <circle cx="15" cy="19" r="1"></circle>
                                        </svg>
                                        <a href="{{ route($route, $child->slug) }}" wire:navigate.hover>
                                            {{$child["$itemLabel"] }}
                                        </a>
                                    </div>

                                    {{-- option icon here --}}
                                    <x-core::option wire:key="{{ $child->id }}" :item="$child->slug" :itemId="$child->id"
                                        :route="$route" />

                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

            </li>
        @endforeach
    </ul>
</div>