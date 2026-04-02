<div>
    <x-core::page-header gradient :title="__('Authentication Log')" :subtitle="__('Monitor user login activities')">
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Core\Models\AuthenticationLog"
        rowView="core::livewire.pages.authentication-log.section.auth-log-row"
        :columns="[
            [
                'key'      => 'user',
                'label'    => __('User'),
                'sortable' => false,
            ],
            [
                'key'      => 'ip_address',
                'label'    => __('IP Address'),
                'sortable' => true,
            ],
            [
                'key'      => 'user_agent',
                'label'    => __('User Agent'),
                'sortable' => true,
                'hidden'   => 'lg',
            ],
            [
                'key'      => 'login_at',
                'label'    => __('Login At'),
                'sortable' => true,
            ],
            [
                'key'      => 'logout_at',
                'label'    => __('Logout At'),
                'sortable' => true,
            ],
            [
                'key'      => 'actions',
                'label'    => '',
                'sortable' => false,
            ],
        ]"
        :with="['authenticatable']"
        :searchable="['ip_address', 'user_agent']"
        sortField="login_at"
        sortDirection="desc"
        :perPage="20"
    />
</div>