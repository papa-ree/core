<div>
    <x-core::breadcrumb :items="[
        ['label' => 'User Management', 'route' => 'user-management'],
    ]" active="Create User" />

    <x-core::page-header title="Create New User" subtitle="Add a new user to the system" />

    <div class="max-w-3xl">
        <div class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
            <form wire:submit="createUser" class="space-y-6">
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

                {{-- Password --}}
                <div>
                    <x-core::label for="password" value="Password *" />
                    <x-core::input id="password" type="password" wire:model="password" required />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum 8 characters</p>
                    @error('password')
                        <x-core::input-error :message="$message" />
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <x-core::label for="password_confirmation" value="Confirm Password *" />
                    <x-core::input id="password_confirmation" type="password" wire:model="password_confirmation"
                        required />
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-core::secondary-button wire:click="cancel" type="button">
                        Cancel
                    </x-core::secondary-button>
                    <x-core::button type="submit">
                        Create User
                    </x-core::button>
                </div>
            </form>
        </div>
    </div>
</div>