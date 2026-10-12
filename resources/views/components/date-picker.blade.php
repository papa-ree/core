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
|
| The owning Livewire component must expose `$model` as a public property, or
| render inside a Livewire component so `$wire` magic is available.
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

@once
    <script>
        function baleDatePicker(config) {
            return {
                instance: null,

                init() {
                    const initial = config.model ? this.$wire.get(config.model) : this.$refs.input.value;

                    this.instance = window.flatpickr(this.$refs.input, {
                        enableTime: !!config.enableTime,
                        time_24hr: true,
                        dateFormat: config.format,
                        altInput: true,
                        altFormat: config.displayFormat,
                        allowInput: false,
                        minDate: config.minDate || null,
                        defaultDate: initial || null,
                        onChange: (dates, str) => {
                            if (!config.model) return;

                            if (config.live) {
                                this.$wire.set(config.model, str);
                            } else {
                                this.$wire.set(config.model, str, false);
                            }
                        },
                    });

                    if (config.model) {
                        this.$wire.$watch(config.model, (value) => {
                            const next = value || null;

                            if ((value || '') === (this.$refs.input.value || '')) return;

                            this.instance.setDate(next, false);
                        });
                    }
                },
            };
        }
    </script>
@endonce
