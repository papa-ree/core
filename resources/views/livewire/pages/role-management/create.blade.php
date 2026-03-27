<div>
    <x-core::breadcrumb :items="[['label' => __('Roles'), 'route' => 'role.index']]" :active="__('Create Role')" />

    <div class="max-w-4xl mx-auto">
        <div
            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden">

            {{-- Header Banner --}}
            <div
                class="p-6 border-b border-gray-100 dark:border-slate-800 bg-linear-to-r from-indigo-50/50 to-purple-50/50 dark:from-indigo-900/10 dark:to-purple-900/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Role Details') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Define the details and system guards for the new role') }}
                </p>
            </div>

            <form wire:submit="save" class="p-8 space-y-6">
                <div>
                    <x-core::input label="{{ __('Role Name') }}" wire:model="name" name="name"
                        placeholder="e.g. administrator" required autofocus />
                    <x-core::input-error for="name" />
                </div>

                <div>
                    <x-core::input label="{{ __('Guard Name') }}" wire:model="guard_name" name="guard_name"
                        placeholder="e.g. web" required />
                    <x-core::input-error for="guard_name" />
                </div>

                {{-- Actions / Footer --}}
                <div class="pt-6 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between">
                    <x-core::secondary-button type="button" link href="{{ route('role.index') }}" label="Cancel" />

                    <x-core::button type="submit" variant="primary" label="Save Role" spinner="save">
                        <x-slot name="icon"><x-lucide-check class="w-4 h-4" /></x-slot>
                    </x-core::button>
                </div>
            </form>
        </div>
    </div>
</div>