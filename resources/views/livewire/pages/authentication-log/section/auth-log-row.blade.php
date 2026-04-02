<tr wire:key="auth-log-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">
    {{-- User --}}
    <td class="px-4 py-3.5">
        <div class="flex items-center gap-3">
            <div class="size-8 rounded-full bg-linear-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-[10px] shadow-sm">
                {{ strtoupper(substr($record->authenticatable->name ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ $record->authenticatable->name ?? 'Unknown' }}
                </p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500">
                    {{ $record->authenticatable->email ?? '' }}
                </p>
            </div>
        </div>
    </td>

    {{-- IP Address --}}
    <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">
        {{ $record->ip_address }}
    </td>

    {{-- User Agent --}}
    <td class="hidden px-4 py-3.5 text-xs text-gray-500 lg:table-cell">
        <div class="flex items-center gap-2">
            @switch($record->device_type)
                @case('mobile')
                    <x-lucide-smartphone class="w-3.5 h-3.5 text-gray-400" />
                    @break
                @case('tablet')
                    <x-lucide-tablet class="w-3.5 h-3.5 text-gray-400" />
                    @break
                @default
                    <x-lucide-monitor class="w-3.5 h-3.5 text-gray-400" />
            @endswitch
            <span class="max-w-[150px] truncate" title="{{ $record->user_agent }}">
                {{ $record->user_agent }}
            </span>
        </div>
    </td>

    {{-- Login At --}}
    <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">
        {{ $record->login_at?->format('d M Y H:i:s') ?? '-' }}
    </td>

    {{-- Logout At --}}
    <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">
        {{ $record->logout_at?->format('d M Y H:i:s') ?? '-' }}
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        @can('authentication-log.delete')
            <livewire:core.shared-components.item-actions
                :editUrl="null"
                :deleteId="$record->id"
                :navigate="false"
                confirmMessage="{{ __('Are you sure you want to delete this log?') }}"
                wire:key="item-actions-{{ $record->id }}" />
        @endcan
    </td>
</tr>
