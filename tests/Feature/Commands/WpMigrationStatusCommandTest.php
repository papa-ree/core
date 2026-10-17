<?php

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| core:wp-migration-status
|--------------------------------------------------------------------------
|
| `jobs` is a queue table, not a log: a worker deletes each row it processed,
| so an empty `jobs` table is ambiguous. These tests pin down what the command
| must tell an operator in both cases — "nothing was ever dispatched" and
| "here is exactly where the run is now".
|
| Output is asserted through Artisan::output() rather than
| `expectsOutputToContain()`: the latter matches a single `doWrite` chunk, and
| one table row carries several of the substrings we care about, so only the
| first registered matcher would ever be satisfied.
|
*/

if (! function_exists('wp_status_output')) {
    /**
     * @param  array<string, mixed>  $args
     * @return array{0: int, 1: string}
     */
    function wp_status_output(array $args = []): array
    {
        $exitCode = Artisan::call('core:wp-migration-status', $args);

        return [$exitCode, Artisan::output()];
    }
}

if (! function_exists('wp_status_make_run')) {
    /**
     * @param  array<string, mixed>  $overrides
     */
    function wp_status_make_run(array $overrides = []): WpMigrationRun
    {
        return WpMigrationRun::create(array_merge([
            'bale_id' => 'bale-1',
            'type' => 'sql',
            'status' => 'running',
            'total' => 1,
            'started_at' => now(),
            'meta' => ['slug' => 'dindik', 'file' => 'wp_posts_only.txt', 'author' => 'author-1'],
        ], $overrides));
    }
}

it('explains the pre-dispatch guards when no run exists yet', function () {
    expect(WpMigrationRun::count())->toBe(0);

    [$exitCode, $output] = wp_status_output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('no migration run was ever created')
        ->and($output)->toContain('no Author selected')
        ->and($output)->toContain('core:migrate-wp-sql');
});

it('lists recent runs with their per-status item counts', function () {
    $run = wp_status_make_run(['total' => 3]);

    foreach (['imported', 'imported', 'pending'] as $index => $status) {
        WpMigrationItem::create([
            'run_id' => $run->id,
            'wp_post_id' => (string) ($index + 1),
            'status' => $status,
        ]);
    }

    [$exitCode, $output] = wp_status_output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain(substr((string) $run->id, 0, 8))
        ->and($output)->toContain('Pending')
        ->and($output)->toContain('Done')
        ->and($output)->toContain('Blocked');
});

it('shows the run meta and the per-post detail for a single run', function () {
    $run = wp_status_make_run();

    WpMigrationItem::create([
        'run_id' => $run->id,
        'wp_post_id' => '42',
        'post_id' => (string) Str::uuid(),
        'status' => WpMigrationItem::STATUS_FAILED,
        'result' => ['title' => 'Berita Pertama', 'error' => 'boom'],
    ]);

    [$exitCode, $output] = wp_status_output(['--run' => $run->id]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('wp_posts_only.txt')
        ->and($output)->toContain('Berita Pertama')
        ->and($output)->toContain('boom')
        ->and($output)->toContain('not resolved yet');
});

it('fails when the requested run does not exist', function () {
    [$exitCode, $output] = wp_status_output(['--run' => (string) Str::uuid()]);

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('not found');
});

it('scopes the run list to a single bale when asked', function () {
    // Explicit ids: UUIDv7 values generated in the same millisecond share their
    // leading characters, and the run list prints only the first 8.
    $first = wp_status_make_run([
        'id' => 'aaaaaaaa-0000-7000-8000-000000000001',
        'bale_id' => 'bale-1',
        'total' => 0,
    ]);
    $second = wp_status_make_run([
        'id' => 'bbbbbbbb-0000-7000-8000-000000000002',
        'bale_id' => 'bale-2',
        'total' => 0,
    ]);

    [$exitCode, $output] = wp_status_output(['--bale' => 'bale-2']);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain(substr((string) $second->id, 0, 8))
        ->and($output)->not->toContain(substr((string) $first->id, 0, 8));
});

it('asks the real queue backend instead of the jobs table', function () {
    // On Redis the `jobs` table is always empty, so reading it printed "0"
    // while 396 jobs were waiting. The diagnostics must follow queue.default.
    Queue::fake();
    config(['queue.default' => 'redis']);

    [$exitCode, $output] = wp_status_output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('driver: redis')
        ->and($output)->toContain('queued [wp-import]')
        ->and($output)->not->toContain('connection: mysql');
});

it('explains that an unfinished batch with an empty queue means lost jobs', function () {
    Queue::fake();
    config(['queue.default' => 'redis']);

    $batchId = (string) Str::uuid();

    DB::table('job_batches')->insert([
        'id' => $batchId,
        'name' => 'wp-import:lost',
        'total_jobs' => 198,
        'pending_jobs' => 198,
        'failed_jobs' => 0,
        'failed_job_ids' => json_encode([]),
        'options' => json_encode([]),
        'cancelled_at' => null,
        'created_at' => now()->timestamp,
        'finished_at' => null,
    ]);

    wp_status_make_run(['batch_id' => $batchId]);

    [$exitCode, $output] = wp_status_output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('nothing queued')
        ->and($output)->toContain('core:wp-migration-redispatch')
        ->and($output)->toContain('0/198');
});

it('warns when retry_after is shorter than the longest migration job', function () {
    // Redis re-queues a job that is still running after retry_after seconds.
    // The media job has a 900s timeout, so the default 90s executes it twice.
    Queue::fake();
    config([
        'queue.default' => 'redis',
        'queue.connections.redis.retry_after' => 90,
    ]);

    [$exitCode, $output] = wp_status_output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('retry_after (90s)')
        ->and($output)->toContain('900')
        ->and($output)->toContain('REDIS_QUEUE_RETRY_AFTER=960');
});

it('stays quiet when retry_after already covers the job timeout', function () {
    Queue::fake();
    config([
        'queue.default' => 'redis',
        'queue.connections.redis.retry_after' => 960,
    ]);

    [, $output] = wp_status_output();

    expect($output)->not->toContain('retry_after');
});

it('names the env variable that belongs to the active queue driver', function () {
    Queue::fake();
    config([
        'queue.default' => 'database',
        'queue.connections.database.retry_after' => 90,
    ]);

    [, $output] = wp_status_output();

    expect($output)->toContain('DB_QUEUE_RETRY_AFTER=960');
});
