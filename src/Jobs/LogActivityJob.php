<?php

namespace Bale\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Models\Activity;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(
        public array $attributes,
    ) {}

    public function handle(): void
    {
        $modelClass = config('activitylog.activity_model', Activity::class);

        $modelClass::create($this->attributes);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('LogActivityJob failed: '.$exception->getMessage());
    }
}
