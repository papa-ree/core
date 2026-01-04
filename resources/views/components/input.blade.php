@props(['disabled' => false, 'autofocus' => false, 'useGenPassword' => false, 'usePasswordField' => false, 'useInlineAddon' => false, 'useRangeSlide' => false])

@assets
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
@endassets

@if ($attributes->has('label'))
    <x-core::label :value="$attributes['label']" />
@endif

@if ($usePasswordField)
    <div class="relative" x-data="{ hidePassword: true }" wire:ignore>
        <input :type="hidePassword ? 'password' : 'text'" {{ $disabled ? 'disabled' : '' }} {{ $autofocus ? 'autofocus' : '' }} name="password" id="password" autocomplete="off" {!! $attributes->merge([
            'class' =>
                'py-3 px-4 block w-full border-gray-200 rounded-md text-sm focus:border-purple-300 focus:ring-purple-300 dark:bg-slate-900 dark:border-gray-700 text-gray-800 dark:text-neutral-200',
        ]) !!}>

        <div class="absolute transform -translate-y-1/2 cursor-pointer top-1/2 right-4"
            @click="hidePassword = !hidePassword">
            <i data-lucide="eye" class="h-6 text-gray-700" :class="{ 'hidden': !hidePassword }"></i>
            <i data-lucide="eye-off" class="hidden h-6 text-gray-700" :class="{ 'hidden': hidePassword }"></i>
        </div>
    </div>
@elseif ($useInlineAddon)
    <div class="space-y-3">
        <div>
            <label for="bale-inline-add-on" class="block mb-2 text-sm font-medium dark:text-white">Website
                URL</label>
            <div class="relative">
                <input type="text" id="bale-inline-add-on" name="bale-inline-add-on" {!! $attributes->merge([]) !!} {{ $autofocus ? 'autofocus' : '' }}
                    class="py-3 px-4 ps-16 block w-full text-gray-900 placeholder-gray-500 transition-all duration-200 bg-white border border-gray-300 form-input dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="www.example.com">
                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                    <span class="text-sm text-gray-500 dark:text-neutral-500">{{ $attributes['addon'] }}</span>
                </div>
            </div>
        </div>
    </div>
@elseif ($useRangeSlide)
    <input type="range" class="w-full bg-transparent cursor-pointer appearance-none disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden
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
                                              [&::-moz-range-track]:rounded-full" id="steps-range-bale-grid"
        aria-orientation="horizontal" {!! $attributes->merge([])!!}>
@else
    <input {{ $disabled ? 'disabled' : '' }} {{ $autofocus ? 'autofocus' : '' }} {!! $attributes->merge([
            'type' => 'text',
            'class' =>
                'block w-full py-3 px-4 text-gray-900 placeholder-gray-500 transition-all duration-200 bg-white border border-gray-300 form-input dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent',
        ]) !!}>
@endif

