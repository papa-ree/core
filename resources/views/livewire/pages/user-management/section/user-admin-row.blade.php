<tr wire:key="user-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">
    {{-- User --}}
    <td class="w-full py-4 pl-4 pr-3 text-sm font-medium text-gray-900 max-w-0 sm:w-auto sm:max-w-none">
        <div class="flex items-center gap-x-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-linear-to-br from-indigo-500 to-purple-600 text-white font-semibold text-sm shadow-sm ring-1 ring-white/20">
                {{ strtoupper(substr($record->name, 0, 1)) }}
            </div>
            <div class="grow min-w-0">
                <span class="block text-sm text-gray-800 dark:text-gray-200 font-bold truncate">{{ $record->name }}</span>
                <dl class="font-normal md:hidden">
                    <dt class="sr-only">Username</dt>
                    <dd class="text-xs text-gray-500 dark:text-gray-400 truncate">@<span>{{ $record->username }}</span></dd>
                    <dt class="sr-only lg:hidden">Email</dt>
                    <dd class="text-xs text-gray-500 dark:text-gray-400 lg:hidden truncate">
                        {{ $record->email }}
                    </dd>
                </dl>
            </div>
        </div>
    </td>

    {{-- Username (Desktop) --}}
    <td class="hidden px-3 py-4 text-sm text-gray-500 md:table-cell">
        <span class="text-gray-400 font-medium whitespace-nowrap">@</span><span class="text-gray-800 dark:text-gray-200">{{ $record->username }}</span>
    </td>

    {{-- Email (Desktop) --}}
    <td class="hidden px-3 py-4 text-sm text-gray-600 dark:text-gray-400 lg:table-cell">
        {{ $record->email }}
    </td>

    {{-- Roles --}}
    <td class="hidden px-3 py-4 text-sm text-gray-500 sm:table-cell">
        <div class="flex flex-wrap gap-1">
            @forelse($record->getRoleNames() as $role)
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 dark:text-indigo-300 rounded border border-indigo-100 dark:border-indigo-800">
                    {{ $role }}
                </span>
            @empty
                <span class="text-xs text-gray-400 dark:text-gray-600 italic">{{ __('No roles assigned') }}</span>
            @endforelse
        </div>
    </td>

    {{-- Actions --}}
    <td class="px-6 py-4 whitespace-nowrap w-px">
        @can('user-management.delete')
            <livewire:core.shared-components.item-actions
                :editUrl="route('user-management.edit', $record->id)"
                :deleteId="$record->id"
                :navigate="false"
                wire:key="user-actions-{{ $record->id }}"
                confirmMessage="{{ __('Are you sure you want to delete this user?') }}" />
        @endcan
    </td>
</tr>
