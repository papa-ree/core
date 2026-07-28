<tr wire:key="activity-log-row-{{ $record->id }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">
    {{-- Event --}}
    <td class="px-4 py-3.5">
        @php
            $eventColors = match($record->event) {
                'created' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                'updated' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                default   => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
            };
        @endphp
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $eventColors }}">
            @switch($record->event)
                @case('created')
                    <x-lucide-plus class="size-3" />
                    @break
                @case('updated')
                    <x-lucide-pencil class="size-3" />
                    @break
                @case('deleted')
                    <x-lucide-trash-2 class="size-3" />
                    @break
                @default
                    <x-lucide-circle-dot class="size-3" />
            @endswitch
            {{ ucfirst($record->event) }}
        </span>
    </td>

    {{-- Description --}}
    <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $record->description }}">
        {{ $record->description }}
    </td>

    {{-- Subject --}}
    <td class="px-4 py-3.5">
        @if($record->subject_type)
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-full bg-linear-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold text-[9px] shadow-sm">
                    {{ strtoupper(substr(class_basename($record->subject_type), 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ class_basename($record->subject_type) }}#{{ $record->subject_id }}
                    </p>
                    @if($record->attribute_changes?->get('attributes'))
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate max-w-[160px]">
                            @foreach($record->attribute_changes->get('attributes') as $key => $val)
                                {{ $key }}{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>
        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
        @endif
    </td>

    {{-- Causer --}}
    <td class="px-4 py-3.5">
        @if($record->causer_type && $record->causer_id)
            <div class="flex items-center gap-2">
                <div class="size-7 rounded-full bg-linear-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-[9px] shadow-sm">
                    {{ strtoupper(substr(class_basename($record->causer_type), 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ class_basename($record->causer_type) }}#{{ $record->causer_id }}
                    </p>
                </div>
            </div>
        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">System</span>
        @endif
    </td>

    {{-- Tenant --}}
    <td class="hidden px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400 lg:table-cell">
        @if($record->tenant_name)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                {{ $record->tenant_name }}
            </span>
        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
        @endif
    </td>

    {{-- IP Address --}}
    <td class="hidden px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400 md:table-cell">
        {{ $record->ip_address ?? '-' }}
    </td>

    {{-- Date --}}
    <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
        {{ $record->created_at?->format('d M Y H:i') ?? '-' }}
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        @can('activity-log.delete')
            <livewire:core.shared-components.item-actions
                :editUrl="null"
                :deleteId="$record->id"
                :navigate="false"
                confirmMessage="{{ __('Are you sure you want to delete this log?') }}"
                wire:key="item-actions-{{ $record->id }}" />
        @endcan
    </td>
</tr>
