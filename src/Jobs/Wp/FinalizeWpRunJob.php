<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Bus\Batch;
use Throwable;

/**
 * FinalizeWpRunJob
 *
 * Batch completion callback for the media stage: marks the media run as
 * finished so the status endpoint can stop polling.
 *
 * A *callback*, not a queued job — see {@see StartWpMediaRunJob} for why it
 * intentionally has no Batchable trait. Registered as both `then` and `finally`
 * so a failing last job cannot leave the run stuck in `running`.
 */
class FinalizeWpRunJob
{
    public function __construct(protected string $runId) {}

    public function __invoke(?Batch $batch = null, ?Throwable $e = null): void
    {
        $run = WpMigrationRun::find($this->runId);

        if ($run) {
            app(WpMigrationRunService::class)->finish($run);
        }
    }
}
