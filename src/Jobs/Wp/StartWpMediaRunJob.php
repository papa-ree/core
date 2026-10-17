<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Bus\Batch;
use Throwable;

/**
 * StartWpMediaRunJob
 *
 * Batch completion callback for the SQL import stage. Runs inside the queue
 * worker once every import job has finished; it marks the SQL run as done and
 * starts the media stage for the imported posts (the automatic chain).
 *
 * Implemented as an invokable object (not a closure) so it can be serialized
 * safely inside the batch options. It is a *callback*, not a queued job, so it
 * deliberately does NOT use the Batchable trait — `PendingBatch` only requires
 * that trait for the jobs inside the batch, and `Batch::invokeHandlerCallback()`
 * calls this with `($batch, $e)`.
 *
 * Registered as both `then` and `finally` by {@see WpMigrationRunService::dispatchSqlBatch()},
 * because `then` only fires when the last job to finish succeeded. `chainMedia()`
 * is idempotent, so a double invocation is harmless.
 */
class StartWpMediaRunJob
{
    public function __construct(protected string $sqlRunId) {}

    public function __invoke(?Batch $batch = null, ?Throwable $e = null): void
    {
        app(WpMigrationRunService::class)->chainMedia($this->sqlRunId);
    }
}
