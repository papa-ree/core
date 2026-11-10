<div>
    <x-core::page-header gradient :title="__('User Management')" :subtitle="__('Manage system users')">
        <x-slot name="action">
            @can('user-management.create')
                <x-core::button link href="{{ route('user-management.create') }}" label="Add New User">
                    <x-slot name="icon">
                        <x-lucide-plus class="w-5 h-5" />
                    </x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="App\Models\User"
        rowView="core::livewire.pages.user-management.section.user-admin-row"
        :columns="[
            [
                'key'      => 'name',
                'label'    => __('User'),
                'sortable' => true,
            ],
            [
                'key'      => 'username',
                'label'    => __('Username'),
                'sortable' => true,
                'hidden'   => 'md',
            ],
            [
                'key'      => 'email',
                'label'    => __('Email'),
                'sortable' => true,
                'hidden'   => 'lg',
            ],
            [
                'key'      => 'roles',
                'label'    => __('Roles'),
                'sortable' => false,
                'hidden'   => 'sm',
            ],
            [
                'key'      => 'actions',
                'label'    => '',
                'sortable' => false,
            ],
        ]"
        :searchable="['name', 'email', 'username']"
        sortField="name"
        sortDirection="asc"
        :perPage="20"
    />
</div>