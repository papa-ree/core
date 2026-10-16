{{-- ===================================================================
|  Section: Migration Progress
|  Live progress driven by Alpine (fetch + setInterval) — deliberately no
|  wire:poll so Livewire never re-renders just to refresh counters.
|  Polls core's JSON status endpoint every 2s until the run is done.
=================================================================== --}}
<div
    x-data='{
        runId: @json($runId),
        url: @json(route("wp-tools.migrator.status", ["run" => $runId])),
        state: null,
        error: null,
        timer: null,
        start() {
            this.refresh();
            this.timer = setInterval(() => this.refresh(), 2000);
        },
        stop() {
            if (this.timer) { clearInterval(this.timer); this.timer = null; }
        },
        async refresh() {
            try {
                const res = await fetch(this.url, { headers: { Accept: "application/json" } });
                if (! res.ok) { this.error = "HTTP " + res.status; return; }
                this.state = await res.json();
                this.error = null;
                if (this.state && this.state.done) { this.stop(); }
            } catch (e) {
                this.error = String(e);
            }
        },
        get run() { return (this.state && this.state.run) ? this.state.run : {}; },
        get counts() { return this.run.counts ? this.run.counts : {}; },
        get total() { return this.run.total ? this.run.total : 0; },
        get pending() { return this.run.pending ? this.run.pending : 0; },
        get processed() { return Math.max(0, this.total - this.pending); },
        get percent() {
            if (this.total > 0) { return Math.round((this.processed / this.total) * 100); }
            return (this.state && this.state.done) ? 100 : 0;
        },
        get media() { return (this.state && this.state.media) ? this.state.media : null; },
        get done() { return !!(this.state && this.state.done); },
        get errors() { return (this.state && this.state.errors) ? this.state.errors : []; },
        get security() { return (this.state && this.state.security) ? this.state.security : []; },
        get imported() { return this.counts.imported ? this.counts.imported : 0; },
        get migrated() { return this.counts.migrated ? this.counts.migrated : 0; },
        get blocked() { return this.counts.blocked ? this.counts.blocked : 0; },
        get failed() { return this.counts.failed ? this.counts.failed : 0; },
        get skipped() { return this.counts.skipped ? this.counts.skipped : 0; }
    }'
    x-init="start()"
    class="p-6 bg-linear-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20
           border border-indigo-200 dark:border-indigo-800 rounded-2xl"
>
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-5">
        <div class="p-3 rounded-xl shadow-lg" :class="done ? 'bg-emerald-500' : 'bg-indigo-500'">
            <template x-if="!done">
                <svg class="w-7 h-7 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </template>
            <template x-if="done">
                <x-lucide-check-circle-2 class="w-7 h-7 text-white" />
            </template>
        </div>
        <div>
            <template x-if="!done">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Migration in progress…') }}</h3>
            </template>
            <template x-if="done">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Migration Complete!') }}</h3>
            </template>
            <template x-if="!done">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Queued jobs are running in the background. You can leave this page open to watch progress.') }}</p>
            </template>
            <template x-if="done">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('All queued jobs have finished.') }}</p>
            </template>
        </div>
    </div>

    {{-- Progress bar --}}
    <div class="w-full h-3 bg-white dark:bg-gray-900 rounded-full overflow-hidden border border-indigo-100 dark:border-indigo-900">
        <div class="h-3 bg-linear-to-r from-indigo-500 to-purple-600 transition-all duration-500"
             :style="'width: ' + percent + '%'"></div>
    </div>
    <div class="flex items-center justify-between mt-2">
        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="processed + ' / ' + total + ' {{ __('posts processed') }}'"></p>
        <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400" x-text="percent + '%'"></p>
    </div>

    {{-- Media stage (chained after the SQL stage) --}}
    <template x-if="media">
        <div class="mt-4 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
            <x-lucide-image class="w-4 h-4 text-purple-500" />
            <span x-text="'{{ __('Media stage') }}: ' + (media.total - media.pending) + ' / ' + media.total + ' {{ __('posts') }}'"></span>
        </div>
    </template>

    {{-- Counters --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-5">
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-emerald-100 dark:border-emerald-900 text-center shadow-sm">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="imported"></p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Imported') }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-purple-100 dark:border-purple-900 text-center shadow-sm">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="migrated"></p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Media migrated') }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-amber-100 dark:border-amber-900 text-center shadow-sm">
            <p class="text-2xl font-bold text-amber-500 dark:text-amber-400" x-text="skipped"></p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Skipped') }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-red-100 dark:border-red-900 text-center shadow-sm">
            <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="failed"></p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Failed') }}</p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-orange-100 dark:border-orange-900 text-center shadow-sm">
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400" x-text="blocked"></p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wider font-semibold">{{ __('Blocked') }}</p>
        </div>
    </div>

    {{-- Polling error --}}
    <template x-if="error">
        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
            <p class="text-xs text-amber-700 dark:text-amber-400" x-text="'{{ __('Could not refresh progress') }}: ' + error"></p>
        </div>
    </template>

    {{-- Security findings --}}
    <template x-if="security.length > 0">
        <div class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
            <p class="text-sm font-semibold text-orange-700 dark:text-orange-400 mb-2 flex items-center gap-2">
                <x-lucide-shield-alert class="w-4 h-4" />
                <span x-text="'{{ __('Blocked by the security scanner') }} (' + security.length + ')'"></span>
            </p>
            <ul class="space-y-1 max-h-40 overflow-y-auto">
                <template x-for="(item, i) in security" :key="i">
                    <li class="text-xs text-orange-800 dark:text-orange-300 font-mono" x-text="'• ' + item"></li>
                </template>
            </ul>
        </div>
    </template>

    {{-- Failures --}}
    <template x-if="errors.length > 0">
        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-2">
                <x-lucide-alert-circle class="w-4 h-4" />
                <span x-text="'{{ __('Some posts were skipped due to errors') }} (' + errors.length + ')'"></span>
            </p>
            <ul class="space-y-1 max-h-40 overflow-y-auto">
                <template x-for="(item, i) in errors" :key="i">
                    <li class="text-xs text-amber-800 dark:text-amber-300 font-mono" x-text="'• ' + item"></li>
                </template>
            </ul>
        </div>
    </template>

    {{-- Reset --}}
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
