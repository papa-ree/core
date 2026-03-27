<div>
    <x-core::page-header gradient :title="__('Authentication Log')" :subtitle="__('Monitor user login activities')">
    </x-core::page-header>

    <x-core::table :links="$this->logs" header>
        <x-slot name="thead">
            <tr>
                <x-core::table-th label="User" />
                <x-core::table-th label="IP Address" sortBy="ip_address" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                <x-core::table-th class="hidden lg:table-cell" label="User Agent" sortBy="user_agent"
                    :sortField="$sortField" :sortDirection="$sortDirection" />
                <x-core::table-th label="Login At" sortBy="login_at" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                <x-core::table-th label="Logout At" sortBy="logout_at" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                @can('authentication-log.delete')
                    <x-core::table-th label="Action" />
                @endcan
            </tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($this->logs as $log)
                <tr wire:key='log-{{ $log->id }}'
                    class="transition-colors duration-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    {{-- User --}}
                    <td class="px-3 py-4 text-sm text-gray-500">
                        <div class="flex items-center gap-x-3">
                            <div
                                class="flex items-center justify-center w-8 h-8 rounded-full bg-linear-to-br from-indigo-500 to-purple-600 text-white font-semibold text-xs">
                                {{ strtoupper(substr($log->authenticatable->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="grow">
                                <span class="block text-sm text-gray-800 dark:text-gray-200 font-medium">
                                    {{ $log->authenticatable->name ?? 'Unknown' }}
                                </span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->authenticatable->email ?? '' }}
                                </span>
                            </div>
                        </div>
                    </td>

                    {{-- IP Address --}}
                    <td class="px-3 py-4 text-sm text-gray-500">
                        <span class="block text-sm text-gray-800 dark:text-gray-200">{{ $log->ip_address }}</span>
                    </td>

                    {{-- User Agent --}}
                    <td class="hidden px-3 py-4 text-sm text-gray-500 lg:table-cell">
                        <div class="flex items-center gap-x-2">
                            @switch($log->device_type)
                                @case('mobile')
                                    <x-lucide-smartphone class="w-4 h-4 text-slate-400" />
                                @break

                                @case('tablet')
                                    <x-lucide-tablet class="w-4 h-4 text-slate-400" />
                                @break

                                @default
                                    <x-lucide-monitor class="w-4 h-4 text-slate-400" />
                            @endswitch
                            <span class="block text-xs text-gray-600 dark:text-gray-400 max-w-xs truncate"
                                title="{{ $log->user_agent }}">
                                {{ $log->user_agent }}
                            </span>
                        </div>
                    </td>

                    {{-- Login At --}}
                    <td class="px-3 py-4 text-sm text-gray-500">
                        <span class="block text-sm text-gray-800 dark:text-gray-200">
                            {{ $log->login_at?->format('d M Y H:i:s') ?? '-' }}
                        </span>
                    </td>

                    {{-- Logout At --}}
                    <td class="px-3 py-4 text-sm text-gray-500">
                        <span class="block text-sm text-gray-800 dark:text-gray-200">
                            {{ $log->logout_at?->format('d M Y H:i:s') ?? '-' }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    @can('authentication-log.delete')
                        <td class="size-px whitespace-nowrap">
                            <div class="px-6 py-1.5">
                                <livewire:core.shared-components.item-actions :editUrl="route('authentication-log.edit', $log->id)" :deleteId="$log->id" wire:key="item-actions-{{ $log->id }}"
                                    confirmMessage="Are you sure you want to delete this log?" />
                            </div>
                        </td>
                    @endcan
                </tr>
            @endforeach
        </x-slot>
    </x-core::table>
</div>