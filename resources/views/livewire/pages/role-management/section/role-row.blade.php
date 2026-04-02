<tr wire:key="role-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">
    <td class="px-4 py-4 truncate">
        <div class="flex items-center gap-3">
            <div class="size-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-100 dark:border-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <x-lucide-shield class="size-4" />
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 dark:text-white capitalize">
                    {{ $record->name }}
                </p>
                <div class="flex items-center gap-1.5 mt-0.5 font-normal">
                    <x-lucide-users class="w-3 h-3 text-gray-400" />
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">
                        {{ $record->users_count }} {{ Str::plural(__('user'), $record->users_count) }}
                    </span>
                </div>
            </div>
        </div>
    </td>
    <td class="px-4 py-4">
        <div class="flex flex-wrap gap-1.5 max-w-sm">
            @php
                $permissions = $record->permissions;
                $visiblePermissions = $permissions->take(6);
                $moreCount = $permissions->count() - 6;
            @endphp
            @forelse ($visiblePermissions as $permission)
                <span class="px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40 rounded-md">
                    {{ $permission->name }}
                </span>
            @empty
                <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('No permissions assigned') }}</span>
            @endforelse
            @if ($moreCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/40 rounded-md cursor-default"
                    title="{{ $permissions->skip(6)->pluck('name')->implode(', ') }}">
                    +{{ $moreCount }} {{ __('more') }}
                </span>
            @endif
        </div>
    </td>
    <td class="px-4 py-4">
        <span class="px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-md">
            {{ $record->guard_name }}
        </span>
    </td>
    <td class="px-4 py-4 whitespace-nowrap w-px">
        @canany(['role.update', 'role.delete'])
            <livewire:core.shared-components.item-actions
                :editUrl="route('role.edit', $record->id)"
                :deleteId="$record->id"
                :navigate="false"
                wire:key="role-actions-{{ $record->id }}"
                confirmMessage="{{ __('Are you sure you want to delete this role?') }}" />
        @endcanany
    </td>
</tr>
