{{--
|--------------------------------------------------------------------------
| Component: x-core::date-picker
|--------------------------------------------------------------------------
| Date / datetime picker bertema Bale, tanpa library: input baca-saja plus
| kalender Alpine (`window.baleDatePicker`).
|
| Props:
|   @prop string  $label       Optional inline label (renders x-core::label)
|   @prop string  $model       Livewire property to sync (also default `name`)
|   @prop string  $name        Form field name override (defaults to $model)
|   @prop string  $mode        'datetime' | 'date'  (default: datetime)
|   @prop string  $placeholder Placeholder text
|   @prop bool    $disabled    Disable the input
|   @prop bool    $live        Push changes to the server immediately
|   @prop string  $minDate     Minimum selectable date (Y-m-d)
|   @prop string  $maxDate     Maximum selectable date (Y-m-d)
|
| Nilai yang disimpan tetap `Y-m-d` (mode date) atau `Y-m-d\TH:i` (mode datetime).
|
| ?? Factory Alpine-nya (`window.baleDatePicker`) hidup di
| `resources/js/date-picker.js` (diekspor lewat `resources/js/app.js`), bukan di
| file ini. `<script>` yang dikirim bersama HTML Livewire tidak dijalankan saat
| komponen dirender ulang.
--}}
@props([
    'label' => null,
    'model' => null,
    'name' => null,
    'mode' => 'datetime',
    'placeholder' => null,
    'disabled' => false,
    'live' => false,
    'minDate' => null,
    'maxDate' => null,
])
@php
    $name = $name ?? $model;
    $isDateTime = $mode === 'datetime';
    $placeholder = $placeholder ?? ($isDateTime ? __('Pilih tanggal & waktu') : __('Pilih tanggal'));

    $config = [
        'model' => $model,
        'live' => (bool) $live,
        'enableTime' => $isDateTime,
        'minDate' => $minDate,
        'maxDate' => $maxDate,
    ];
@endphp

<div
    class="w-full"
    wire:ignore
    x-data="baleDatePicker(@js($config))"
    x-on:keydown.escape="close()"
>
    @if ($label)
        <x-core::label :value="$label" />
    @endif

    <div class="relative">
        <input
            x-ref="input"
            type="text"
            readonly
            autocomplete="off"
            @if ($name) name="{{ $name }}" @endif
            placeholder="{{ $placeholder }}"
            aria-haspopup="dialog"
            :aria-expanded="open ? 'true' : 'false'"
            x-on:click="toggle()"
            x-on:keydown.enter.prevent="toggle()"
            x-on:keydown.space.prevent="toggle()"
            x-on:keydown.arrow-down.prevent="show()"
            :value="display"
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge([
                'class' => 'py-3 px-4 pe-11 block w-full cursor-pointer
                            text-gray-900 placeholder-gray-500
                            transition-all duration-200
                            bg-white border border-gray-300 rounded-xl
                            dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                            disabled:cursor-not-allowed disabled:opacity-60',
            ]) }}
        >

        <span class="absolute inset-y-0 end-0 flex items-center pe-2">
            <button
                type="button"
                tabindex="-1"
                x-on:click="toggle()"
                aria-label="{{ __('Buka kalender') }}"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600
                       dark:text-gray-500 dark:hover:bg-gray-600 dark:hover:text-gray-200"
            >
                <i data-lucide="{{ $isDateTime ? 'calendar-clock' : 'calendar' }}" class="h-4 w-4"></i>
            </button>
        </span>

        <div
            x-show="open"
            x-cloak
            x-on:click.outside="close()"
            role="dialog"
            aria-label="{{ __('Pilih tanggal') }}"
            class="absolute start-0 z-50 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-3 shadow-xl
                   dark:border-gray-600 dark:bg-gray-800"
        >
            {{-- Navigasi bulan --}}
            <div class="flex items-center justify-between gap-2">
                <button
                    type="button"
                    x-on:click="moveMonth(-1)"
                    :disabled="!canGoBack"
                    aria-label="{{ __('Bulan sebelumnya') }}"
                    class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700
                           disabled:cursor-not-allowed disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                >
                    <x-lucide-chevron-left class="size-4" />
                </button>

                <div
                    class="text-sm font-semibold capitalize text-gray-900 dark:text-white"
                    x-text="monthLabel"
                    aria-live="polite"
                ></div>

                <button
                    type="button"
                    x-on:click="moveMonth(1)"
                    :disabled="!canGoForward"
                    aria-label="{{ __('Bulan berikutnya') }}"
                    class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700
                           disabled:cursor-not-allowed disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                >
                    <x-lucide-chevron-right class="size-4" />
                </button>
            </div>

            {{-- Nama hari, Senin dulu --}}
            <div class="mt-2 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                <template x-for="day in weekdays" :key="day">
                    <span x-text="day"></span>
                </template>
            </div>

            {{-- Grid tanggal --}}
            <div
                x-ref="grid"
                class="mt-1 grid grid-cols-7 gap-1"
                x-on:keydown.arrow-left.prevent="moveDays(-1)"
                x-on:keydown.arrow-right.prevent="moveDays(1)"
                x-on:keydown.arrow-up.prevent="moveDays(-7)"
                x-on:keydown.arrow-down.prevent="moveDays(7)"
                x-on:keydown.enter.prevent="pickActive()"
            >
                <template x-for="cell in days" :key="cell.key">
                    <button
                        type="button"
                        x-on:click="pick(cell)"
                        x-on:mouseenter="active = cell.date"
                        :disabled="cell.disabled"
                        :tabindex="cell.active ? 0 : -1"
                        :data-active="cell.active ? 'true' : 'false'"
                        :aria-pressed="cell.selected ? 'true' : 'false'"
                        :aria-label="cell.label"
                        :class="{
                            'bg-purple-600 text-white hover:bg-purple-600': cell.selected,
                            'ring-1 ring-inset ring-purple-400 dark:ring-purple-500': cell.today && !cell.selected,
                            'bg-gray-100 dark:bg-gray-700': cell.active && !cell.selected,
                            'text-gray-300 dark:text-gray-600': !cell.inMonth,
                            'cursor-not-allowed opacity-40': cell.disabled,
                            'hover:bg-gray-100 dark:hover:bg-gray-700': !cell.disabled && !cell.selected,
                        }"
                        class="flex h-9 items-center justify-center rounded-lg text-sm font-medium
                               text-gray-700 transition-colors dark:text-gray-200"
                    >
                        <span x-text="cell.day"></span>
                    </button>
                </template>
            </div>

            {{-- Footer --}}
            <div class="mt-3 flex items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                <button
                    type="button"
                    x-on:click="pickToday()"
                    class="rounded-lg px-2 py-1 text-xs font-semibold text-purple-700 transition-colors
                           hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-900/30"
                >
                    {{ __('Hari ini') }}
                </button>

                @if ($isDateTime)
                    <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ __('Jam') }}</span>
                        <input
                            type="time"
                            x-model="time"
                            x-on:change="commit()"
                            class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900
                                   dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                   focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        >
                    </label>
                @endif
            </div>
        </div>
    </div>
</div>
