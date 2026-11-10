@props([
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin ingin melanjutkan?',
    'confirmLabel' => 'Ya, Lanjutkan',
    'cancelLabel' => 'Batal',
    'variant' => 'primary',
    'action' => null,
    'event' => null,
    'params' => [],
])

@php
    [$icon, $iconColor, $iconBox] = match ($variant) {
        'danger' => ['alert-triangle', 'text-red-600 dark:text-red-400', 'bg-red-100 dark:bg-red-900/30'],
        'success' => ['check-circle', 'text-emerald-600 dark:text-emerald-400', 'bg-emerald-100 dark:bg-emerald-900/30'],
        'secondary' => ['help-circle', 'text-gray-600 dark:text-gray-300', 'bg-gray-100 dark:bg-gray-700'],
        default => ['help-circle', 'text-purple-600 dark:text-purple-400', 'bg-purple-100 dark:bg-purple-900/30'],
    };

    $confirmClasses = match ($variant) {
        'danger' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
        default => 'bg-purple-600 hover:bg-purple-700 focus:ring-purple-500',
    };
@endphp

{{--
    Popover konfirmasi, pola sama dengan tombol hapus di
    `core-shared-components::item-actions`: Preline dropdown, bukan modal.
    Slot `trigger` bebas; kliknya diteruskan lewat wrapper toggle.
--}}
<div {{ $attributes->merge(['class' => 'hs-dropdown relative inline-flex [--placement:bottom-right]']) }}
    x-init="window.HSStaticMethods.autoInit(['dropdown'])">
    <div @class(['hs-dropdown-toggle inline-flex', 'w-full' => str_contains($attributes->get('class', ''), 'w-full')])>
        {{ $trigger ?? '' }}
    </div>

    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-xl rounded-xl p-4 mt-2 dark:bg-neutral-800 dark:border dark:border-neutral-700 z-70">
        <div class="text-center">
            <div class="size-10 {{ $iconBox }} {{ $iconColor }} rounded-full flex items-center justify-center mx-auto mb-3">
                <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
            </div>
            <h3 class="text-sm font-bold text-gray-800 dark:text-neutral-200">{{ __($title) }}</h3>
            <p class="text-xs text-gray-500 dark:text-neutral-500 mt-1">{{ __($message) }}</p>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            @if ($action)
                <button type="button" wire:click="{{ $action }}"
                    wire:loading.attr="disabled" wire:target="{{ $action }}"
                    class="hs-dropdown-close w-full py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent text-white disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 {{ $confirmClasses }} transition-all">
                    <svg wire:loading wire:target="{{ $action }}"
                        class="size-3.5 animate-spin text-white/80" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span wire:loading.remove wire:target="{{ $action }}">{{ __($confirmLabel) }}</span>
                    <span wire:loading wire:target="{{ $action }}">{{ __('Memproses...') }}</span>
                </button>
            @elseif ($event)
                <button type="button"
                    x-on:click="$dispatch('{{ $event }}', {{ json_encode($params) }})"
                    class="hs-dropdown-close w-full py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent text-white focus:outline-none focus:ring-2 {{ $confirmClasses }} transition-all">
                    {{ __($confirmLabel) }}
                </button>
            @else
                <button type="button" class="hs-dropdown-close w-full py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent text-white focus:outline-none focus:ring-2 {{ $confirmClasses }} transition-all">
                    {{ __($confirmLabel) }}
                </button>
            @endif

            <button type="button"
                class="hs-dropdown-close w-full py-2 px-3 inline-flex justify-center items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 transition-all">
                {{ __($cancelLabel) }}
            </button>
        </div>
    </div>
</div>
