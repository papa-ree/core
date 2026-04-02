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

    <livewire:core-shared-components::data-table
        model="Spatie\Permission\Models\Permission"
        rowView="core::livewire.pages.permission-management.section.permission-row"
        :columns="[
            [
                'key'      => 'name',
                'label'    => __('Permission Name'),
                'sortable' => true,
            ],
            [
                'key'      => 'roles',
                'label'    => __('Roles'),
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
        :with="['roles']"
        :searchable="['name', 'guard_name']"
        sortField="name"
        sortDirection="asc"
        :perPage="20"
    />
</div>