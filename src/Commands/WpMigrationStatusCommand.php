<?php

namespace Bale\Core\Commands;

use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * core:wp-migration-status
 *
 * Answers the only question that matters when a WordPress migration "does
 * nothing": did a run ever get created, and where are the jobs now?
 *
 * `jobs` is a queue table, **not** a log — a worker deletes each row once the
 * job has been processed, so an empty `jobs` table means either "not dispatched
 * yet" or "already finished". The authoritative tables are:
 *
 *   wp_migration_runs    one row per dispatched run
 *   wp_migration_items   one row per post (the unit of work)
 *   job_batches          batch progress (pending / failed / total)
 *   failed_jobs          exceptions raised by a wp-import / wp-media job
 *
 * Because `wp_migration_runs` is written *before* `Bus::batch()` runs, an empty
 * run table proves the import stopped in one of the pre-dispatch guards — this
 * command spells those out, so nobody has to guess from an empty `jobs` table.
 */
class WpMigrationStatusCommand extends Command
{
    protected $signature = 'core:wp-migration-status
        {--run= : Show the per-post detail of a single run UUID}
        {--bale= : Only list runs belonging to this BaleList UUID}
        {--limit=10 : How many recent runs to list}
        {--all-items : List every item of the run instead of the first 50}';

    protected $description = 'Show WordPress migration runs, per-post progress, and queue diagnostics';

    public function handle(): int
    {
        if (! Schema::hasTable('wp_migration_runs')) {
            $this->error('Table `wp_migration_runs` does not exist on the ['.config('database.default').'] connection.');
            $this->line('Run `php artisan migrate` on the app that owns the queue (the landlord/default database) first.');
            $this->line('Migration runs are tracked there, not in the tenant database.');

            return self::FAILURE;
        }

        $runId = (string) $this->option('run');

        if ($runId !== '') {
            $run = WpMigrationRun::find($runId);

            if (! $run) {
                $this->error("Migration run [{$runId}] not found.");

                return self::FAILURE;
            }

            $this->renderRun($run);
            $this->renderItems($run);
        } else {
            $this->renderRunList();
        }

        $this->renderQueueDiagnostics();

        return self::SUCCESS;
    }

    // -----------------------------------------------------------------------
    // Sections
    // -----------------------------------------------------------------------

    protected function renderRunList(): void
    {
        $query = WpMigrationRun::query()->latest();

        if ($baleId = (string) $this->option('bale')) {
            $query->where('bale_id', $baleId);
        }

        $runs = $query->limit(max(1, (int) $this->option('limit')))->get();

        if ($runs->isEmpty()) {
            $this->newLine();
            $this->warn('No rows in `wp_migration_runs` — no migration run was ever created.');
            $this->newLine();
            $this->line('A run row is written <options=bold>before</> the batch is dispatched, so an empty table means the');
            $this->line('import stopped in one of the pre-dispatch guards:');
            $this->newLine();
            $this->line('  • no destination Bale selected;');
            $this->line('  • no Author selected — the tenant usually has no user yet;');
            $this->line('  • the uploaded dump was rejected or could not be read;');
            $this->line('  • the dump contained no publishable post (wrong file or prefix).');
            $this->newLine();
            $this->line('The UI reports that reason as a red message above the form. From the command line,');
            $this->line('reproduce it without the queue and without a proxy in front:');
            $this->newLine();
            $this->line('  php artisan core:migrate-wp-sql --bale=<uuid> --author=<uuid> --file=<dump> --sync --limit=1');
            $this->newLine();

            return;
        }

        $counts = $this->countsByRun($runs->pluck('id')->all());
        $batches = $this->batchesById($runs->pluck('batch_id')->filter()->all());

        $this->newLine();
        $this->table(
            ['Run', 'Type', 'Status', 'Total', 'Pending', 'Done', 'Failed', 'Blocked', 'Batch', 'Started', 'Finished'],
            $runs->map(function (WpMigrationRun $run) use ($counts, $batches) {
                $c = $counts[$run->id] ?? [];

                return [
                    substr((string) $run->id, 0, 8),
                    (string) $run->type,
                    (string) $run->status,
                    (int) $run->total,
                    (int) ($c[WpMigrationItem::STATUS_PENDING] ?? 0),
                    (int) ($c[WpMigrationItem::STATUS_IMPORTED] ?? 0) + (int) ($c[WpMigrationItem::STATUS_MIGRATED] ?? 0),
                    (int) ($c[WpMigrationItem::STATUS_FAILED] ?? 0),
                    (int) ($c[WpMigrationItem::STATUS_BLOCKED] ?? 0),
                    $this->batchLabel($run->batch_id, $batches),
                    $this->formatTime($run->started_at),
                    $this->formatTime($run->finished_at),
                ];
            })->all()
        );

        $this->line('Per-post detail: php artisan core:wp-migration-status --run=<uuid>');
        $this->newLine();
    }

