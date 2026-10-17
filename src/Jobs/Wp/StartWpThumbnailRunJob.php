<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Bus\Batch;
use Throwable;

/**
 * StartWpThumbnailRunJob
 *
 * Batch completion callback for the media stage. Runs inside the queue worker
 * once every media job has finished; it marks the media run as done and starts
 * the thumbnail stage for the parent SQL run (the second link of the chain:
 * SQL → media → thumbnail).
 *
 * It replaces {@see FinalizeWpRunJob} on the media batch precisely because that
 * finalization is no longer the end of the chain.
 *
 * Implemented as an invokable object (not a closure) so it can be serialized
 * safely inside the batch options. It is a *callback*, not a queued job, so it
 * deliberately does NOT use the Batchable trait — see {@see StartWpMediaRunJob}.
 *
 * Registered as both `then` and `finally` by {@see WpMigrationRunService::dispatchMediaBatch()},
 * because `then` only fires when the last job to finish succeeded. Both
 * `finish()` and `chainThumbnails()` are idempotent, so a double invocation is
 * harmless.
 *
 * When the media run was started manually (`core:migrate-wp-media`), it has no
 * parent SQL run and this callback only finalizes the media run.
 */
class StartWpThumbnailRunJob
{
    public function __construct(protected string $mediaRunId) {}

    public function __invoke(?Batch $batch = null, ?Throwable $e = null): void
    {
        $run = WpMigrationRun::find($this->mediaRunId);

        if (! $run) {
            return;
        }

        $service = app(WpMigrationRunService::class);
        $service->finish($run);

        if ($run->parent_id) {
            $service->chainThumbnails((string) $run->parent_id);
        }
    }
}
