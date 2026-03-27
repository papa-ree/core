<div>
    <x-core::page-header :title="__('Edit Permission')" :subtitle="__('Modify existing permission')">
        <x-slot name="actions">
            <a href="{{ route('permission.index') }}" wire:navigate
                class="inline-flex items-center gap-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Back to List') }}
            </a>
        </x-slot>
    </x-core::page-header>

    <div class="mt-6">
        <div
            class="overflow-hidden bg-white shadow-sm dark:bg-slate-900 sm:rounded-xl border border-gray-200 dark:border-slate-800">
            <div class="p-6">
                <form wire:submit="save" class="space-y-6">
                    <div>
                        <x-core::label for="name" :value="__('Permission Name')" />
                        <x-core::input id="name" type="text" class="block w-full mt-1" wire:model="name" required />
                        <x-core::input-error for="name" class="mt-2" />
                    </div>

                    <div>
                        <x-core::label for="guard_name" :value="__('Guard Name')" />
                        <x-core::input id="guard_name" type="text" class="block w-full mt-1" wire:model="guard_name"
                            required />
                        <x-core::input-error for="guard_name" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-slate-800">
                        <button type="submit"
                            class="inline-flex items-center gap-x-2 px-6 py-2.5 text-sm font-semibold text-white bg-linear-to-r from-indigo-500 to-purple-600 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                            <span wire:loading.remove wire:target="save">{{ __('Update Permission') }}</span>
                            <span wire:loading wire:target="save">{{ __('Updating...') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>