    protected function renderRun(WpMigrationRun $run): void
    {
        $counts = $this->countsByRun([$run->id])[$run->id] ?? [];
        $batches = $this->batchesById(array_filter([$run->batch_id]));
        $meta = $run->meta ?? [];

        $this->newLine();
        $this->info('Run '.$run->id);

        $this->table(['Field', 'Value'], [
            ['Type', (string) $run->type],
            ['Status', (string) $run->status],
            ['Bale', (string) $run->bale_id],
            ['Parent run', (string) ($run->parent_id ?: '—')],
            ['Total posts', (int) $run->total],
            ['Batch', $this->batchLabel($run->batch_id, $batches)],
            ['Slug', (string) ($meta['slug'] ?? '—')],
            ['File', (string) ($meta['file'] ?? '—')],
            ['Table prefix', (string) ($meta['table_prefix'] ?? '—')],
            ['Source', (string) ($meta['source'] ?? '—')],
            ['Author', (string) ($meta['author'] ?? '—')],
            ['Chained media run', array_key_exists('media_run_id', $meta)
                ? ((string) ($meta['media_run_id'] ?? 'none needed'))
                : 'not resolved yet'],
            ['Started', $this->formatTime($run->started_at)],
            ['Finished', $this->formatTime($run->finished_at)],
        ]);

        $rows = [];

        foreach ([
            WpMigrationItem::STATUS_PENDING,
            WpMigrationItem::STATUS_IMPORTED,
            WpMigrationItem::STATUS_MIGRATED,
            WpMigrationItem::STATUS_SKIPPED,
            WpMigrationItem::STATUS_FAILED,
            WpMigrationItem::STATUS_BLOCKED,
        ] as $status) {
            $rows[] = [$status, (int) ($counts[$status] ?? 0)];
        }

        $this->table(['Item status', 'Count'], $rows);
    }

    protected function renderItems(WpMigrationRun $run): void
    {
        $query = WpMigrationItem::where('run_id', $run->id)->orderBy('created_at');

        if (! $this->option('all-items')) {
            $query->limit(50);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->warn('This run has no items — the batch was never dispatched.');

            return;
        }

        $this->table(
            ['Item', 'WP post', 'Post', 'Status', 'Title', 'Note'],
            $items->map(function (WpMigrationItem $item) {
                $note = $item->result['error'] ?? $item->result['security'] ?? '';

                return [
                    substr((string) $item->id, 0, 8),
                    (string) ($item->wp_post_id ?: '—'),
                    $item->post_id ? substr((string) $item->post_id, 0, 8) : '—',
                    (string) $item->status,
                    Str::limit((string) ($item->result['title'] ?? '—'), 40) ?: '—',
                    Str::limit((string) $note, 60) ?: '—',
                ];
            })->all()
        );

        if (! $this->option('all-items') && $items->count() === 50) {
            $this->line('Showing the first 50 items — pass --all-items for the full list.');
        }

        $this->newLine();
    }

