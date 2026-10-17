<?php

use Bale\Core\Jobs\Wp\FinalizeWpRunJob;
use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Jobs\Wp\StartWpMediaRunJob;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| Batching contract for the WordPress migration jobs
|--------------------------------------------------------------------------
|
| `Bus::batch()` validates every job in `PendingBatch::__construct()` and throws
| `RuntimeException` unless the job uses the Batchable trait. That guard is
| skipped entirely by `Bus::fake()`, because `BusFake::batch()` returns a
| `PendingBatchFake` whose constructor overrides the parent one — so a trait-less
| batched job is invisible to the rest of this suite.
|
| These tests therefore build the *real* PendingBatch, which is the only way to
| catch a regression here.
|
*/

it('keeps the Batchable trait on every batched wp job', function () {
    expect(class_uses_recursive(ImportWpPostJob::class))->toHaveKey(Batchable::class)
        ->and(class_uses_recursive(MigrateWpMediaPostJob::class))->toHaveKey(Batchable::class);
});

it('accepts both wp jobs inside a real PendingBatch', function () {
    $batch = new PendingBatch(app(), new Collection([
        new ImportWpPostJob('run-1', 'item-1'),
        new MigrateWpMediaPostJob('run-1', 'item-1'),
    ]));

    expect($batch->jobs)->toHaveCount(2)
        ->and($batch->jobs->first())->toBeInstanceOf(ImportWpPostJob::class);
});

it('accepts the invokable batch callbacks without requiring Batchable', function () {
    // The completion callbacks are plain invokable objects, not queued jobs:
    // `Batch::invokeHandlerCallback()` calls them with `($batch, $e)`. They must
    // not be dragged into the Batchable requirement.
    $batch = new PendingBatch(app(), new Collection([
        new ImportWpPostJob('run-1', 'item-1'),
    ]));

    $batch->then(new StartWpMediaRunJob('run-1'))
        ->finally(new StartWpMediaRunJob('run-1'));

    expect($batch->thenCallbacks())->toHaveCount(1)
        ->and($batch->finallyCallbacks())->toHaveCount(1);
});

it('invokes the completion callbacks with the batch and an optional exception', function () {
    // Mirrors Batch::invokeHandlerCallback(): $handler($batch, $e) — two arguments.
    expect((new StartWpMediaRunJob('run-1'))->__invoke(null, null))->toBeNull()
        ->and((new FinalizeWpRunJob('run-1'))->__invoke(null, new RuntimeException('boom')))->toBeNull();
});
