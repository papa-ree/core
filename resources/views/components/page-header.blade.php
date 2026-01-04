@props(['title', 'subtitle' => null, 'gradient' => false])

@if($gradient)
    {{-- Gradient Header Style --}}
    <div class="relative overflow-hidden p-8 mb-8 text-white rounded-2xl shadow-xl"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-white md:text-4xl mb-2">{{ __($title) }}</h1>
                @if($subtitle)
                    <p class="text-white/90 text-lg">{{ __($subtitle) }}</p>
                @endif
            </div>
            @isset($action)
                <div class="shrink-0">{{ $action }}</div>
            @endisset
        </div>
    </div>
@else
    {{-- Standard Header Style --}}
    <div class="pb-5 mb-6 border-b border-gray-200 dark:border-gray-700 sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __($title) }}</h3>
            @if($subtitle)
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __($subtitle) }}</p>
            @endif
        </div>
        @isset($action)
            <div class="mt-3 flex sm:mt-0 sm:ml-4">{{ $action }}</div>
        @endisset
    </div>
@endif