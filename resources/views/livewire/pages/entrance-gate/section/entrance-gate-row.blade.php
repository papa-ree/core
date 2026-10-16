<tr wire:key="entrance-gate-row-{{ $record->getKey() }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">

    {{-- IP / CIDR (primary column) --}}
    <td class="px-4 py-3.5 w-full max-w-0 sm:max-w-none sm:w-auto">
        <div class="font-mono text-sm text-gray-900 dark:text-gray-100">
            {{ $record->ip_address }}
        </div>
    </td>

    {{-- Label --}}
    <td class="px-4 py-3.5 hidden lg:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-xs">
            {{ $record->label ?? '—' }}
        </div>
    </td>

    {{-- Status --}}
    <td class="px-4 py-3.5 hidden sm:table-cell">
        @if($record->is_active)
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40">
                {{ __('Aktif') }}
            </span>
        @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                {{ __('Nonaktif') }}
            </span>
        @endif
    </td>

    {{-- Created At --}}
    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $record->created_at }}
        </div>
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        <livewire:core.shared-components.item-actions
            :editUrl="route('entrance-gate.edit', $record->id)"
            :deleteId="$record->id"
            deleteEvent="deleteEntranceGateIp"
            :navigate="false"
            confirmMessage="{{ __('Yakin ingin menghapus entri allowlist ini?') }}"
            wire:key="item-actions-{{ $record->id }}" />
    </td>

</tr>