@if ($useGenPassword)
    <div class="mt-3 hs-accordion-group" wire:ignore>
        <div class="space-y-3 hs-accordion" id="bale-generate-password-form">

            <div class="flex justify-end">
                <button type="button" aria-expanded="true" aria-controls="bale-generate-password-button"
                    class="inline-flex items-center p-3 text-sm font-medium text-gray-700 transition bg-gray-100 border border-transparent rounded-lg hs-accordion-toggle hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-white/10 dark:text-neutral-400 dark:hover:bg-white/20 dark:hover:text-neutral-300 dark:focus:bg-white/20 dark:focus:text-neutral-300">
                    Generate password
                </button>
            </div>

            <div id="bale-generate-password-button"
                class="hs-accordion-content w-full hidden overflow-hidden transition-[height] duration-300" role="region"
                aria-labelledby="bale-generate-password-form">

                <div class="w-full p-5 mx-auto text-gray-800 border rounded-lg" x-data="app()" x-init="generatePassword()">

                    {{-- Password Input Field --}}
                    <div class="relative mb-2">
                        <div class="hs-tooltip">
                            <div class="rounded-lg cursor-pointer sm:flex hs-tooltip-toggle group"
                                @click="$clipboard(generatedPassword); tooltipText = 'Copied'; setTimeout(() => { tooltipText = 'Copy'; showCopyIcon = true; }, 2000); showCopyIcon = !showCopyIcon">
                                <input type="text" x-model="generatedPassword" disabled
                                    class="py-2.5 sm:py-3 px-4 pe-11 block w-full border-gray-200 group-hover:bg-gray-100 transition duration-300 -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-purple-500 focus:ring-purple-500 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600">
                                <span
                                    class="py-2.5 sm:py-3 px-4 inline-flex items-center min-w-fit w-full border border-gray-200 bg-gray-50 sm:text-sm text-gray-500 -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:w-auto sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg dark:bg-neutral-700 dark:border-neutral-700 dark:text-neutral-400">
                                    <i data-lucide="clipboard"
                                        class="h-5 text-gray-700 transition-all duration-300 group-hover:rotate-12"
                                        :class="{ 'hidden': !showCopyIcon }"></i>
                                    <i data-lucide="check"
                                        class="hidden h-5 transition-all duration-300 text-purple-500 group-hover:rotate-12"
                                        :class="{ 'hidden': showCopyIcon }"></i>
                                </span>
                                <span
                                    class="absolute z-10 invisible inline-block px-2 py-1 text-xs font-medium text-white transition-opacity bg-gray-900 rounded-md opacity-0 hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible shadow-2xs dark:bg-neutral-700"
                                    role="tooltip" x-text="tooltipText">

                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Password Strength Indicator --}}
                    <div class="flex -mx-1">
                        <template x-for="(v, i) in 5" :key="i">
                            <div class="w-1/5 px-1">
                                <div class="h-2 transition-colors rounded-xl"
                                    :class="i < passwordScore ? (passwordScore <= 2 ? 'bg-red-400' : (
                                                                                                                                                                                passwordScore <= 4 ? 'bg-yellow-400' : 'bg-green-500')) :
                                                                                                                                                                            'bg-gray-200'">
                                </div>
                            </div>
                        </template>
                    </div>

                    <hr class="my-5 border border-gray-200">

                    {{-- Password Length Control --}}
                    <div class="mb-2">
                        <input label="Password Length" type="number" min="1" max="18" step="1" x-model="charsLength"
                            @input="generatePassword()" />
                        <input type="range"
                            class="w-full bg-transparent cursor-pointer appearance-none disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:border-purple-500 focus:ring-purple-500
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
                                                                                                                                                                [&::-moz-range-thumb]:border-purple-400
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
                                                                                                                                                                [&::-moz-range-track]:rounded-full"
                            id="steps-range-slider-usage" aria-orientation="horizontal" min="1" max="18" step="1"
                            x-model="charsLength" @input="generatePassword()">
                    </div>

                    {{-- Character Type Options --}}
                    <div class="grid gap-2 mt-4 select-none sm:grid-cols-2">
                        <label for="charsLower"
                            class="flex w-full p-3 text-sm bg-white border border-gray-200 rounded-lg focus:border-purple-500 focus:ring-purple-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400">
                            <input type="checkbox" id="charsLower" x-ref="charsLower" x-model="charsLower"
                                @input="generatePassword()"
                                class="w-5 h-5 transition duration-200 text-purple-400 form-checkbox rounded-xl focus:border-purple-500 focus:ring-purple-500"
                                id="charsLower" checked>
                            <span
                                class="text-sm text-gray-500 ms-3 dark:text-neutral-400">{{ __('abcdefghijklmnopqrstuvwxyz') }}</span>
                        </label>

                        <label for="charsUpper"
                            class="flex w-full p-3 text-sm bg-white border border-gray-200 rounded-lg focus:border-purple-400 focus:ring-purple-400 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400">
                            <input type="checkbox" id="charsUpper" x-ref="charsUpper" x-model="charsUpper"
                                @input="generatePassword()"
                                class="w-5 h-5 transition duration-200 text-purple-400 form-checkbox rounded-xl focus:border-purple-500 focus:ring-purple-500"
                                id="charsUpper" checked>
                            <span
                                class="text-sm text-gray-500 ms-3 dark:text-neutral-400">{{ __('ABCDEFGHIJKLMNOPQRSTUVWXYZ') }}</span>
                        </label>

                        <label for="charsNumeric"
                            class="flex w-full p-3 text-sm bg-white border border-gray-200 rounded-lg focus:border-purple-500 focus:ring-purple-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400">
                            <input type="checkbox" id="charsNumeric" x-ref="charsNumeric" x-model="charsNumeric"
                                @input="generatePassword()"
                                class="w-5 h-5 transition duration-200 text-purple-400 form-checkbox rounded-xl focus:border-purple-500 focus:ring-purple-500"
                                id="charsNumeric" checked>
                            <span class="text-sm text-gray-500 ms-3 dark:text-neutral-400">{{ __('123456789') }}</span>
                        </label>

                        <label for="charsSymbols"
                            class="flex w-full p-3 text-sm bg-white border border-gray-200 rounded-lg focus:border-purple-500 focus:ring-purple-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400">
                            <input type="checkbox" id="charsSymbols" x-ref="charsSymbols" x-model="charsSymbols"
                                @input="generatePassword()"
                                class="w-5 h-5 transition duration-200 text-purple-400 form-checkbox rounded-xl focus:border-purple-500 focus:ring-purple-500"
                                id="charsSymbols" checked>
                            <span
                                class="text-sm text-gray-500 ms-3 dark:text-neutral-400">{{ __('~!@#$%^&*_+-=:;?,.') }}</span>
                        </label>

                    </div>

                    {{-- Generate Password and Use Password Button --}}
                    <div class="flex items-center justify-end gap-2 mt-3">
                        <button type="button"
                            class="inline-flex items-center p-3 text-sm font-medium text-gray-700 transition bg-gray-100 border border-transparent rounded-lg hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-white/10 dark:text-neutral-400 dark:hover:bg-white/20 dark:hover:text-neutral-300 dark:focus:bg-white/20 dark:focus:text-neutral-300"
                            @click="HSAccordion.hide('#bale-generate-password-form')">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button"
                            class="inline-flex items-center p-3 text-sm font-medium text-gray-700 transition bg-gray-100 border border-transparent rounded-lg hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-white/10 dark:text-neutral-400 dark:hover:bg-white/20 dark:hover:text-neutral-300 dark:focus:bg-white/20 dark:focus:text-neutral-300"
                            @click="generatePassword()">
                            {{ __('Generate') }}
                        </button>
                        <button type="button" id="bale-generate-password-used"
                            class="inline-flex items-center p-3 text-sm font-medium text-gray-700 transition bg-gray-100 border border-transparent rounded-lg hs-accordion-toggle hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-white/10 dark:text-neutral-400 dark:hover:bg-white/20 dark:hover:text-neutral-300 dark:focus:bg-white/20 dark:focus:text-neutral-300"
                            @click="$wire.$set(@js($attributes->whereStartsWith('wire:model')->first()), generatedPassword); HSAccordion.hide('#bale-generate-password-form')">
                            {{ __('Use password') }}
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function app ()
        {
            return {
                passwordScore: 0,
                generatedPassword: '',
                charsLength: 12,
                charsLower: true,
                charsUpper: true,
                charsNumeric: true,
                charsSymbols: true,
                tooltipText: "Copy",
                showCopyIcon: true,
                chars: {
                    lower: 'abcdefghijklmnopqrstuvwxyz',
                    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    numeric: '0123456789',
                    symbols: '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~'
                },
                checkStrength ()
                {
                    if ( !this.generatedPassword ) return this.passwordScore = 0;
                    this.passwordScore = zxcvbn( this.generatedPassword ).score + 1;
                },
                generatePassword ()
                {
                    this.generatedPassword = this.shuffleArray(
                        ( ( document.getElementById( 'charsLower' ).checked ? this.chars.lower : '' ) + ( document
                            .getElementById( 'charsUpper' ).checked ? this.chars.upper : '' ) + ( document
                                .getElementById( 'charsNumeric' ).checked ? this.chars.numeric : '' ) + ( document
                                    .getElementById( 'charsSymbols' ).checked ? this.chars.symbols : '' ) ).split( '' )
                    ).join( '' ).substring( 0, this.charsLength );
                    this.checkStrength();
                },
                shuffleArray ( array )
                {
                    for ( let i = array.length - 1; i > 0; i-- ) {
                        const j = Math.floor( Math.random() * ( i + 1 ) );
                        [ array[ i ], array[ j ] ] = [ array[ j ], array[ i ] ];
                    }
                    return array;
                }

            }
        }
    </script>
@endif