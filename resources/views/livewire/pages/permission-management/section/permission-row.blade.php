<tr wire:key="permission-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">
    <td class="px-4 py-3.5 text-sm font-medium text-gray-900 dark:text-gray-200">
        {{ $record->name }}
    </td>
    <td class="px-4 py-3.5 text-sm text-gray-500 dark:text-gray-400">
        <div class="flex flex-wrap gap-1">
            @forelse ($record->roles as $role)
                <span class="px-2 py-0.5 text-[10px] font-medium text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800/50">
                    {{ $role->name }}
                </span>
            @empty
                <span class="text-xs text-gray-400 italic">{{ __('No roles') }}</span>
            @endforelse
        </div>
    </td>
    <td class="px-4 py-3.5 text-sm text-gray-500 dark:text-gray-400">
        <span class="px-2 py-1 text-xs font-medium text-slate-700 bg-slate-100 rounded-lg dark:bg-slate-800 dark:text-slate-300">
            {{ $record->guard_name }}
        </span>
    </td>
    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        @canany(['permission.update', 'permission.delete'])
            <livewire:core.shared-components.item-actions
                :editUrl="route('permission.edit', $record->id)"
                :deleteId="$record->id"
                :navigate="false"
                wire:key="permission-actions-{{ $record->id }}"
                confirmMessage="{{ __('Are you sure you want to delete this permission?') }}" />
        @endcanany
    </td>
</tr>
