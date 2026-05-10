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
        <div class="p-6 md:p-8 space-y-8">

            {{-- ── Fatal Error Alert ─────────────────────────────────── --}}
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
                        <button
                            wire:click="resetForm"
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                   text-sm font-semibold text-gray-700 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400
                                   transition-all duration-200 shadow-sm"
                        >
                            <x-lucide-refresh-cw class="w-4 h-4" />
                            {{ __('Migrate Another File') }}
                        </button>
                    </div>
                </div>
            @else
                {{-- ── Step 1: Select Destination Bale ──────────────── --}}
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

                {{-- ── Step 1.5: Select Author (Tenant Users) ────────── --}}
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

                {{-- ── Step 2: Upload SQL File ───────────────────────── --}}
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
