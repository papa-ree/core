<div class="px-4 py-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">{{ __('IP / CIDR') }}</p>
            <p class="mt-0.5 font-mono text-sm font-semibold text-gray-900 dark:text-gray-100 break-all">{{ $record->ip_address }}</p>

            @if($record->label)
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $record->label }}</p>
            @endif

            <div class="mt-2 flex flex-wrap items-center gap-2">
                @if($record->is_active)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40">
                        {{ __('Aktif') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                        {{ __('Nonaktif') }}
                    </span>
                @endif
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $record->created_at }}</span>
            </div>
        </div>

        <div class="shrink-0">
            <livewire:core.shared-components.item-actions
                :editUrl="route('entrance-gate.edit', $record->id)"
                :deleteId="$record->id"
                deleteEvent="deleteEntranceGateIp"
                :navigate="false"
                confirmMessage="{{ __('Yakin ingin menghapus entri allowlist ini?') }}"
                wire:key="item-actions-card-{{ $record->id }}" />
        </div>
    </div>
</div>
