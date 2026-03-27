<div>
    <x-core::breadcrumb :items="[['label' => __('Permissions'), 'route' => 'permission.index']]" :active="__('Create Permission')" />

    <div class="max-w-4xl mx-auto mt-6">
        <div
            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden">
            {{-- Header Banner --}}
            <div
                class="p-6 border-b border-gray-100 dark:border-slate-800 bg-linear-to-r from-indigo-50/50 to-purple-50/50 dark:from-indigo-900/10 dark:to-purple-900/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Permission Details') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Define system access permission') }}
                </p>
            </div>

            <form wire:submit="save" class="p-8 space-y-6">
                <div>
                    <x-core::label for="name" :value="__('Permission Name')" />
                    <x-core::input id="name" type="text" class="block w-full mt-1" wire:model="name"
                        placeholder="e.g. permission.create" required />
                    <x-core::input-error for="name" class="mt-2" />
                </div>

                <div>
                    <x-core::label for="guard_name" :value="__('Guard Name')" />
                    <x-core::input id="guard_name" type="text" class="block w-full mt-1" wire:model="guard_name"
                        required />
                    <x-core::input-error for="guard_name" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-100 dark:border-slate-800">
                    <x-core::secondary-button link href="{{ route('permission.index') }}" label="Cancel" />

                    <x-core::button type="submit" label="Save Permission" spinner="save">
                        <x-slot name="icon"><x-lucide-check class="w-4 h-4" /></x-slot>
                    </x-core::button>
                </div>
            </form>
        </div>
    </div>
</div>