    /**
     * Explain the queue side: what is still waiting, what already ran, what blew up.
     *
     * The queue backend is whatever `queue.default` says — reading the `jobs`
     * table is only meaningful for `database`. On Redis (the usual production
     * setup) that table is always empty, so an operator debugging "nothing ran"
     * would be shown `0` while 396 jobs were actually waiting. Ask the queue
     * connection itself instead.
     */
    protected function renderQueueDiagnostics(): void
    {
        $driver = $this->queueDriver();
        $sqlQueue = (string) config('core.wp_migration.sql_queue', 'wp-import');
        $mediaQueue = (string) config('core.wp_migration.media_queue', 'wp-media');

        $sqlWaiting = $this->queueSize($sqlQueue);
        $mediaWaiting = $this->queueSize($mediaQueue);
        $unfinishedBatches = $this->tableCount('job_batches', fn ($q) => $q->whereNull('finished_at'));
        $failedCount = $this->failedJobCount($sqlQueue, $mediaQueue);

        $this->info(sprintf(
            'Queue diagnostics (driver: %s · queues: %s, %s)',
            $driver,
            $sqlQueue,
            $mediaQueue
        ));

        $this->table(['Check', 'Count', 'Meaning'], [
            ["queued [{$sqlQueue}]", $sqlWaiting, 'waiting for a worker'],
            ["queued [{$mediaQueue}]", $mediaWaiting, 'waiting for a worker'],
            ['job_batches (unfinished)', $unfinishedBatches, 'batches still in flight'],
            ['failed_jobs [wp-*]', $failedCount, 'exceptions raised by a job'],
        ]);

        $this->renderQueueAdvice($sqlQueue, $mediaQueue, $sqlWaiting, $mediaWaiting, $unfinishedBatches);

        $failed = $this->failedJobs($sqlQueue, $mediaQueue);

        if ($failed->isNotEmpty()) {
            $this->newLine();
            $this->error($failed->count().' failed job(s) on the WP queues — last 5:');

            foreach ($failed->take(5) as $job) {
                $this->line('  • ['.$job->queue.'] '.Str::limit((string) $job->exception, 160));
            }

            $this->line('  Retry them with: php artisan queue:retry all');
        }

        $this->newLine();
    }

    /**
     * Translate the counts into the one action the operator has to take.
     */
    protected function renderQueueAdvice(
        string $sqlQueue,
        string $mediaQueue,
        int|string $sqlWaiting,
        int|string $mediaWaiting,
        int|string $unfinishedBatches,
    ): void {
        $waiting = $this->sumQueueSizes($sqlWaiting, $mediaWaiting);

        if (is_int($waiting) && $waiting > 0) {
            $this->newLine();
            $this->comment('Jobs are waiting. A worker listening to the migration queues is required:');
            $this->line('  '.$this->workerCommand($sqlQueue, $mediaQueue));
            $this->line('  `queue:work` without --queue only reads `default`, which is how batches sit');
            $this->line('  at 0/198 for hours without a single error.');
        }

        if ($waiting === 0 && is_int($unfinishedBatches) && $unfinishedBatches > 0) {
            $this->newLine();
            $this->warn('There is an unfinished batch but nothing queued: those jobs are gone.');
            $this->line('  Usually a cleared queue (`queue:clear`, `queue:flush`, `FLUSHDB`) or a batch');
            $this->line('  whose jobs were never pushed. Re-arm the run with:');
            $this->line('  php artisan core:wp-migration-redispatch <run-uuid>');
        }

        if ($warning = $this->retryAfterWarning()) {
            $this->newLine();
            $this->error($warning);
        }
    }

