<?php

namespace Bale\Core\Commands;

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * core:wp-migration-redispatch
 *
 * A run and its `job_batches` row can survive while its jobs disappear — a
 * cleared queue, a `FLUSHDB`, a container that never started a worker, or a
 * batch whose jobs were never pushed. The batch then sits at 0/198 forever and
 * neither `queue:work` nor a restart will help, because there is nothing left
 * in the queue to consume.
 *
 * This command re-arms such a run: it dispatches one job per pending item again
 * (imports are idempotent — the post id is a UUIDv5 of the WordPress id and the
 * insert uses `insertOrIgnore`), optionally resetting failed items first.
 */
class WpMigrationRedispatchCommand extends Command
{
    protected $signature = 'core:wp-migration-redispatch
        {run : Run UUID, or a unique prefix as printed by core:wp-migration-status}
        {--reset : Reset failed items back to pending before dispatching (and clear the run status)}
        {--include-skipped : With --reset, also re-arm items that were skipped by a duplicate run}
        {--force : Dispatch even when the run is finished or a batch job is still in flight}
        {--dry-run : Show what would be dispatched without touching the queue}';

    protected $description = 'Re-dispatch the queued jobs of a WordPress migration run whose jobs were lost';

    public function handle(WpMigrationRunService $service): int
    {
        $run = $this->resolveRun((string) $this->argument('run'));

        if (! $run) {
            return self::FAILURE;
        }

        if (! in_array($run->type, ['sql', 'media', 'thumbnail'], true)) {
            $this->error("Run [{$run->id}] has unsupported type [{$run->type}].");

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Run <info>%s</info> · %s · status <info>%s</info> · total %d · batch %s',
            $run->id,
            $run->type,
            $run->status,
            (int) $run->total,
            $run->batch_id ?: '—'
        ));

        if ($batch = $this->inFlightBatch($run)) {
            $processed = (int) $batch->total_jobs - (int) $batch->pending_jobs;

            if (! $this->option('force')) {
                $this->error(sprintf(
                    'Batch %s is still unfinished (%d/%d). Its jobs may simply be waiting for a worker.',
                    $run->batch_id,
                    $processed,
                    (int) $batch->total_jobs
                ));
                $this->line('Check the worker first: php artisan core:wp-migration-status');
                $this->line('Use --force only if you are sure those jobs are gone.');

                return self::FAILURE;
            }

            $this->warn('A batch is still unfinished — dispatching again because of --force.');
        }

        if ($run->status === 'finished' && ! $this->option('force') && ! $this->option('reset')) {
            $this->error('This run is already finished.');
            $this->line('Pass --reset to re-arm its failed items, or --force to import every pending item again.');

            return self::FAILURE;
        }

        if ($this->option('reset')) {
            $this->resetItems($run);
        }

        $pending = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_PENDING)
            ->count();

        if ($pending === 0) {
            $this->warn('No pending item to dispatch.');

            $failed = WpMigrationItem::where('run_id', $run->id)
                ->where('status', WpMigrationItem::STATUS_FAILED)
                ->count();

            if ($failed > 0) {
                $this->line("{$failed} failed item(s) — pass --reset to retry them.");
            }

            return self::SUCCESS;
        }

        $this->line("Pending items: <info>{$pending}</info>");

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing was dispatched.');
            $this->line('Worker: '.$this->workerCommand());

            return self::SUCCESS;
        }

        if ($run->type === 'sql') {
            $service->dispatchSqlBatch($run);
        } elseif ($run->type === 'media') {
            $service->dispatchMediaBatch($run);
        } else {
            $service->dispatchThumbnailBatch($run);
        }

        $run->refresh();

        $this->info(sprintf('Dispatched %d job(s) into batch %s.', $pending, $run->batch_id ?: '?'));

        $this->newLine();
        $this->line('A worker listening to the migration queues is required:');
        $this->line('  '.$this->workerCommand());
        $this->line('Watch it with: php artisan core:wp-migration-status --run='.$run->id);

        return self::SUCCESS;
    }

    /**
     * Accept a full UUID or the 8-character prefix printed by the status command.
     */
    protected function resolveRun(string $id): ?WpMigrationRun
    {
        $id = trim($id);

        if ($id === '') {
            $this->error('The run UUID is required.');

            return null;
        }

        if ($run = WpMigrationRun::find($id)) {
            return $run;
        }

        $matches = WpMigrationRun::where('id', 'like', $id.'%')->limit(5)->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->isEmpty()) {
            $this->error("Run [{$id}] not found.");

            return null;
        }

        $this->error("Run prefix [{$id}] is ambiguous:");

        foreach ($matches as $match) {
            $this->line('  • '.$match->id.'  ('.$match->type.', '.$match->status.')');
        }

        return null;
    }

    protected function inFlightBatch(WpMigrationRun $run): ?object
    {
        if (! $run->batch_id || ! Schema::hasTable('job_batches')) {
            return null;
        }

        $batch = DB::table('job_batches')->where('id', $run->batch_id)->first();

        if (! $batch || ($batch->finished_at ?? null)) {
            return null;
        }

        return $batch;
    }

    protected function resetItems(WpMigrationRun $run): void
    {
        $statuses = [WpMigrationItem::STATUS_FAILED];

        if ($this->option('include-skipped')) {
            $statuses[] = WpMigrationItem::STATUS_SKIPPED;
        }

        $count = WpMigrationItem::where('run_id', $run->id)
            ->whereIn('status', $statuses)
            ->update(['status' => WpMigrationItem::STATUS_PENDING, 'updated_at' => now()]);

        $run->forceFill(['status' => 'running', 'finished_at' => null])->save();

        $this->line("Reset {$count} item(s) back to pending.");
    }

    protected function workerCommand(): string
    {
        $driver = (string) config('queue.default');
        $sqlQueue = (string) config('core.wp_migration.sql_queue', 'wp-import');
        $mediaQueue = (string) config('core.wp_migration.media_queue', 'wp-media');

        if (in_array($driver, ['sync', 'null'], true)) {
            return "php artisan queue:work --queue={$sqlQueue},{$mediaQueue} (driver is {$driver})";
        }

        return "php artisan queue:work {$driver} --queue={$sqlQueue},{$mediaQueue}";
    }
}
