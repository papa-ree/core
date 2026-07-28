<?php

namespace Bale\Core\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Actions\CleanActivityLogAction;

class CleanActivityLogCommand extends Command
{
    protected $signature = 'core:clean-activity-log
        {--days= : Number of days to retain logs (default: config activitylog.clean_after_days)}
        {--log-name= : Only clean logs with this log name}';

    protected $description = 'Clean old activity log entries based on retention policy';

    public function handle(CleanActivityLogAction $cleaner): int
    {
        $days = (int) ($this->option('days') ?? config('activitylog.clean_after_days', 90));
        $logName = $this->option('log-name');

        $deleted = $cleaner->execute($days, $logName);

        $this->info("Deleted {$deleted} activity log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
