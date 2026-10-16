<div>
    <livewire:core-shared-components::data-table
        model="Bale\Core\Models\EntranceGateAllowlist"
        rowView="core::livewire.pages.entrance-gate.section.entrance-gate-row"
        cardView="core::livewire.pages.entrance-gate.section.entrance-gate-card"
        :columns="[
            [
                'key'      => 'ip_address',
                'label'    => __('IP / CIDR'),
                'sortable' => true,
            ],
            [
                'key'      => 'label',
                'label'    => __('Label'),
                'sortable' => true,
                'hidden'   => 'lg',
            ],
            [
                'key'      => 'is_active',
                'label'    => __('Status'),
                'sortable' => true,
                'hidden'   => 'sm',
            ],
            [
                'key'      => 'created_at',
                'label'    => __('Dibuat'),
                'sortable' => true,
                'hidden'   => 'md',
            ],
            [
                'key'      => 'actions',
                'label'    => '',
                'sortable' => false,
            ],
        ]"
        :searchable="['ip_address', 'label']"
        :filterOptions="[
            [
                'key'      => 'is_active',
                'label'    => __('Status'),
                'options'  => [1 => __('Aktif'), 0 => __('Nonaktif')],
                'allLabel' => __('Semua'),
            ],
        ]"
        sortField="created_at"
        sortDirection="desc"
        :perPage="20"
    />
</div>
