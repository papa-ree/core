<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Bus\Batch;

/**
 * StartWpMediaRunJob
 *
 * Batch completion callback for the SQL import stage. Runs inside the queue
 * worker once every import job has finished; it marks the SQL run as done and
 * starts the media stage for the imported posts (the automatic chain).
 *
 * Implemented as an invokable object (not a closure) so it can be serialized
 * safely inside the batch options.
 */
class StartWpMediaRunJob
{
    public function __construct(protected string $sqlRunId) {}

    public function __invoke(?Batch $batch = null): void
    {
        app(WpMigrationRunService::class)->chainMedia($this->sqlRunId);
    }
}
