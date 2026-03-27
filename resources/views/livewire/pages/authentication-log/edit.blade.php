<div>
    <x-core::page-header gradient :title="__('Edit Authentication Log')" :subtitle="__('Update authentication log details')">
        <x-slot name="action">
            <button type="button" wire:click="cancel" wire:navigate
                class="inline-flex items-center gap-x-2 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all shadow-sm">
                {{ __('Cancel') }}
            </button>
        </x-slot>
    </x-core::page-header>

    <div class="mt-6">
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
            <div class="p-6">
                <form wire:submit="update" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- User (Read Only) --}}
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('User') }}
                            </label>
                            <div
                                class="flex items-center gap-x-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                                <div
                                    class="flex items-center justify-center w-10 h-10 rounded-full bg-linear-to-br from-indigo-500 to-purple-600 text-white font-semibold text-sm">
                                    {{ strtoupper(substr($this->log->authenticatable->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <span class="block text-sm text-gray-900 dark:text-gray-100 font-semibold">
                                        {{ $this->log->authenticatable->name ?? 'Unknown' }}
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $this->log->authenticatable->email ?? '' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- IP Address --}}
                        <div>
                            <x-core::label for="ip_address" :value="__('IP Address')" />
                            <x-core::input id="ip_address" type="text" class="block mt-1 w-full" wire:model="ip_address"
                                required />
                            <x-core::input-error :for="'ip_address'" class="mt-2" />
                        </div>

                        {{-- User Agent --}}
                        <div class="col-span-2">
                            <x-core::label for="user_agent" :value="__('User Agent')" />
                            <textarea id="user_agent"
                                class="block mt-1 w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-900 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                rows="3" wire:model="user_agent"></textarea>
                            <x-core::input-error :for="'user_agent'" class="mt-2" />
                        </div>

                        {{-- Login At --}}
                        <div>
                            <x-core::label for="login_at" :value="__('Login At')" />
                            <x-core::input id="login_at" type="datetime-local" class="block mt-1 w-full"
                                wire:model="login_at" />
                            <x-core::input-error :for="'login_at'" class="mt-2" />
                        </div>

                        {{-- Logout At --}}
                        <div>
                            <x-core::label for="logout_at" :value="__('Logout At')" />
                            <x-core::input id="logout_at" type="datetime-local" class="block mt-1 w-full"
                                wire:model="logout_at" />
                            <x-core::input-error :for="'logout_at'" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <x-core::button type="submit">
                            {{ __('Save Changes') }}
                        </x-core::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>