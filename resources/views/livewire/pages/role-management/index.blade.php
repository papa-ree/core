<div>
    <x-core::page-header gradient :title="__('Role Management')" :subtitle="__('Manage system roles')">
        <x-slot name="action">
            @can('role.create')
                <x-core::button link href="{{ route('role.create') }}" label="Add New Role">
                    <x-slot name="icon">
                        <x-lucide-plus class="w-5 h-5" />
                    </x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Spatie\Permission\Models\Role"
        rowView="core::livewire.pages.role-management.section.role-row"
        :columns="[
            [
                'key'      => 'name',
                'label'    => __('Role'),
                'sortable' => true,
            ],
            [
                'key'      => 'permissions',
                'label'    => __('Permissions'),
                'sortable' => false,
            ],
            [
                'key'      => 'guard_name',
                'label'    => __('Guard'),
                'sortable' => true,
            ],
            [
                'key'      => 'actions',
                'label'    => '',
                'sortable' => false,
            ],
        ]"
        :with="['permissions']"
        :withCount="['users']"
        :searchable="['name', 'guard_name']"
        sortField="name"
        sortDirection="asc"
        :perPage="20"
    />
</div>