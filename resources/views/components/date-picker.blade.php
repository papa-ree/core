{{--
|--------------------------------------------------------------------------
| Component: x-core::date-picker
|--------------------------------------------------------------------------
| A themed date / datetime picker backed by flatpickr (window.flatpickr).
|
| Props:
|   @prop string  $label       Optional inline label (renders x-core::label)
|   @prop string  $model       Livewire property to sync (also default `name`)
|   @prop string  $name        Form field name override (defaults to $model)
|   @prop string  $mode        'datetime' | 'date'  (default: datetime)
|   @prop string  $placeholder Placeholder text
|   @prop bool    $disabled    Disable the input
|   @prop bool    $live        Push changes to the server immediately
|   @prop string  $minDate     Minimum selectable date (flatpickr format)
|   @prop string  $maxDate     Maximum selectable date (flatpickr format)
|
| The owning Livewire component must expose `$model` as a public property, or
| render inside a Livewire component so `$wire` magic is available.
|
| 🔴 Factory Alpine-nya (`baleDatePicker`) hidup di `resources/js/app.js`,
| bukan di file ini. `<script>` yang dikirim bersama HTML Livewire tidak
| dijalankan saat komponen dirender ulang, jadi picker yang baru muncul akan
| gagal dengan "baleDatePicker is not defined".
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
        // Nilai yang disimpan ke Livewire/server (ISO-ish agar mudah di-parse Carbon).
        'format' => $isDateTime ? 'Y-m-d\\TH:i' : 'Y-m-d',
        // Format yang ditampilkan ke pengguna (dd/mm/yy, jam 24).
        'displayFormat' => $isDateTime ? 'd/m/y H:i' : 'd/m/y',
        'enableTime' => $isDateTime,
        'minDate' => $minDate,
        'maxDate' => $maxDate,
    ];
@endphp

<div
    class="w-full"
    wire:ignore
    x-data="baleDatePicker(@js($config))"
>
    @if ($label)
        <x-core::label :value="$label" />
    @endif

    <div class="relative">
        <input
            type="text"
            x-ref="input"
            @if ($name) name="{{ $name }}" @endif
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge([
                'class' => 'py-3 px-4 pe-11 block w-full
                            text-gray-900 placeholder-gray-500
                            transition-all duration-200
                            bg-white border border-gray-300 rounded-xl
                            dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
            ]) }}
        >

        <span class="absolute inset-y-0 end-0 flex items-center pointer-events-none pe-4 text-gray-400 dark:text-gray-500">
            <i data-lucide="{{ $isDateTime ? 'calendar-clock' : 'calendar' }}" class="h-4 w-4"></i>
        </span>
    </div>
</div>

