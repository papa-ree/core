@assets
    <script defer src="https://unpkg.com/@alpinejs/ui@3.15.2/dist/cdn.min.js"></script>
@endassets
@dump($items)
@props([
    'items' => [],
    'valueField' => 'id',
    'labelField' => 'name',
    'placeholder' => 'Choose option...',
])

<div
    :x-data="listboxComponent({
        items: @js($items),
        valueField: '{{ $valueField }}',
        labelField: '{{ $labelField }}',
        entangled: $wire.entangle($attributes->wire('model'))
    })"
>
    <div x-listbox x-model="selected" class="relative">

        <label x-listbox:label class="sr-only">{{ $placeholder }}</label>

        <button x-listbox:button
            class="group flex w-full items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-sm">
            <span x-text="selected ? selected[labelField] : '{{ $placeholder }}'"
                  class="truncate"
                  :class="{ 'text-gray-400': !selected }"></span>

            <svg class="size-5 shrink-0 text-gray-300 group-hover:text-gray-800"
                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                 fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                      d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                      clip-rule="evenodd"></path>
            </svg>
        </button>

        <ul x-listbox:options x-cloak
            class="absolute right-0 z-10 mt-2 max-h-80 w-full overflow-y-auto rounded-lg 
                   border border-gray-200 bg-white p-1.5 shadow-sm outline-none">

            <template x-for="item in items" :key="item[valueField]">
                <li x-listbox:option
                    :value="item"
                    :class="{
                        'bg-gray-100': $listboxOption.isActive,
                        'opacity-50 cursor-not-allowed': $listboxOption.isDisabled
                    }"
                    class="group flex w-full items-center rounded-md px-2 py-1.5 cursor-default transition-colors">

                    <div class="w-6 shrink-0">
                        <svg class="size-5 shrink-0"
                             x-show="$listboxOption.isSelected"
                             xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 20 20"
                             fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                  d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                  clip-rule="evenodd"></path>
                        </svg>
                    </div>

                    <span x-text="item[labelField]"></span>
                </li>
            </template>

        </ul>

    </div>
</div>


<!-- Alpine Component Script -->
<script>
    function listboxComponent({ items, valueField, labelField, disabledField, entangled }) {
        return {
            items,
            valueField,
            labelField,
            disabledField,
            selected: entangled,
            init() {
                // Sync selected object by id
                this.$watch('selected', (value) => {
                    if (value && typeof value !== 'object') {
                        this.selected = this.items.find(i => i[valueField] == value) || null;
                    }
                });
            }
        }
    }
</script>
