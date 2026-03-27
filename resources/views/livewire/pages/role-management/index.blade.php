<div>
    <x-core::page-header gradient :title="__('Role Management')" :subtitle="__('Manage system roles')" />

    <div class="mt-8">
        {{-- Search Bar --}}
        <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="relative w-full sm:max-w-xs">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <x-lucide-search class="w-4 h-4 text-slate-400" />
                </div>
                <x-core::input type="text" wire:model.live.debounce.300ms="query" class="w-full pl-10"
                    placeholder="{{ __('Search roles...') }}" />
            </div>
        </div>
    </div>

    {{-- Grid Skeleton (shown while Livewire is loading) --}}
    <div wire:loading.block wire:target="query, sortBy" class="mt-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 6; $i++)
                <div
                    class="animate-pulse bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden">
                    <div class="p-6 space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-28"></div>
                                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-16"></div>
                            </div>
                            <div class="h-6 w-6 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
                            <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-4/5"></div>
                            <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-3/5"></div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                        <div class="h-8 w-16 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                        <div class="h-8 w-16 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Actual Content --}}
    <div wire:loading.remove wire:target="query, sortBy" class="mt-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

            {{-- Add Role Card --}}
            @can('role.create')
                <a href="{{ route('role.create') }}" wire:navigate
                    class="group flex flex-col items-center justify-center gap-4 min-h-52 bg-white dark:bg-slate-900 rounded-2xl border-2 border-dashed border-indigo-200 dark:border-indigo-800/40 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-600 hover:shadow-xl transition-all duration-300 p-6">
                    <div class="flex flex-col items-center gap-3">
                        {{-- SVG Illustration --}}
                        <div
                            class="w-20 h-20 text-indigo-300 dark:text-indigo-700 group-hover:text-indigo-400 dark:group-hover:text-indigo-500 transition-colors duration-300">
                            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="38" stroke="currentColor" stroke-width="2" stroke-dasharray="6 4"
                                    opacity="0.4" />
                                <rect x="22" y="22" width="36" height="28" rx="4" fill="currentColor" opacity="0.12" />
                                <rect x="28" y="30" width="16" height="3" rx="1.5" fill="currentColor" opacity="0.5" />
                                <rect x="28" y="36" width="24" height="2" rx="1" fill="currentColor" opacity="0.35" />
                                <rect x="28" y="41" width="20" height="2" rx="1" fill="currentColor" opacity="0.25" />
                                <circle cx="55" cy="55" r="10" fill="currentColor" opacity="0.15" stroke="currentColor"
                                    stroke-width="1.5" />
                                <line x1="55" y1="50" x2="55" y2="60" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                                <line x1="50" y1="55" x2="60" y2="55" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="text-center">
                            <span
                                class="block text-sm font-bold text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
                                {{ __('Add New Role') }}
                            </span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                {{ __('Create a new system role') }}
                            </span>
                        </div>
                    </div>
                </a>
            @endcan

            {{-- Role Cards --}}
            @foreach ($this->roles as $role)
                @php
                    $permissions = $role->permissions;
                    $visiblePermissions = $permissions->take(6);
                    $moreCount = $permissions->count() - 6;
                @endphp
                <div wire:key="role-card-{{ $role->id }}"
                    class="flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">

                    {{-- Card Header --}}
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white capitalize leading-tight">
                                    {{ $role->name }}
                                </h3>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <x-lucide-users class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $role->users_count }} {{ Str::plural(__('user'), $role->users_count) }}
                                    </span>
                                </div>
                            </div>
                            <span
                                class="shrink-0 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-md">
                                {{ $role->guard_name }}
                            </span>
                        </div>
                    </div>

                    {{-- Permissions List --}}
                    <div class="p-6 flex-1">
                        @if ($permissions->isEmpty())
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('No permissions assigned') }}</p>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($visiblePermissions as $permission)
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40 rounded-md">
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                                @if ($moreCount > 0)
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/40 rounded-md cursor-default"
                                        title="{{ $permissions->skip(6)->pluck('name')->implode(', ') }}">
                                        +{{ $moreCount }} {{ __('more') }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer Actions --}}
                    @canany(['role.update', 'role.delete'])
                        <div
                            class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/30 flex items-center justify-end gap-2">
                            @can('role.update')
                                <a href="{{ route('role.edit', $role->id) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/40 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors">
                                    <x-lucide-pencil class="w-3.5 h-3.5" />
                                    {{ __('Edit') }}
                                </a>
                            @endcan
                            @can('role.delete')
                                <livewire:core.shared-components.item-actions :editUrl="null" :deleteId="$role->id"
                                    wire:key="item-actions-{{ $role->id }}"
                                    confirmMessage="{{ __('Are you sure you want to delete this role?') }}" />
                            @endcan
                        </div>
                    @endcanany
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $this->roles->links() }}
        </div>
    </div>
</div>