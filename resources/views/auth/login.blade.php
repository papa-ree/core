@extends('core::layouts.guest')

<style>
    .grecaptcha-badge {
        visibility: hidden !important;
    }
</style>
{!! RecaptchaV3::initJs() !!}

<div x-data="recaptchaHandler()" class="bg-white dark:bg-gray-900" x-cloak>

    <div class="flex justify-center h-screen">
        <div
            class="bg-center bg-cover bg-no-repeat relative block lg:w-2/3 bg-[url('https://images.unsplash.com/photo-1665686374006-b8f04cf62d57?ixlib=rb-4.0.3&ixid=MnwxMjA3fDF8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1020&q=80')]">
            <div class="flex items-center h-full px-20 bg-gray-900 bg-opacity-40">
                <div>
                    <h2 class="text-2xl font-semibold text-white sm:text-3xl">BALé CMS</h2>
                    <p class="max-w-xl mt-3 text-gray-300">
                        Content Management System By Dinas Kominfo Ponorogo
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center w-full max-w-md px-6 mx-auto lg:w-2/6">
            <div class="flex-1">
                <div class="text-center">
                    <div class="flex justify-center mx-auto">
                        {{-- <img class="w-auto h-12 sm:h-16" src="{{ asset('img/sanggaha link.png') }}"
                            alt="site logo"> --}}
                    </div>
                    <p class="mt-2 text-2xl font-bold text-gray-500 dark:text-gray-300">Rakaca Platform</p>
                    <p class="mt-5 text-gray-500 dark:text-gray-300">Sign in</p>
                </div>

                <div class="mt-5">
                    <form method="POST" action="{{ route('login') }}" autocomplete="off">
                        @csrf
                        <div>
                            <label for="username" class="block mb-2 text-sm text-gray-600 dark:text-gray-200">
                                Username
                            </label>
                            <input type="text" name="username" id="username" placeholder="username" autofocus
                                autocomplete="off"
                                class="block w-full px-4 py-2 mt-2 text-gray-700 placeholder-gray-400 bg-white border border-gray-200 rounded-lg dark:placeholder-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700 focus:border-blue-400 dark:focus:border-blue-400 focus:ring-blue-400 focus:outline-none focus:ring focus:ring-opacity-40" />
                            <x-core::input-error for="username" />
                        </div>

                        <div class="mt-6">
                            <div class="flex justify-between mb-2">
                                <label for="password" class="text-sm text-gray-600 dark:text-gray-200">Password</label>
                            </div>

                            <input type="password" name="password" id="password" placeholder="Your Password"
                                autocomplete="off"
                                class="block w-full px-4 py-2 mt-2 text-gray-700 placeholder-gray-400 bg-white border border-gray-200 rounded-lg dark:placeholder-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700 focus:border-blue-400 dark:focus:border-blue-400 focus:ring-blue-400 focus:outline-none focus:ring focus:ring-opacity-40" />
                            <x-core::input-error for="password" />
                        </div>

                        {!! RecaptchaV3::field('login') !!}
                        <x-core::input-error for="g-recaptcha-response" />

                        <div class="relative mt-6">
                            <button type="submit" :disabled="!recaptchaValue" x-show="recaptchaValue"
                                class="flex items-center justify-center w-full px-4 py-3 text-sm antialiased tracking-wide text-center text-white capitalize transition-all duration-500 border-2 border-blue-300 rounded-lg dark:border dark:border-blue-300/70 bg-linear-to-tl from-emerald-300 via-blue-500 to-teal-500 bg-size-200 bg-pos-0 hover:bg-pos-100">
                                {{ __('Sign in') }}
                            </button>

                            <button type="submit" :disabled="recaptchaValue" x-show="!recaptchaValue"
                                class="flex items-center justify-center w-full px-4 py-3 text-sm antialiased tracking-wide text-center text-gray-500 capitalize transition-all duration-500 bg-gray-100 border-2 border-gray-300 rounded-lg dark:border dark:border-gray-300/70">
                                {{-- {{ __('Please Wait') }} --}}
                                <div class="animate-spin inline-block size-6 border-[3px] border-current border-t-transparent text-gray-600 rounded-full dark:text-gray-500"
                                    role="status" aria-label="loading">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </button>

                            {{-- <a href="#_"
                                class="box-border relative z-30 inline-flex items-center justify-center w-auto px-8 py-3 overflow-hidden font-bold text-white transition-all duration-300 bg-teal-600 rounded-md cursor-pointer group ring-offset-2 ring-1 ring-teal-300 ring-offset-teal-200 hover:ring-offset-teal-500 ease focus:outline-none">
                                <span
                                    class="absolute bottom-0 right-0 w-8 h-20 -mb-8 -mr-5 transition-all duration-300 ease-out transform rotate-45 translate-x-1 bg-white opacity-10 group-hover:translate-x-0"></span>
                                <span
                                    class="absolute top-0 left-0 w-20 h-8 -mt-1 -ml-12 transition-all duration-300 ease-out transform -rotate-45 -translate-x-1 bg-white opacity-10 group-hover:translate-x-0"></span>
                                <span class="relative z-20 flex items-center text-sm">
                                    <svg class="relative w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Button Text
                                </span>
                            </a> --}}
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function recaptchaHandler ()
    {
        return {
            recaptchaValue: '',
            init ()
            {
                const observer = new MutationObserver( () =>
                {
                    const input = document.querySelector( 'input[name="g-recaptcha-response"]' );
                    if ( input ) {
                        this.recaptchaValue = input.value || '';
                    }
                } );
                observer.observe( document.body, {
                    subtree: true,
                    attributes: true
                } );
            }
        };
    }
</script>