<div>
    <x-core::breadcrumb :items="[['label' => __('Users'), 'route' => 'user-management']]" :active="__('Create User')" />

    <div class="max-w-5xl mx-auto mt-6">
        <div
            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden">

            {{-- Header Banner --}}
            <div
                class="relative px-8 py-7 overflow-hidden bg-linear-to-br from-indigo-500 via-purple-600 to-violet-700">
                {{-- Decorative circles --}}
                <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-12 -left-8 w-40 h-40 rounded-full bg-purple-300/20 blur-2xl"></div>

                <div class="relative flex items-center gap-5">
                    {{-- Avatar placeholder --}}
                    <div
                        class="shrink-0 w-16 h-16 flex items-center justify-center rounded-2xl bg-white/20 ring-2 ring-white/30 backdrop-blur-sm">
                        <x-lucide-user-plus class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ __('Create New User') }}</h2>
                        <p class="text-sm text-indigo-100 mt-0.5">
                            {{ __('Fill in the details below to register a new account') }}
                        </p>
                    </div>
                </div>
            </div>

            <form wire:submit="createUser" class="divide-y divide-gray-100 dark:divide-slate-800">

                {{-- Section: Profile Information --}}
                <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-0 lg:gap-0">
                    {{-- Section label --}}
                    <div class="px-8 py-7 bg-gray-50/60 dark:bg-slate-800/40 flex flex-col gap-1">
                        <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                            <x-lucide-circle-user class="w-4 h-4" />
                            <span class="text-xs font-semibold uppercase tracking-widest">{{ __('Profile') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                            {{ __('Basic identity information for the user') }}
                        </p>
                    </div>

                    {{-- Fields --}}
                    <div class="px-8 py-7 space-y-5">
                        {{-- Full Name --}}
                        <div>
                            <x-core::label for="name" :value="__('Full Name')" />
                            <x-core::input id="name" type="text" class="block w-full mt-1" wire:model="name" required
                                placeholder="John Doe" />
                            <x-core::input-error for="name" class="mt-2" />
                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Username --}}
                            <div>
                                <x-core::label for="username" :value="__('Username')" />
                                <x-core::input id="username" type="text" class="block w-full mt-1" wire:model="username"
                                    required placeholder="johndoe" />
                                <x-core::input-error for="username" class="mt-2" />
                            </div>

                            {{-- Email --}}
                            <div>
                                <x-core::label for="email" :value="__('Email Address')" />
                                <x-core::input id="email" type="email" class="block w-full mt-1" wire:model="email"
                                    required placeholder="john@example.com" />
                                <x-core::input-error for="email" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section: Security --}}
                <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-0 lg:gap-0">
                    {{-- Section label --}}
                    <div class="px-8 py-7 bg-gray-50/60 dark:bg-slate-800/40 flex flex-col gap-1">
                        <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                            <x-lucide-lock-keyhole class="w-4 h-4" />
                            <span class="text-xs font-semibold uppercase tracking-widest">{{ __('Security') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                            {{ __('Set a strong password. Use the generator for best results.') }}
                        </p>
                    </div>

                    {{-- Fields --}}
                    <div class="px-8 py-7 space-y-5">
                        {{-- Password with Generate Password --}}
                        <div>
                            <x-core::label for="password" :value="__('Password')" />
                            <x-core::input id="password" type="password" class="block w-full mt-1" wire:model="password"
                                useGenPassword required />
                            <p class="mt-1.5 text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                {{ __('Minimum 8 characters') }}
                            </p>
                            <x-core::input-error for="password" class="mt-2" />
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <x-core::label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-core::input id="password_confirmation" type="password" class="block w-full mt-1"
                                wire:model="password_confirmation" required />
                            <x-core::input-error for="password_confirmation" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between px-8 py-5 bg-gray-50/50 dark:bg-slate-800/30">
                    <x-core::secondary-button wire:click="cancel" type="button" label="{{ __('Cancel') }}" />

                    <x-core::button type="submit" label="{{ __('Create User') }}" spinner="createUser">
                        <x-slot name="icon"><x-lucide-user-plus class="w-4 h-4" /></x-slot>
                    </x-core::button>
                </div>

            </form>
        </div>
    </div>
</div>