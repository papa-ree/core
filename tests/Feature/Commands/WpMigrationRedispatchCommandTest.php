<?php

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| core:wp-migration-redispatch
|--------------------------------------------------------------------------
|
| A batch row can outlive its jobs (cleared queue, FLUSHDB, worker that never
| started). This command re-arms the run so the operator does not have to
| re-upload the whole dump — imports are idempotent, so re-dispatching pending
| items is safe.
|
*/

if (! function_exists('wp_redispatch_make_run')) {
    /**
     * @param  array<int, array<string, mixed>>  $posts
     */
    function wp_redispatch_make_run(array $posts): WpMigrationRun
    {
        return app(WpMigrationRunService::class)->createSqlRun(
            'bale-1',
            'author-1',
            $posts,
            ['slug' => 'dindik']
        );
    }
}

if (! function_exists('wp_redispatch_batch_row')) {
    function wp_redispatch_batch_row(string $batchId, int $total = 198, int $pending = 198, bool $finished = false): void
    {
        DB::table('job_batches')->insert([
            'id' => $batchId,
            'name' => 'wp-import:test',
            'total_jobs' => $total,
            'pending_jobs' => $pending,
            'failed_jobs' => 0,
            'failed_job_ids' => json_encode([]),
            'options' => json_encode([]),
            'cancelled_at' => null,
            'created_at' => now()->timestamp,
            'finished_at' => $finished ? now()->timestamp : null,
        ]);
    }
}

beforeEach(function () {
    if (! Schema::hasTable('job_batches')) {
        $this->markTestSkipped('job_batches table is not available in this test database.');
    }
});

it('re-dispatches one job per pending item', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([
        ['ID' => '1', 'post_title' => 'A', 'post_content' => '<p>A</p>'],
        ['ID' => '2', 'post_title' => 'B', 'post_content' => '<p>B</p>'],
    ]);

    expect($run->batch_id)->toBeNull();

    $exitCode = Artisan::call('core:wp-migration-redispatch', ['run' => $run->id]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Dispatched 2 job(s)')
        ->and($run->fresh()->batch_id)->not->toBeNull();

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2);
});

it('accepts the short run prefix printed by the status table', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([['ID' => '1', 'post_title' => 'A']]);

    $exitCode = Artisan::call('core:wp-migration-redispatch', [
        'run' => substr((string) $run->id, 0, 8),
    ]);

    expect($exitCode)->toBe(0)
        ->and($run->fresh()->batch_id)->not->toBeNull();
});

it('does not dispatch items that are no longer pending', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([
        ['ID' => '1', 'post_title' => 'A'],
        ['ID' => '2', 'post_title' => 'B'],
    ]);

    WpMigrationItem::where('run_id', $run->id)->first()
        ->forceFill(['status' => WpMigrationItem::STATUS_IMPORTED])->save();

    Artisan::call('core:wp-migration-redispatch', ['run' => $run->id]);

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
});

it('refuses to re-dispatch a finished run without a flag', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([['ID' => '1', 'post_title' => 'A']]);
    $run->forceFill(['status' => 'finished', 'finished_at' => now()])->save();

    $exitCode = Artisan::call('core:wp-migration-redispatch', ['run' => $run->id]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('already finished');

    Bus::assertNothingBatched();
});

it('re-arms failed items with --reset', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([
        ['ID' => '1', 'post_title' => 'A'],
        ['ID' => '2', 'post_title' => 'B'],
    ]);

    WpMigrationItem::where('run_id', $run->id)->first()
        ->forceFill(['status' => WpMigrationItem::STATUS_FAILED])->save();

    $run->forceFill(['status' => 'finished', 'finished_at' => now()])->save();

    $exitCode = Artisan::call('core:wp-migration-redispatch', [
        'run' => $run->id,
        '--reset' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Reset 1 item(s)')
        ->and(WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_FAILED)->count())->toBe(0)
        ->and($run->fresh()->status)->toBe('running');

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2);
});

it('refuses while the existing batch is still unfinished, unless forced', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([['ID' => '1', 'post_title' => 'A']]);
    $batchId = (string) Str::uuid();
    wp_redispatch_batch_row($batchId, 198, 198);
    $run->forceFill(['batch_id' => $batchId])->save();

    $exitCode = Artisan::call('core:wp-migration-redispatch', ['run' => $run->id]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('still unfinished');

    Bus::assertNothingBatched();

    $exitCode = Artisan::call('core:wp-migration-redispatch', [
        'run' => $run->id,
        '--force' => true,
    ]);

    expect($exitCode)->toBe(0);
    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
});

it('dispatches nothing on --dry-run', function () {
    Bus::fake();

    $run = wp_redispatch_make_run([['ID' => '1', 'post_title' => 'A']]);

    $exitCode = Artisan::call('core:wp-migration-redispatch', [
        'run' => $run->id,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Dry run');

    Bus::assertNothingBatched();
    expect($run->fresh()->batch_id)->toBeNull();
});

it('fails when the run does not exist', function () {
    Bus::fake();

    $exitCode = Artisan::call('core:wp-migration-redispatch', [
        'run' => (string) Str::uuid(),
    ]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('not found');
});
