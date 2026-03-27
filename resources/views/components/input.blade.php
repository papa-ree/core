{{--
|--------------------------------------------------------------------------
| Component: x-core::input
|--------------------------------------------------------------------------
| A flexible input component supporting multiple variants:
|
|   - Default          : Standard text/email/number input
|   - usePasswordField : Password input with show/hide toggle
|   - useInlineAddon   : Text input with a left-side inline prefix addon
|   - useRangeSlide    : Styled range slider input
|   - useGenPassword   : Password generator panel (accordion) below the input
|
| Props:
|   @prop bool   $disabled        Disable the input element
|   @prop bool   $autofocus       Auto-focus the input on render
|   @prop bool   $useGenPassword  Show the password generator accordion panel
|   @prop bool   $usePasswordField Show/hide-toggle password input
|   @prop bool   $useInlineAddon  Input with a left-side text addon (requires `addon` attribute)
|   @prop bool   $useRangeSlide   Render as a styled range slider
|
| Dependencies:
|   - Alpine.js (for reactive state)
|   - Preline UI (HSAccordion for generate password panel)
|   - zxcvbn.js (password strength scoring, loaded via @assets)
|   - Lucide icons (clipboard, check, eye, eye-off)
--}}

@props([
    'disabled'        => false,
    'autofocus'       => false,
    'useGenPassword'  => false,
    'usePasswordField' => false,
    'useInlineAddon'  => false,
    'useRangeSlide'   => false,
])

{{-- Load zxcvbn only once per page (used by the password strength meter) --}}
@assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
@endassets

{{-- Optional inline label (alternative to x-core::label used externally) --}}
@if ($attributes->has('label'))
    <x-core::label :value="$attributes['label']" />
@endif

{{-- ============================================================
     Variant: usePasswordField
     A password input with an eye icon to toggle visibility.
     Uses Alpine.js local state `{ hidePassword: true }`.
     wire:ignore prevents Livewire from re-rendering on update.
     ============================================================ --}}
@if ($usePasswordField)

    <div class="relative" x-data="{ hidePassword: true }" wire:ignore>
        <input
            :type="hidePassword ? 'password' : 'text'"
            {{ $disabled ? 'disabled' : '' }}
            {{ $autofocus ? 'autofocus' : '' }}
            name="password"
            id="password"
            autocomplete="off"
            {!! $attributes->merge([
                'class' => 'py-3 px-4 block w-full border-gray-200 rounded-md text-sm
                            focus:border-purple-300 focus:ring-purple-300
                            dark:bg-slate-900 dark:border-gray-700
                            text-gray-800 dark:text-neutral-200',
            ]) !!}
        >

        {{-- Toggle visibility button --}}
        <div
            class="absolute top-1/2 right-4 -translate-y-1/2 cursor-pointer"
            @click="hidePassword = !hidePassword"
        >
            <i data-lucide="eye"     class="h-6 text-gray-700"        :class="{ 'hidden': !hidePassword }"></i>
            <i data-lucide="eye-off" class="hidden h-6 text-gray-700" :class="{ 'hidden': hidePassword }"></i>
        </div>
    </div>

{{-- ============================================================
     Variant: useInlineAddon
     A text input with a left-side inline text prefix (addon).
     Pass the addon text via the `addon` attribute:
         <x-core::input useInlineAddon addon="https://" />
     ============================================================ --}}
@elseif ($useInlineAddon)

    <div class="space-y-3">
        <div>
            <label for="bale-inline-add-on" class="block mb-2 text-sm font-medium dark:text-white">
                Website URL
            </label>
            <div class="relative">
                <input
                    type="text"
                    id="bale-inline-add-on"
                    name="bale-inline-add-on"
                    placeholder="www.example.com"
                    {{ $autofocus ? 'autofocus' : '' }}
                    {!! $attributes->merge([
                        'class' => 'py-3 px-4 ps-16 block w-full
                                    text-gray-900 placeholder-gray-500
                                    transition-all duration-200
                                    bg-white border border-gray-300 form-input rounded-xl
                                    dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400
                                    focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
                    ]) !!}
                >
                {{-- Left-side addon text --}}
                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                    <span class="text-sm text-gray-500 dark:text-neutral-500">{{ $attributes['addon'] }}</span>
                </div>
            </div>
        </div>
    </div>