    /**
     * Redis puts a job that is still running after `retry_after` seconds back on
     * the queue, so the media job (900s timeout) would be executed twice on the
     * default 90s — silently duplicating downloads and CDN uploads.
     */
    protected function retryAfterWarning(): ?string
    {
        $driver = $this->queueDriver();
        $retryAfter = config("queue.connections.{$driver}.retry_after");

        if ($retryAfter === null) {
            return null;
        }

        $longest = max(
            $this->jobTimeout(ImportWpPostJob::class),
            $this->jobTimeout(MigrateWpMediaPostJob::class),
        );

        if ($longest <= 0 || (int) $retryAfter >= $longest) {
            return null;
        }

        $env = match ($driver) {
            'redis' => 'REDIS_QUEUE_RETRY_AFTER',
            'database' => 'DB_QUEUE_RETRY_AFTER',
            default => "queue.connections.{$driver}.retry_after",
        };

        return sprintf(
            'retry_after (%ds) is shorter than the longest migration job (%ds): a job still running is re-queued and executed twice — set %s=%d.',
            (int) $retryAfter,
            $longest,
            $env,
            $longest + 60
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Item counts grouped by run → status, in a single query.
     *
     * @param  array<int, string>  $runIds
     * @return array<string, array<string, int>>
     */
    protected function countsByRun(array $runIds): array
    {
        if ($runIds === []) {
            return [];
        }

        return WpMigrationItem::whereIn('run_id', $runIds)
            ->select('run_id', 'status', DB::raw('count(*) as aggregate'))
            ->groupBy('run_id', 'status')
            ->get()
            ->groupBy('run_id')
            ->map(fn ($rows) => $rows->pluck('aggregate', 'status')->map(fn ($v) => (int) $v)->all())
            ->all();
    }

    /**
     * @param  array<int, string>  $batchIds
     * @return array<string, object>
     */
    protected function batchesById(array $batchIds): array
    {
        if ($batchIds === [] || ! Schema::hasTable('job_batches')) {
            return [];
        }

        return DB::table('job_batches')
            ->whereIn('id', $batchIds)
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * @param  array<string, object>  $batches
     */
    protected function batchLabel(?string $batchId, array $batches): string
    {
        if (! $batchId) {
            return '—';
        }

        $batch = $batches[$batchId] ?? null;

        if (! $batch) {
            return 'missing';
        }

        $processed = (int) ($batch->total_jobs ?? 0) - (int) ($batch->pending_jobs ?? 0);
        $label = $processed.'/'.(int) ($batch->total_jobs ?? 0);

        if ((int) ($batch->failed_jobs ?? 0) > 0) {
            $label .= ' ('.$batch->failed_jobs.' failed)';
        }

        return $label.(($batch->finished_at ?? null) ? ' done' : '');
    }

    protected function tableCount(string $table, ?callable $filter = null): int|string
    {
        if (! Schema::hasTable($table)) {
            return 'n/a';
        }

        $query = DB::table($table);

        if ($filter) {
            $filter($query);
        }

        return (int) $query->count();
    }

    /**
     * Number of jobs waiting on the given queue, asked from the real backend.
     *
     * `size()` is part of the queue contract, so this works for redis, database,
     * sqs, … A backend that is unreachable reports `n/a` instead of a misleading
     * zero.
     */
    protected function queueSize(string $queue): int|string
    {
        if ($this->queueDriver() === 'null') {
            return 0;
        }

        try {
            return (int) Queue::connection($this->queueDriver())->size($queue);
        } catch (\Throwable) {
            return 'n/a';
        }
    }

    protected function sumQueueSizes(int|string ...$sizes): int|string|null
    {
        $total = 0;

        foreach ($sizes as $size) {
            if (! is_int($size)) {
                return null;
            }

            $total += $size;
        }

        return $total;
    }

    protected function queueDriver(): string
    {
        return (string) config('queue.default');
    }

    protected function workerCommand(string $sqlQueue, string $mediaQueue): string
    {
        if (in_array($this->queueDriver(), ['sync', 'null'], true)) {
            return 'php artisan queue:work --queue='.$sqlQueue.','.$mediaQueue.' (driver is '.$this->queueDriver().')';
        }

        return sprintf(
            'php artisan queue:work %s --queue=%s,%s',
            $this->queueDriver(),
            $sqlQueue,
            $mediaQueue
        );
    }

    /**
     * Default `$timeout` of a job class without instantiating it (the jobs need
     * constructor arguments).
     */
    protected function jobTimeout(string $jobClass): int
    {
        return (int) (get_class_vars($jobClass)['timeout'] ?? 0);
    }

    protected function failedJobCount(string $sqlQueue, string $mediaQueue): int|string
    {
        if (! $this->failedJobsUseDatabase()) {
            return 'n/a';
        }

        if (! Schema::hasTable('failed_jobs')) {
            return 'n/a';
        }

        return (int) DB::table('failed_jobs')
            ->whereIn('queue', [$sqlQueue, $mediaQueue])
            ->count();
    }

    /** @return Collection<int, object> */
    protected function failedJobs(string $sqlQueue, string $mediaQueue): Collection
    {
        if (! $this->failedJobsUseDatabase() || ! Schema::hasTable('failed_jobs')) {
            return collect();
        }

        return DB::table('failed_jobs')
            ->whereIn('queue', [$sqlQueue, $mediaQueue])
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * Failed jobs live in `failed_jobs` only when the failed provider stores
     * them in the database (the default, also used for redis queues).
     */
    protected function failedJobsUseDatabase(): bool
    {
        return str_starts_with((string) config('queue.failed.driver', 'database-uuids'), 'database');
    }

    protected function formatTime(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        return (string) $value;
    }
}
