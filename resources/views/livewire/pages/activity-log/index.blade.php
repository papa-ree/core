<div>
    <x-core::page-header gradient :title="__('Activity Log')" :subtitle="__('Track all user actions across the system')">
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Core\Models\ActivityLog"
        rowView="core::livewire.pages.activity-log.section.activity-log-row"
        :columns="[
            [
                'key'      => 'event',
                'label'    => __('Event'),
                'sortable' => true,
            ],
            [
                'key'      => 'description',
                'label'    => __('Description'),
                'sortable' => false,
            ],
            [
                'key'      => 'subject',
                'label'    => __('Subject'),
                'sortable' => false,
            ],
            [
                'key'      => 'causer',
                'label'    => __('Caused By'),
                'sortable' => false,
            ],
            [
                'key'      => 'tenant',
                'label'    => __('Tenant'),
                'sortable' => false,
                'hidden'   => 'lg',
            ],
            [
                'key'      => 'ip_address',
                'label'    => __('IP Address'),
                'sortable' => false,
                'hidden'   => 'md',
            ],
            [
                'key'      => 'created_at',
                'label'    => __('Date'),
                'sortable' => true,
            ],
            [
                'key'      => 'actions',
                'label'    => '',
                'sortable' => false,
            ],
        ]"
        :searchable="['description', 'log_name']"
        sortField="created_at"
        sortDirection="desc"
        :perPage="20"
    >
        <x-slot name="filters">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">
                    {{ __('Event Type') }}
                </label>
                <select wire:model.live="activeFilters.event"
                    class="w-full rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-sm font-medium focus:ring-indigo-500">
                    <option value="">{{ __('All Events') }}</option>
                    <option value="created">{{ __('Created') }}</option>
                    <option value="updated">{{ __('Updated') }}</option>
                    <option value="deleted">{{ __('Deleted') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">
                    {{ __('Log Name') }}
                </label>
                <select wire:model.live="activeFilters.log_name"
                    class="w-full rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-sm font-medium focus:ring-indigo-500">
                    <option value="">{{ __('All Logs') }}</option>
                    <option value="core">{{ __('Core') }}</option>
                    <option value="cms">{{ __('CMS') }}</option>
                    <option value="ikm">{{ __('IKM') }}</option>
                    <option value="loker">{{ __('Loker') }}</option>
                    <option value="rakaca">{{ __('Rakaca') }}</option>
                </select>
            </div>
        </x-slot>
    </livewire:core-shared-components::data-table>
</div>