{{-- ============================================================
     Variant: useRangeSlide
     A styled HTML range input with Tailwind pseudo-element classes
     for cross-browser thumb and track styling.
     Pass `min`, `max`, `step`, `x-model`, etc. as attributes.
     ============================================================ --}}
@elseif ($useRangeSlide)

    @php
        $rangeClasses = '
            w-full bg-transparent cursor-pointer appearance-none
            disabled:opacity-50 disabled:pointer-events-none
            focus:outline-hidden

            [&::-webkit-slider-thumb]:w-2.5
            [&::-webkit-slider-thumb]:h-2.5
            [&::-webkit-slider-thumb]:-mt-0.5
            [&::-webkit-slider-thumb]:appearance-none
            [&::-webkit-slider-thumb]:bg-white
            [&::-webkit-slider-thumb]:shadow-[0_0_0_4px_rgba(37,99,235,1)]
            [&::-webkit-slider-thumb]:rounded-full
            [&::-webkit-slider-thumb]:transition-all
            [&::-webkit-slider-thumb]:duration-150
            [&::-webkit-slider-thumb]:ease-in-out
            dark:[&::-webkit-slider-thumb]:bg-neutral-700

            [&::-moz-range-thumb]:w-2.5
            [&::-moz-range-thumb]:h-2.5
            [&::-moz-range-thumb]:appearance-none
            [&::-moz-range-thumb]:bg-white
            [&::-moz-range-thumb]:border-4
            [&::-moz-range-thumb]:border-blue-600
            [&::-moz-range-thumb]:rounded-full
            [&::-moz-range-thumb]:transition-all
            [&::-moz-range-thumb]:duration-150
            [&::-moz-range-thumb]:ease-in-out

            [&::-webkit-slider-runnable-track]:w-full
            [&::-webkit-slider-runnable-track]:h-2
            [&::-webkit-slider-runnable-track]:bg-gray-100
            [&::-webkit-slider-runnable-track]:rounded-full
            dark:[&::-webkit-slider-runnable-track]:bg-neutral-700

            [&::-moz-range-track]:w-full
            [&::-moz-range-track]:h-2
            [&::-moz-range-track]:bg-gray-100
            [&::-moz-range-track]:rounded-full
        ';
    @endphp

    <input
        type="range"
        id="steps-range-bale-grid"
        aria-orientation="horizontal"
        class="{{ $rangeClasses }}"
        {!! $attributes->merge([]) !!}
    >

{{-- ============================================================
     Variant: Default (Standard Input)
     A regular text/email/number/etc. input with Bale theme styles.
     The `type` defaults to `text` but can be overridden via attributes.
     ============================================================ --}}
@else

    <input
        {{ $disabled ? 'disabled' : '' }}
        {{ $autofocus ? 'autofocus' : '' }}
        {!! $attributes->merge([
            'type'  => 'text',
            'class' => 'block w-full py-3 px-4
                        text-gray-900 placeholder-gray-500
                        transition-all duration-200
                        bg-white border border-gray-300 form-input rounded-xl
                        dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400
                        focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
        ]) !!}
    >

@endif

{{-- ============================================================
     Add-on: useGenPassword
     Renders a collapsible accordion panel below the input field
     that generates a random password based on user settings.
     Can be combined with the default input variant.
     Uses Alpine.js `app()` for reactive state.
     Uses Preline's HSAccordion to show/hide the panel.
     ============================================================ --}}
