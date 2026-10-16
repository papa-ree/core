<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Bus\Batch;

/**
 * FinalizeWpRunJob
 *
 * Batch completion callback for the media stage: marks the media run as
 * finished so the status endpoint can stop polling.
 */
class FinalizeWpRunJob
{
    public function __construct(protected string $runId) {}

    public function __invoke(?Batch $batch = null): void
    {
        $run = WpMigrationRun::find($this->runId);

        if ($run) {
            app(WpMigrationRunService::class)->finish($run);
        }
    }
}
