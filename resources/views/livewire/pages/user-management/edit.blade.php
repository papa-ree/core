<div x-data="{ activeTab: @entangle('activeTab') }">
    <x-core::breadcrumb :items="[
        ['label' => 'User Management', 'route' => 'user-management'],
    ]" active="Edit User" />

    <x-core::page-header title="Edit User: {{ $this->user->name }}"
        subtitle="Manage user information, roles, and services" />

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="p-4 mb-6 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg dark:bg-green-900/20 dark:text-green-400 dark:border-green-800"
            role="alert">
            <div class="flex items-center gap-x-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 mb-6 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-400 dark:border-red-800"
            role="alert">
            <div class="flex items-center gap-x-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="max-w-5xl">
        {{-- Tabs Navigation --}}
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="flex gap-x-2" aria-label="Tabs">
                <button type="button" @click="activeTab = 'basic-info'"
                    :class="activeTab === 'basic-info' ?
                        'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' :
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm font-medium whitespace-nowrap focus:outline-none transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Basic Info
                </button>
                <button type="button" @click="activeTab = 'roles'"
                    :class="activeTab === 'roles' ?
                        'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' :
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm font-medium whitespace-nowrap focus:outline-none transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    Roles
                </button>
                <button type="button" @click="activeTab = 'services'"
                    :class="activeTab === 'services' ?
                        'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' :
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="py-4 px-1 inline-flex items-center gap-x-2 border-b-2 text-sm font-medium whitespace-nowrap focus:outline-none transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    Services
                </button>
            </nav>
        </div>

        {{-- Basic Info Tab --}}
        <div x-show="activeTab === 'basic-info'" x-transition>
            <div class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <form wire:submit="updateBasicInfo" class="space-y-6">
                    {{-- Name --}}
                    <div>
                        <x-core::label for="name" value="Name *" />
                        <x-core::input id="name" type="text" wire:model="name" required />
                        @error('name')
                            <x-core::input-error :message="$message" />
                        @enderror
                    </div>

                    {{-- Username --}}
                    <div>
                        <x-core::label for="username" value="Username *" />
                        <x-core::input id="username" type="text" wire:model="username" required />
                        @error('username')
                            <x-core::input-error :message="$message" />
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <x-core::label for="email" value="Email *" />
                        <x-core::input id="email" type="email" wire:model="email" required />
                        @error('email')
                            <x-core::input-error :message="$message" />
                        @enderror
                    </div>

                    {{-- New Password --}}
                    <div>
                        <x-core::label for="password" value="New Password" />
                        <x-core::input id="password" type="password" wire:model="password" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to keep current password
                        </p>
                        @error('password')
                            <x-core::input-error :message="$message" />
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <x-core::label for="password_confirmation" value="Confirm New Password" />
                        <x-core::input id="password_confirmation" type="password" wire:model="password_confirmation" />
                    </div>

                    {{-- Actions --}}
                    <div
                        class="flex items-center justify-end gap-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-core::secondary-button wire:click="cancel" type="button">
                            Cancel
                        </x-core::secondary-button>
                        <x-core::button type="submit">
                            Update Basic Info
                        </x-core::button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Roles Tab --}}
        <div x-show="activeTab === 'roles'" x-transition>
            <div class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Role Assignment</h3>
                <form wire:submit="updateRoles" class="space-y-6">
                    <div class="space-y-3">
                        @forelse($this->availableRoles as $role)
                            <label
                                class="flex items-center gap-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                                <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                    class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-emerald-600">
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                                    @if($role->permissions->count() > 0)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $role->permissions->count() }} permissions
                                        </p>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No roles available</p>
                        @endforelse
                    </div>

                    {{-- Actions --}}
                    <div
                        class="flex items-center justify-end gap-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-core::secondary-button wire:click="cancel" type="button">
                            Cancel
                        </x-core::secondary-button>
                        <x-core::button type="submit">
                            Update Roles
                        </x-core::button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Services Tab --}}
        <div x-show="activeTab === 'services'" x-transition>
            <div class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Service Assignment</h3>
                <form wire:submit="updateServices" class="space-y-6">
                    <div class="space-y-3">
                        @forelse($this->availableServices as $service)
                            <label
                                class="flex items-center gap-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
                                <input type="checkbox" wire:model="selectedServices" value="{{ $service->id }}"
                                    class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-emerald-600">
                                <div class="flex-1">
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->name }}</span>
                                    @if($service->slug)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Slug: {{ $service->slug }}
                                        </p>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No services available</p>
                        @endforelse
                    </div>

                    {{-- Actions --}}
                    <div
                        class="flex items-center justify-end gap-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-core::secondary-button wire:click="cancel" type="button">
                            Cancel
                        </x-core::secondary-button>
                        <x-core::button type="submit">
                            Update Services
                        </x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>