@if ($useGenPassword)

    {{-- Accordion wrapper (wire:ignore prevents Livewire re-renders disrupting Alpine state) --}}
    <div class="mt-2 hs-accordion-group" wire:ignore>
        <div class="hs-accordion" id="bale-generate-password-form">

            {{-- Toggle button: Bale-themed pill with icon and gradient hover --}}
            <div class="flex justify-end mt-2">
                <button
                    type="button"
                    aria-expanded="true"
                    aria-controls="bale-generate-password-button"
                    class="hs-accordion-toggle
                           inline-flex items-center gap-2 px-3.5 py-1.5
                           text-xs font-semibold text-purple-600 dark:text-purple-400
                           bg-purple-50 dark:bg-purple-900/20
                           border border-purple-200 dark:border-purple-800
                           rounded-full transition-all duration-200
                           hover:bg-purple-100 dark:hover:bg-purple-900/40
                           hover:border-purple-300 dark:hover:border-purple-700
                           focus:outline-none focus:ring-2 focus:ring-purple-400/40
                           disabled:opacity-50 disabled:pointer-events-none"
                >
                    <x-lucide-wand-sparkles class="w-3.5 h-3.5"/>
                    {{ __('Generate password') }}
                </button>
            </div>

            {{-- Collapsible generator panel --}}
            <div
                id="bale-generate-password-button"
                class="hs-accordion-content w-full hidden overflow-hidden transition-[height] duration-300"
                role="region"
                aria-labelledby="bale-generate-password-form"
            >
                {{-- Alpine.js scope: initialises and auto-generates a password on mount --}}
                <div
                    class="mt-3 w-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700
                           rounded-2xl shadow-md overflow-hidden"
                    x-data="balePasswordGenerator()"
                    x-init="generatePassword()"
                >

                    {{-- Panel header with gradient banner --}}
                    <div class="flex items-center gap-3 px-5 py-4
                                bg-linear-to-r from-indigo-50/70 to-purple-50/70
                                dark:from-indigo-900/10 dark:to-purple-900/10
                                border-b border-gray-100 dark:border-gray-700">
                        <div class="p-2 bg-linear-to-br from-indigo-500 to-purple-600 rounded-xl shadow-md">
                            <x-lucide-shield-check class="w-4 h-4 text-white"/>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Password Generator') }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Generate a strong, random password') }}</p>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">

                        {{-- Generated password preview with copy button --}}
                        <div>
                            <label class="block mb-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                {{ __('Generated Password') }}
                            </label>
                            <div class="hs-tooltip">
                                <div
                                    class="flex items-center gap-2 p-3 rounded-xl cursor-pointer group
                                           bg-gray-50 dark:bg-gray-900/50
                                           border border-gray-200 dark:border-gray-700
                                           hover:border-purple-300 dark:hover:border-purple-700
                                           hover:bg-purple-50/30 dark:hover:bg-purple-900/10
                                           transition-all duration-200 hs-tooltip-toggle"
                                    @click="
                                        $clipboard(generatedPassword);
                                        tooltipText = 'Copied!';
                                        setTimeout(() => { tooltipText = 'Copy'; showCopyIcon = true; }, 2000);
                                        showCopyIcon = !showCopyIcon
                                    "
                                >
                                    {{-- Password text --}}
                                    <span
                                        class="flex-1 font-mono text-sm font-semibold tracking-widest text-gray-800 dark:text-gray-200 truncate select-none"
                                        x-text="generatedPassword"
                                    ></span>

                                    {{-- Copy / Check icon --}}
                                    <span class="shrink-0 flex items-center justify-center w-7 h-7
                                                 bg-white dark:bg-gray-800 rounded-lg shadow-sm
                                                 border border-gray-200 dark:border-gray-600
                                                 group-hover:border-purple-300 dark:group-hover:border-purple-700
                                                 transition-all duration-200">
                                        <x-lucide-clipboard class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400 group-hover:text-purple-500 transition-colors" x-show="showCopyIcon"/>
                                        <x-lucide-check class="w-3.5 h-3.5 text-emerald-500" x-show="!showCopyIcon"/>
                                    </span>
                                </div>
                                {{-- Tooltip --}}
                                <span
                                    class="absolute z-10 invisible inline-block px-2 py-1 text-xs font-medium text-white
                                           bg-gray-900 dark:bg-neutral-700 rounded-lg opacity-0 shadow-sm
                                           hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible
                                           transition-opacity duration-200"
                                    role="tooltip"
                                    x-text="tooltipText"
                                ></span>
                            </div>
                        </div>

                        {{-- Password strength bar with label --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    {{ __('Strength') }}
                                </span>
                                <span
                                    class="text-xs font-semibold"
                                    :class="{
                                        'text-gray-400':   passwordScore === 0,
                                        'text-red-500':    passwordScore <= 2 && passwordScore > 0,
                                        'text-yellow-500': passwordScore <= 4 && passwordScore > 2,
                                        'text-emerald-500': passwordScore === 5,
                                    }"
                                    x-text="['—', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'][passwordScore]"
                                ></span>
                            </div>
                            <div class="flex gap-1">
                                <template x-for="(v, i) in 5" :key="i">
                                    <div class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                        :class="i < passwordScore
                                            ? (passwordScore <= 2 ? 'bg-red-400' : (passwordScore <= 4 ? 'bg-yellow-400' : 'bg-emerald-500'))
                                            : 'bg-gray-200 dark:bg-gray-700'"
                                    ></div>
                                </template>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700"></div>

                        {{-- Password length control --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    {{ __('Length') }}
                                </label>
                                <span class="inline-flex items-center justify-center w-8 h-6 text-xs font-bold
                                             text-purple-700 dark:text-purple-300
                                             bg-purple-100 dark:bg-purple-900/30
                                             rounded-md"
                                    x-text="charsLength"
                                ></span>
                            </div>

                            @php
                                $rangeSliderClasses = '
                                    w-full bg-transparent cursor-pointer appearance-none
                                    disabled:opacity-50 disabled:pointer-events-none
                                    focus:outline-hidden

                                    [&::-webkit-slider-thumb]:w-3.5
                                    [&::-webkit-slider-thumb]:h-3.5
                                    [&::-webkit-slider-thumb]:-mt-1
                                    [&::-webkit-slider-thumb]:appearance-none
                                    [&::-webkit-slider-thumb]:bg-white
                                    [&::-webkit-slider-thumb]:shadow-[0_0_0_3px_rgba(124,58,237,1)]
                                    [&::-webkit-slider-thumb]:rounded-full
                                    [&::-webkit-slider-thumb]:transition-all
                                    [&::-webkit-slider-thumb]:duration-150
                                    [&::-webkit-slider-thumb]:ease-in-out
                                    dark:[&::-webkit-slider-thumb]:bg-neutral-700

                                    [&::-moz-range-thumb]:w-3.5
                                    [&::-moz-range-thumb]:h-3.5
                                    [&::-moz-range-thumb]:appearance-none
                                    [&::-moz-range-thumb]:bg-white
                                    [&::-moz-range-thumb]:border-[3px]
                                    [&::-moz-range-thumb]:border-purple-600
                                    [&::-moz-range-thumb]:rounded-full
                                    [&::-moz-range-thumb]:transition-all
                                    [&::-moz-range-thumb]:duration-150
                                    [&::-moz-range-thumb]:ease-in-out

                                    [&::-webkit-slider-runnable-track]:w-full
                                    [&::-webkit-slider-runnable-track]:h-1.5
                                    [&::-webkit-slider-runnable-track]:bg-purple-100
                                    [&::-webkit-slider-runnable-track]:rounded-full
                                    dark:[&::-webkit-slider-runnable-track]:bg-gray-700

                                    [&::-moz-range-track]:w-full
                                    [&::-moz-range-track]:h-1.5
                                    [&::-moz-range-track]:bg-purple-100
                                    [&::-moz-range-track]:rounded-full
                                    dark:[&::-moz-range-track]:bg-gray-700
                                ';
                            @endphp

                            <input
                                type="range"
                                id="steps-range-slider-usage"
                                aria-orientation="horizontal"
                                min="6" max="32" step="1"
                                x-model="charsLength"
                                @input="generatePassword()"
                                class="{{ $rangeSliderClasses }}"
                            >
                            <div class="flex justify-between mt-1 text-[10px] text-gray-400 dark:text-gray-600">
                                <span>6</span>
                                <span>32</span>
                            </div>
                        </div>

                        {{-- Character type toggles --}}
                        <div>
                            <label class="block mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                {{ __('Character Types') }}
                            </label>

                            @php
                                $charOptions = [
                                    ['id' => 'charsLower',   'model' => 'charsLower',   'label' => 'abc', 'desc' => 'Lowercase'],
                                    ['id' => 'charsUpper',   'model' => 'charsUpper',   'label' => 'ABC', 'desc' => 'Uppercase'],
                                    ['id' => 'charsNumeric', 'model' => 'charsNumeric', 'label' => '123', 'desc' => 'Numbers'],
                                    ['id' => 'charsSymbols', 'model' => 'charsSymbols', 'label' => '#@!', 'desc' => 'Symbols'],
                                ];
                            @endphp

                            <div class="grid grid-cols-2 gap-2 select-none">
                                @foreach ($charOptions as $option)
                                    <label
                                        for="{{ $option['id'] }}"
                                        class="group relative flex items-center gap-3 p-3 cursor-pointer
                                               rounded-xl border transition-all duration-200
                                               bg-white dark:bg-gray-900/50
                                               border-gray-200 dark:border-gray-700
                                               has-checked:border-purple-400 dark:has-checked:border-purple-600
                                               has-checked:bg-purple-50/60 dark:has-checked:bg-purple-900/20
                                               hover:border-gray-300 dark:hover:border-gray-600"
                                    >
                                        <input
                                            type="checkbox"
                                            id="{{ $option['id'] }}"
                                            x-ref="{{ $option['model'] }}"
                                            x-model="{{ $option['model'] }}"
                                            @input="generatePassword()"
                                            class="peer sr-only"
                                            checked
                                        >
                                        {{-- Custom checkbox indicator --}}
                                        <span class="shrink-0 flex items-center justify-center w-4.5 h-4.5 rounded-md
                                                     border-2 transition-all duration-200
                                                     border-gray-300 dark:border-gray-600
                                                     peer-checked:border-purple-500 peer-checked:bg-purple-500
                                                     dark:peer-checked:border-purple-500 dark:peer-checked:bg-purple-500">
                                            <i data-lucide="check" class="w-2.5 h-2.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                        </span>
                                        <div>
                                            <span class="block text-xs font-bold font-mono text-gray-700 dark:text-gray-300 leading-none">
                                                {{ $option['label'] }}
                                            </span>
                                            <span class="block text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">
                                                {{ __($option['desc']) }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">

                            {{-- Cancel --}}
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold
                                       text-gray-600 dark:text-gray-400
                                       bg-gray-100 dark:bg-gray-700/60
                                       border border-gray-200 dark:border-gray-700
                                       rounded-lg transition-all duration-200
                                       hover:bg-gray-200 dark:hover:bg-gray-700
                                       focus:outline-none focus:ring-2 focus:ring-gray-300/50
                                       disabled:opacity-50 disabled:pointer-events-none"
                                @click="HSAccordion.hide('#bale-generate-password-form')"
                            >
                                {{ __('Cancel') }}
                            </button>

                            {{-- Regenerate --}}
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold
                                       text-indigo-600 dark:text-indigo-400
                                       bg-indigo-50 dark:bg-indigo-900/20
                                       border border-indigo-200 dark:border-indigo-800
                                       rounded-lg transition-all duration-200
                                       hover:bg-indigo-100 dark:hover:bg-indigo-900/40
                                       focus:outline-none focus:ring-2 focus:ring-indigo-400/40
                                       disabled:opacity-50 disabled:pointer-events-none"
                                @click="generatePassword()"
                            >
                                <x-lucide-refresh-cw class="w-3.5 h-3.5"/>
                                {{ __('Regenerate') }}
                            </button>

                            {{-- Use Password (primary action) --}}
                            <button
                                type="button"
                                id="bale-generate-password-used"
                                class="hs-accordion-toggle
                                       inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold
                                       text-white
                                       bg-linear-to-r from-indigo-500 to-purple-600
                                       rounded-lg shadow-md shadow-purple-500/20
                                       transition-all duration-200
                                       hover:from-indigo-600 hover:to-purple-700
                                       hover:shadow-lg hover:shadow-purple-500/30
                                       focus:outline-none focus:ring-2 focus:ring-purple-400/50
                                       disabled:opacity-50 disabled:pointer-events-none"
                                @click="
                                    $wire.$set(@js($attributes->whereStartsWith('wire:model')->first()), generatedPassword);
                                    HSAccordion.hide('#bale-generate-password-form')
                                "
                            >
                                <x-lucide-check class="w-3.5 h-3.5"/>
                                {{ __('Use Password') }}
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Alpine.js: balePasswordGenerator()
         Manages the reactive state for the password generator UI.
         - Uses zxcvbn for password strength scoring (score 0–4 → +1 → 1–5)
         - Reads checkbox states via getElementById to match Alpine x-model
           (ensures consistency even if Alpine reactivity is delayed)
         - shuffleArray: Fisher-Yates shuffle for randomness
         ============================================================ --}}
    <script>
        function balePasswordGenerator() {
            return {
                // State
                passwordScore:     0,
                generatedPassword: '',
                charsLength:       12,
                charsLower:        true,
                charsUpper:        true,
                charsNumeric:      true,
                charsSymbols:      true,
                tooltipText:       'Copy',
                showCopyIcon:      true,

                // Character sets used for generation
                chars: {
                    lower:   'abcdefghijklmnopqrstuvwxyz',
                    upper:   'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    numeric: '0123456789',
                    symbols: '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~',
                },

                /**
                 * Evaluate the generated password's strength using zxcvbn.
                 * zxcvbn returns a score of 0–4; we add 1 to use a 1–5 scale
                 * that maps to 5 strength bar segments.
                 */
                checkStrength() {
                    if (!this.generatedPassword) {
                        this.passwordScore = 0;
                        return;
                    }
                    this.passwordScore = zxcvbn(this.generatedPassword).score + 1;
                },

                /**
                 * Build a character pool from the selected character types,
                 * shuffle it, then take the first `charsLength` characters.
                 * Uses getElementById to read live checkbox states.
                 */
                generatePassword() {
                    const pool = [
                        document.getElementById('charsLower').checked   ? this.chars.lower   : '',
                        document.getElementById('charsUpper').checked   ? this.chars.upper   : '',
                        document.getElementById('charsNumeric').checked ? this.chars.numeric : '',
                        document.getElementById('charsSymbols').checked ? this.chars.symbols : '',
                    ].join('').split('');

                    this.generatedPassword = this.shuffleArray(pool)
                        .join('')
                        .substring(0, this.charsLength);

                    this.checkStrength();
                },

                /**
                 * In-place Fisher-Yates shuffle for unbiased randomisation.
                 * @param {string[]} array
                 * @returns {string[]}
                 */
                shuffleArray(array) {
                    for (let i = array.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [array[i], array[j]] = [array[j], array[i]];
                    }
                    return array;
                },
            };
        }
    </script>

@endif