<div>
    <x-core::page-header gradient :title="__('Permission Management')" :subtitle="__('Manage system permissions')">
        <x-slot name="action">
            @can('permission.create')
                <x-core::button link href="{{ route('permission.create') }}" label="Add New Permission">
                    <x-slot name="icon">
                        <x-lucide-plus class="w-5 h-5" />
                    </x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <x-core::table :links="$this->permissions" header>
        <x-slot name="thead">
            <tr>
                <x-core::table-th label="Permission Name" sortBy="name" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                <x-core::table-th label="Roles" />
                <x-core::table-th label="Guard" sortBy="guard_name" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                @canany(['permission.update', 'permission.delete'])
                    <x-core::table-th label="Action" />
                @endcanany
            </tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($this->permissions as $permission)
                <tr wire:key='permission-{{ $permission->id }}'
                    class="transition-colors duration-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    <td class="px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-200">
                        {{ $permission->name }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($permission->roles as $role)
                                <span
                                    class="px-2 py-0.5 text-[10px] font-medium text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800/50">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400 italic">{{ __('No roles') }}</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <span
                            class="px-2 py-1 text-xs font-medium text-slate-700 bg-slate-100 rounded-lg dark:bg-slate-800 dark:text-slate-300">
                            {{ $permission->guard_name }}
                        </span>
                    </td>
                    @canany(['permission.update', 'permission.delete'])
                        <td class="size-px whitespace-nowrap">
                            <div class="px-6 py-1.5">
                                <livewire:core.shared-components.item-actions :editUrl="route('permission.edit', $permission->id)"
                                    :deleteId="$permission->id" wire:key="item-actions-{{ $permission->id }}"
                                    confirmMessage="Are you sure you want to delete this permission?" />
                            </div>
                        </td>
                    @endcanany
                </tr>
            @endforeach
        </x-slot>
    </x-core::table>
</div>