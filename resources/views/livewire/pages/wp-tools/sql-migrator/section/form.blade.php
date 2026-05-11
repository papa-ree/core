{{-- ===================================================================
|  Section: Upload & Configure Form
|  Shown when $isDone is false.
=================================================================== --}}

{{-- ── Step 1: Select Destination Bale ──────────────────────────── --}}
<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="bale-select">
        <span class="inline-flex items-center gap-1.5">
            <x-lucide-server class="w-4 h-4 text-indigo-500" />
            {{ __('Destination Bale (Tenant Database)') }}
        </span>
    </label>

    <x-core::select-dropdown
        :items="$baleLists"
        model="selectedBaleId"
        labelField="display_name"
        valueField="id"
        :placeholder="__('Select a Bale tenant')"
    />

    @error('selectedBaleId')
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    @if(empty($baleLists))
        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
            <x-lucide-alert-circle class="w-3.5 h-3.5" />
            {{ __('No active Bale tenants found. Please create one in Bale List management first.') }}
        </p>
    @endif
</div>

{{-- ── Step 1.5: Select Author (Tenant Users) ────────────────────── --}}
@if($selectedBaleId && !empty($tenantUsers))
    <div x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="author-select">
            <span class="inline-flex items-center gap-1.5">
                <x-lucide-user-check class="w-4 h-4 text-emerald-500" />
                {{ __('Assign Post Author') }}
            </span>
        </label>

        <x-core::select-dropdown
            :items="$tenantUsers"
            model="selectedAuthorId"
            labelField="name"
            valueField="id"
            :placeholder="__('Choose an author from this Bale')"
        />

        @error('selectedAuthorId')
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <p class="mt-2 text-[10px] text-gray-500 uppercase tracking-widest">
            {{ __('All imported posts will be assigned to this user.') }}
        </p>
    </div>
@elseif($selectedBaleId && empty($tenantUsers))
    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
        <p class="text-xs text-red-700 dark:text-red-400 flex items-center gap-2">
            <x-lucide-user-x class="w-4 h-4" />
            {{ __('No users found in the target Bale database. Please create a user in that Bale first.') }}
        </p>
    </div>
@endif

{{-- ── Step 2: Upload SQL File ────────────────────────────────────── --}}
<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
        <span class="inline-flex items-center gap-1.5">
            <x-lucide-file-code-2 class="w-4 h-4 text-purple-500" />
            {{ __('WordPress SQL Dump File') }}
        </span>
    </label>

    <x-core::upload-zone
        wire:model.live="sqlFile"
        accept="application/sql,.sql,text/plain,.txt"
        :maxSize="102400"
        :label="__('Drop your .sql dump here or click to browse')"
        :hint="__('Accepts .sql or .txt files — max 100 MB. For larger files, split with mysqldump --where.')"
    />

    @error('sqlFile')
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
            <x-lucide-alert-circle class="w-3.5 h-3.5" />
            {{ $message }}
        </p>
    @enderror
</div>

{{-- ── Advanced Settings (Collapsible) ──────────────────────────── --}}
<div>
    <button
        type="button"
        @click="showAdvanced = !showAdvanced"
        :class="showAdvanced ? 'ring-2 ring-indigo-500 dark:ring-indigo-400' : ''"
        class="flex items-center gap-2 w-full p-3 bg-gray-50 hover:bg-gray-100
               dark:bg-gray-900/50 dark:hover:bg-gray-900 rounded-lg transition-colors"
    >
        <x-lucide-settings class="w-4 h-4 text-gray-500" />
        <span
            class="flex-1 text-left text-sm font-medium"
            :class="showAdvanced ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300'"
        >
            {{ __('Advanced Settings') }}
        </span>
        <x-lucide-chevron-down
            class="w-4 h-4 text-gray-400 transition-transform duration-200"
            ::class="showAdvanced ? 'rotate-180' : ''"
        />
    </button>

    <div x-show="showAdvanced" x-collapse>
        <div class="mt-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl space-y-4">

            {{-- Table Prefix --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="table-prefix">
                    {{ __('Table Prefix') }}
                </label>
                <input
                    id="table-prefix"
                    type="text"
                    wire:model="tablePrefix"
                    placeholder="wp_"
                    class="block w-full sm:w-64 px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600
                           text-gray-900 dark:text-white rounded-lg text-sm font-mono
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Default: wp_ — Leave empty to auto-detect any prefix ending in _posts') }}
                </p>
            </div>

            {{-- Memory Notice --}}
            <div class="flex items-start gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-indigo-100 dark:border-indigo-900">
                <x-lucide-cpu class="w-4 h-4 text-indigo-500 mt-0.5 shrink-0" />
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    <strong>{{ __('Memory note:') }}</strong>
                    {{ __('For files > 32 MB, the memory limit is automatically raised to 512 MB. For dumps > 256 MB, consider splitting with: ') }}
                    <code class="font-mono text-indigo-600 dark:text-indigo-400">mysqldump --tables wp_posts</code>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ── Import Button ──────────────────────────────────────────────── --}}
<div class="pt-2 flex items-center justify-between flex-wrap gap-4 border-t border-gray-100 dark:border-gray-700">
    <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
        <x-lucide-shield-check class="w-4 h-4 text-emerald-500" />
        {{ __('Data is written directly to the tenant database. No data is stored locally.') }}
    </p>

    <button
        wire:click="import"
        wire:loading.attr="disabled"
        wire:target="import"
        id="wp-import-btn"
        type="button"
        class="inline-flex items-center gap-2.5 px-7 py-3 rounded-lg font-semibold text-sm text-white
               bg-linear-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700
               shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300
               disabled:opacity-50 disabled:cursor-not-allowed disabled:translate-y-0"
    >
        {{-- Loading state --}}
        <span wire:loading wire:target="import" class="inline-flex items-center gap-2">
            <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ __('Processing...') }}
        </span>

        {{-- Default state --}}
        <span wire:loading.remove wire:target="import" class="inline-flex items-center gap-2">
            <x-lucide-play class="w-4 h-4" />
            {{ __('Start Import') }}
        </span>
    </button>
</div>
