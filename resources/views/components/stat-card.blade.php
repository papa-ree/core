<div
    class="flex overflow-hidden text-base bg-white border border-gray-200 shadow-sm lw-tip dark:bg-gray-800 rounded-xl dark:border-gray-700">
    <div class="py-4">
        <div
            class="dark:bg-blue-300 bg-emerald-300 glow dark:shadow-blue-300 shadow-emerald-300 w-[3px] h-full rounded-r-lg">
        </div>
    </div>

    <div class="flex p-4 md:p-5 gap-x-4">
        <div
            class="shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
            {{ $icon }}

        </div>

        <div class="grow">
            <div class="flex items-center gap-x-2">
                {{ $title }}
            </div>
            <div class="flex items-center mt-1 gap-x-2">
                {{ $data }}
            </div>
        </div>
    </div>
</div>