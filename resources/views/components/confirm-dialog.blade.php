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
    $dialogId = 'confirm-dialog-'.\Illuminate\Support\Str::uuid();

    [$icon, $iconColor, $iconBox] = match ($variant) {
        'danger' => ['alert-triangle', 'text-red-600 dark:text-red-400', 'bg-red-100 dark:bg-red-900/30'],
        'success' => ['check-circle', 'text-emerald-600 dark:text-emerald-400', 'bg-emerald-100 dark:bg-emerald-900/30'],
        'secondary' => ['help-circle', 'text-gray-600 dark:text-gray-300', 'bg-gray-100 dark:bg-gray-700'],
        default => ['help-circle', 'text-purple-600 dark:text-purple-400', 'bg-purple-100 dark:bg-purple-900/30'],
    };
@endphp

{{--
    Slot `trigger` dirender di dalam root Alpine, jadi tombol pemicu cukup
    menulis `x-on:click="open = true"`.
--}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false">
    {{ $trigger ?? '' }}

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="{{ $dialogId }}-title">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" x-on:click="open = false"></div>

            <div x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-sm rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full {{ $iconBox }} {{ $iconColor }}">
                    <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
                </div>

                <h3 id="{{ $dialogId }}-title" class="text-base font-bold text-gray-900 dark:text-white">{{ __($title) }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ __($message) }}</p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-core::secondary-button type="button" x-on:click="open = false" :label="$cancelLabel" class="w-full justify-center sm:w-auto" />
                    @if ($action)
                        <x-core::button type="button" :variant="$variant" :label="$confirmLabel" class="w-full justify-center sm:w-auto"
                            x-on:click="open = false"
                            wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $action }}" />
                    @elseif ($event)
                        <x-core::button type="button" :variant="$variant" :label="$confirmLabel" class="w-full justify-center sm:w-auto"
                            x-on:click="open = false; $dispatch('{{ $event }}', {{ json_encode($params) }})" />
                    @else
                        <x-core::button type="button" :variant="$variant" :label="$confirmLabel" class="w-full justify-center sm:w-auto"
                            x-on:click="open = false" />
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
