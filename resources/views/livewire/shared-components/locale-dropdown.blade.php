<div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
    <button id="hs-dropdown-locale" type="button"
        class="inline-flex items-center justify-center gap-2 align-middle transition-all bg-white rounded-full shadow-sm w-9 h-9 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-white dark:bg-gray-800 dark:hover:bg-slate-800 dark:text-gray-400 dark:hover:text-white dark:focus:ring-gray-700 dark:focus:ring-offset-gray-800">

        @if($currentLocale === 'id')
            <img src="https://upload.wikimedia.org/wikipedia/commons/9/9f/Flag_of_Indonesia.svg" alt="ID"
                class="w-5 h-5 rounded-full object-cover">
        @elseif($currentLocale === 'en')
            <img src="https://upload.wikimedia.org/wikipedia/en/a/ae/Flag_of_the_United_Kingdom.svg" alt="EN"
                class="w-5 h-5 rounded-full object-cover">
        @else
            <span class="uppercase text-xs font-bold">{{ $currentLocale }}</span>
        @endif

    </button>

    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[10rem] bg-white shadow-md rounded-lg p-2 mt-2 dark:bg-gray-800 dark:border dark:border-gray-700"
        aria-labelledby="hs-dropdown-locale">

        <div class="py-2 first:pt-0 last:pb-0">
            @foreach($supportedLocales as $localeCode => $properties)
                <button type="button" wire:click="changeLocale('{{ $localeCode }}')"
                    class="flex items-center w-full gap-x-3.5 py-2 px-3 rounded-md text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">

                    @if($localeCode === 'id')
                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/9f/Flag_of_Indonesia.svg" alt="ID"
                            class="w-4 h-4 rounded-full object-cover">
                    @elseif($localeCode === 'en')
                        <img src="https://upload.wikimedia.org/wikipedia/en/a/ae/Flag_of_the_United_Kingdom.svg" alt="EN"
                            class="w-4 h-4 rounded-full object-cover">
                    @else
                        <span class="w-4 text-center uppercase text-xs font-bold">{{ $localeCode }}</span>
                    @endif

                    {{ $properties['native'] }}
                </button>
            @endforeach
        </div>

    </div>
</div>