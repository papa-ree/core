<div>
    {{-- ===================================================================
    |  WP SQL Migrator
    |  Uploads a WordPress .sql dump, parses it in-memory, and inserts
    |  clean posts into a selected tenant (BaleList) database.
    =================================================================== --}}

    {{-- ── Hero Header ──────────────────────────────────────────────────── --}}
    <div
        class="relative overflow-hidden p-8 mb-8 text-white rounded-2xl shadow-xl"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);"
        data-aos="fade-up"
    >
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-6 md:mb-0">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                        <x-lucide-database-zap class="w-8 h-8 text-white" />
                    </div>
                    <h1 class="text-3xl font-bold text-white md:text-4xl">
                        {{ __('WP SQL Migrator') }}
                    </h1>
                </div>
                <p class="max-w-2xl text-white/90 text-lg">
                    {{ __('Upload a WordPress SQL dump and import published posts directly into a Bale tenant database.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── Info / Guide Banner ─────────────────────────────────────────── --}}
    <div
        class="mb-6 p-5 bg-linear-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20
                border border-amber-200 dark:border-amber-800 rounded-2xl"
        data-aos="fade-up" data-aos-delay="100"
    >
        <div class="flex items-start gap-4">
            <div class="p-3 bg-amber-600 rounded-xl shadow-lg shrink-0">
                <x-lucide-info class="w-6 h-6 text-white" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                    {{ __('How it works') }}
                </h3>
                <div class="grid gap-2 md:grid-cols-2">
                    <div class="flex items-start gap-2">
                        <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Only imports rows where post_type = \'post\' AND post_status = \'publish\'') }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Strips all HTML, images, and links from post content') }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Content is converted to EditorJS paragraph format') }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Slug is auto-generated from post_title using Str::slug()') }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('No external database connection — SQL is parsed as a text file') }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-check class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Table prefix is auto-detected or configurable below') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Card ───────────────────────────────────────────────────── --}}
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden"
        data-aos="fade-up" data-aos-delay="150"
        x-data="{ showAdvanced: false }"
    >
        {{-- Card Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-700
                    bg-linear-to-r from-indigo-50/50 to-purple-50/50 dark:from-indigo-900/10 dark:to-purple-900/10">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-linear-to-br from-indigo-500 to-purple-600 rounded-lg shadow-md">
                    <x-lucide-upload class="w-5 h-5 text-white" />
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ __('Upload & Configure') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Select your .sql dump file and target Bale tenant') }}</p>
                </div>
            </div>
        </div>

        {{-- Form Body --}}
        <div class="p-6 md:p-8 space-y-10">

            {{-- ── Step 0: Stored SQL Dumps (The List) ───────────────── --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                        <span class="inline-flex items-center gap-1.5">
                            <x-lucide-list class="w-4 h-4 text-indigo-500" />
                            {{ __('Stored SQL Dumps in S3') }}
                        </span>
                    </label>
                </div>

                @if(count($storedFiles) > 0)
                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Filename') }}</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Size') }}</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Uploaded At') }}</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($storedFiles as $file)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $selectedFile === $file['name'] ? 'bg-indigo-50/50 dark:bg-indigo-900/20' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                @if($selectedFile === $file['name'])
                                                    <x-lucide-check-circle-2 class="w-4 h-4 text-emerald-500" />
                                                @else
                                                    <x-lucide-file-text class="w-4 h-4 text-gray-400" />
                                                @endif
                                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs" title="{{ $file['name'] }}">
                                                    {{ $file['name'] }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                            {{ $file['size'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($file['date'])->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($selectedFile !== $file['name'])
                                                    <button 
                                                        wire:click="$set('selectedFile', '{{ $file['name'] }}')"
                                                        class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                        title="{{ __('Select for Migration') }}"
                                                    >
                                                        <x-lucide-mouse-pointer-click class="w-4 h-4" />
                                                    </button>
                                                @endif
                                                
                                                <button 
                                                    wire:confirm="{{ __('Are you sure you want to delete this dump from S3?') }}"
                                                    wire:click="deleteFile('{{ $file['name'] }}')"
                                                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="{{ __('Delete File') }}"
                                                >
                                                    <x-lucide-trash-2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50/50 dark:bg-gray-900/20">
                        <x-lucide-folder-open class="w-10 h-10 text-gray-300 mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No SQL dumps found in storage. Upload one below.') }}</p>
                    </div>
                @endif
            </div>

            {{-- ── Step 1: Upload New SQL File ──────────────────────── --}}
            <div class="pt-6 border-t border-gray-100 dark:border-gray-700">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    <span class="inline-flex items-center gap-1.5">
                        <x-lucide-plus-circle class="w-4 h-4 text-emerald-500" />
                        {{ __('Upload New SQL Dump to S3') }}
                    </span>
                </label>

                <div class="max-w-xl">
                    <x-core::upload-zone
                        wire:model.live="sqlFile"
                        accept="application/sql,.sql,text/plain,.txt"
                        :maxSize="102400"
                        :label="__('Choose file to store in S3')"
                        :hint="__('Max 100 MB. Files are stored in private/wp-sql-dumps/ on S3.')"
                    />
                </div>

                @error('sqlFile')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                        <x-lucide-alert-circle class="w-3.5 h-3.5" />
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ── Step 2: Configuration ────────────────────────────── --}}
            <div class="pt-6 border-t border-gray-100 dark:border-gray-700 space-y-8">
                <div class="flex items-center gap-2 mb-2">
                    <div class="size-6 bg-indigo-500 rounded-full flex items-center justify-center text-[10px] text-white font-bold">2</div>
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs">{{ __('Migration Configuration') }}</h4>
                </div>

                {{-- Fatal Error Alert --}}
                @if($fatalError)
                <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <x-lucide-alert-triangle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-red-700 dark:text-red-400">{{ __('Migration Failed') }}</p>
                        <p class="text-sm text-red-600 dark:text-red-300 mt-1">{{ $fatalError }}</p>
                    </div>
                </div>
            @endif

            {{-- ── Success Result ────────────────────────────────────── --}}
            @if($isDone)
                <div
                    class="p-6 bg-linear-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20
                           border border-emerald-200 dark:border-emerald-800 rounded-2xl"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-emerald-500 rounded-xl shadow-lg">
                            <x-lucide-check-circle-2 class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Migration Complete!') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('The WordPress posts have been successfully imported.') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4">
                        {{-- Imported --}}
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-emerald-100 dark:border-emerald-900 text-center shadow-sm">
                            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $importedCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Imported') }}</p>
                        </div>
                        {{-- Filtered --}}
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-amber-100 dark:border-amber-900 text-center shadow-sm">
                            <p class="text-3xl font-bold text-amber-500 dark:text-amber-400">{{ $filteredCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Filtered') }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">{{ __('(Draft/Page/Revision)') }}</p>
                        </div>
                        {{-- Skipped/Error --}}
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-red-100 dark:border-red-900 text-center shadow-sm">
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $skippedCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Errors') }}</p>
                        </div>
                        {{-- Total --}}
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-indigo-100 dark:border-indigo-900 text-center shadow-sm">
                            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $totalFoundCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Total Rows') }}</p>
                        </div>
                    </div>

                    {{-- Row-level errors --}}
                    @if(count($importErrors) > 0)
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-2">
                                <x-lucide-alert-circle class="w-4 h-4" />
                                {{ __('Some rows were skipped due to errors:') }}
                            </p>
                            <ul class="space-y-1 max-h-40 overflow-y-auto scrollbar-thin scrollbar-thumb-amber-400 scrollbar-track-amber-100">
                                @foreach($importErrors as $err)
                                    <li class="text-xs text-amber-800 dark:text-amber-300 font-mono">• {{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Reset Button --}}
                    <div class="mt-5 flex justify-end">
                        <x-core::button
                            wire:click="resetForm"
                            variant="secondary"
                            :label="__('Migrate Another File')"
                        >
                            <x-slot:icon>
                                <x-lucide-refresh-cw class="w-4 h-4" />
                            </x-slot:icon>
                        </x-core::button>
                    </div>
                </div>
            @else
                </div>

                {{-- Select Destination Bale --}}
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="bale-select">
                            <span class="inline-flex items-center gap-1.5">
                                <x-lucide-server class="w-4 h-4 text-indigo-500" />
                                {{ __('Destination Bale') }}
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
                    </div>

                    {{-- Select Author --}}
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
                                :placeholder="__('Choose an author')"
                            />

                            @error('selectedAuthorId')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @elseif($selectedBaleId && empty($tenantUsers))
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl self-end">
                            <p class="text-[10px] text-red-700 dark:text-red-400 flex items-center gap-2">
                                <x-lucide-user-x class="w-4 h-4" />
                                {{ __('No users found in target Bale.') }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Step 2: Selected File Info --}}
                @if($selectedFile)
                    <div class="p-4 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-900 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-500 rounded-lg">
                                <x-lucide-file-check class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">{{ __('Selected for Import') }}</p>
                                <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-400 truncate max-w-sm">{{ $selectedFile }}</p>
                            </div>
                        </div>
                        <button wire:click="$set('selectedFile', '')" class="text-xs text-gray-400 hover:text-red-500 transition-colors uppercase font-bold">
                            {{ __('Deselect') }}
                        </button>
                    </div>
                @endif

                {{-- ── Advanced Settings (Collapsible) ──────────────── --}}
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

                {{-- ── Import Button ─────────────────────────────────── --}}
                <div class="pt-2 flex items-center justify-between flex-wrap gap-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                        <x-lucide-shield-check class="w-4 h-4 text-emerald-500" />
                        {{ __('Data is written directly to the tenant database. No data is stored locally.') }}
                    </p>

                    <x-core::button
                        wire:click="import"
                        wire:loading.attr="disabled"
                        wire:target="import"
                        variant="primary"
                        :label="__('Start Import')"
                        @if(empty($selectedFile)) disabled @endif
                    >
                        <x-slot:icon>
                            <span wire:loading wire:target="import">
                                <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="import">
                                <x-lucide-play class="w-4 h-4" />
                            </span>
                        </x-slot:icon>
                    </x-core::button>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Processing Overlay ──────────────────────────────────────────── --}}
    <div
        wire:loading
        wire:target="import"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 flex flex-col items-center gap-5 max-w-sm mx-4">
            <div class="relative">
                <div class="w-16 h-16 rounded-full border-4 border-indigo-100 dark:border-indigo-900"></div>
                <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-transparent border-t-indigo-500 animate-spin"></div>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Migrating Posts…') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Parsing SQL, cleaning HTML, and writing to the tenant database. Please wait.') }}
                </p>
            </div>
            {{-- Progress pulse --}}
            <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-full">
                <div class="w-2 h-2 bg-indigo-600 rounded-full animate-pulse"></div>
                <span class="text-xs font-medium text-indigo-700 dark:text-indigo-400">{{ __('In progress') }}</span>
            </div>
        </div>
    </div>

</div>
