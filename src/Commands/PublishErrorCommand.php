<?php

namespace Bale\Core\Commands;

use Illuminate\Console\Command;

class PublishErrorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:publish-error {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish error views from Bale Core to the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing Bale Core Error Views...');

        $params = [
            '--tag' => 'core:error-views',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        $this->info('Bale Core Error Views published successfully!');

        return self::SUCCESS;
    }
}
