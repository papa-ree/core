<?php

namespace Bale\Core\Commands;

use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Support\Cdn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * core:migrate-wp-media
 *
 * Second stage of the WordPress → Bale CMS import. Scans one tenant database
 * for posts whose EditorJS `content` still contains media placeholders
 * (created by {@see \Bale\Core\Services\PostCleanerService}) and migrates the
 * referenced WordPress media into the active storage disk / CDN.
 *
 * Usage:
 *   php artisan core:migrate-wp-media --bale=<uuid> --wp-base=https://dindik.ponorogo.go.id
 *   php artisan core:migrate-wp-media --bale=<uuid> --post=<post-uuid>
 *   php artisan core:migrate-wp-media --bale=<uuid> --dry-run
 */
class MigrateWpMediaCommand extends Command
{
    protected $signature = 'core:migrate-wp-media
        {--bale= : BaleList UUID (tenant) whose posts should be processed}
        {--slug= : Override organization slug (storage/CDN directory); defaults to the BaleList slug}
        {--wp-base= : Base URL of the legacy WordPress site, used to resolve relative media URLs}
        {--source-dir= : Local path to a copied wp-content (or uploads) directory to read media from, instead of HTTP}
        {--post= : Only migrate a single post ID}
        {--limit=0 : Maximum number of posts to process (0 = all)}
        {--dry-run : Report what would change without writing to the database}';

    protected $description = 'Migrate WordPress media referenced by EditorJS placeholders into Bale storage/CDN';

    public function handle(MediaMigrationService $service): int
    {
        $baleId = (string) $this->option('bale');

        if ($baleId === '') {
            $this->error('The --bale option is required (BaleList UUID).');

            return self::FAILURE;
        }

        $baleClass = 'Paparee\\Rakaca\\Models\\BaleList';
        if (! class_exists($baleClass)) {
            $this->error('BaleList model is not available in this application.');

            return self::FAILURE;
        }

        $bale = $baleClass::find($baleId);
        if (! $bale) {
            $this->error("BaleList [{$baleId}] not found.");

            return self::FAILURE;
        }

        $slug = (string) ($this->option('slug') ?: $bale->slug);
        if ($slug === '') {
            $this->error('Unable to determine organization slug. Pass --slug explicitly.');

            return self::FAILURE;
        }

        // Make Cdn::url() resolve to the tenant we are migrating.
        Cdn::resolveOrganizationSlugUsing(fn () => $slug);

        if ($wpBase = $this->option('wp-base')) {
            $service->setBaseUrl($wpBase);
        }

        if ($sourceDir = $this->option('source-dir')) {
            $service->setSourceDir($sourceDir);
        }

        $connection = $this->setupTenantConnection($bale);

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Failed to connect to tenant database: '.$e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->line(sprintf(
            'Tenant: <info>%s</info> (%s) | slug: <info>%s</info> | disk: <info>%s</info>%s',
            $bale->name,
            $bale->database_name,
            $slug,
            $service->disk(),
            $dryRun ? ' | <comment>DRY RUN</comment>' : ''
        ));

        $query = DB::connection($connection)
            ->table('posts')
            ->select('id', 'title', 'content')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('content', 'like', '%image-placeholder%')
                    ->orWhere('content', 'like', '%media-placeholder%');
            })
            ->orderBy('created_at');

        if ($postId = $this->option('post')) {
            $query->where('id', $postId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->info('No posts with media placeholders found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} post(s) with media placeholders.");

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $totals = ['migrated' => 0, 'failed' => 0, 'posts' => 0];
        $failures = [];

        foreach ($posts as $post) {
            $bar->advance();

            $content = is_string($post->content) ? json_decode($post->content, true) : (array) $post->content;

            if (! is_array($content)) {
                continue;
            }

            $result = $service->migrateContent($content, $slug);

            $totals['migrated'] += $result['migrated'];
            $totals['failed'] += $result['failed'];

            foreach ($result['errors'] as $error) {
                $failures[] = Str::limit($post->title, 45).': '.$error;
            }

            if ($result['migrated'] > 0) {
                $totals['posts']++;

                if (! $dryRun) {
                    DB::connection($connection)
                        ->table('posts')
                        ->where('id', $post->id)
                        // Intentionally do NOT touch updated_at so the original
                        // WordPress timestamps are preserved.
                        ->update([
                            'content' => json_encode($result['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Posts scanned', $posts->count()],
                ['Posts updated', $dryRun ? $totals['posts'].' (dry-run)' : $totals['posts']],
                ['Media migrated', $totals['migrated']],
                ['Media failed', $totals['failed']],
            ]
        );

        if (! empty($failures)) {
            $this->newLine();
            $this->warn('Failures (placeholders kept for a later retry):');
            foreach (array_slice($failures, 0, 25) as $failure) {
                $this->line('  • '.$failure);
            }
            if (count($failures) > 25) {
                $this->line('  … and '.(count($failures) - 25).' more.');
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('Dry run complete — no database changes were made.');
        }

        return self::SUCCESS;
    }

    /**
     * Configure a runtime database connection for a specific BaleList
     * (mirrors \Bale\Core\Livewire\Pages\WpTools\SqlMigrator\Index).
     */
    protected function setupTenantConnection(object $bale): string
    {
        $connectionName = 'bale_wp_media_'.str_replace('-', '_', (string) $bale->id);

        config([
            "database.connections.{$connectionName}" => [
                'driver' => 'mysql',
                'host' => $bale->database_host,
                'database' => $bale->database_name,
                'username' => $bale->database_username,
                'password' => $bale->database_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ],
        ]);

        return $connectionName;
    }
}
