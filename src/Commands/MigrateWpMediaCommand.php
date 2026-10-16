<?php

namespace Bale\Core\Commands;

use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Services\MediaSecurityScanner;
use Bale\Core\Services\PostCleanerService;
use Bale\Core\Services\WpMigrationRunService;
use Bale\Core\Support\Cdn;
use Bale\Core\Support\WpTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * core:migrate-wp-media
 *
 * Second stage of the WordPress → Bale CMS import. Scans one tenant database
 * for posts whose EditorJS `content` still contains media placeholders
 * (created by {@see PostCleanerService}) and migrates the
 * referenced WordPress media into the active storage disk / CDN.
 *
 * By default the work is dispatched to the queue — one job per post on the
 * `wp-media` queue — so a single large post can never time out the run:
 *
 *   php artisan core:migrate-wp-media --bale=<uuid> --wp-base=https://example.test
 *   # then, in another terminal:
 *   php artisan queue:work --queue=wp-import,wp-media
 *
 * Pass `--sync` (or `--dry-run`, or `--post`) to run inline instead.
 *
 * Every file is inspected by {@see MediaSecurityScanner}
 * before it is stored; files carrying backdoors, embedded code or hidden links
 * are rejected (the placeholder is kept so the run stays retryable) and can be
 * copied to `--quarantine-dir` for manual review.
 */
class MigrateWpMediaCommand extends Command
{
    protected $signature = 'core:migrate-wp-media
        {--bale= : BaleList UUID (tenant) whose posts should be processed}
        {--slug= : Override organization slug (storage/CDN directory); defaults to the BaleList slug}
        {--wp-base= : Base URL of the legacy WordPress site, used to resolve relative media URLs}
        {--source-dir= : Local path to a copied wp-content (or uploads) directory to read media from, instead of HTTP}
        {--post= : Only migrate a single post ID (runs inline)}
        {--limit=0 : Maximum number of posts to process (0 = all)}
        {--quarantine-dir= : Directory to copy files rejected by the security scanner into (for manual review)}
        {--strict : Also block files with low/medium security findings, not just high}
        {--queue= : Queue name for the dispatched jobs (default: wp-media)}
        {--sync : Run inline instead of dispatching queued jobs}
        {--dry-run : Report what would change without writing to the database (runs inline)}';

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

        $connection = app(WpTenant::class)->connection($bale->id);

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Failed to connect to tenant database: '.$e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $inline = $dryRun || (bool) $this->option('sync') || (bool) $this->option('post');

        $mode = $inline
            ? ($dryRun ? 'DRY RUN' : 'INLINE')
            : 'QUEUED (one job per post)';

        config(['core.wp_migration.media_queue' => (string) ($this->option('queue') ?: config('core.wp_migration.media_queue'))]);

        $this->line(sprintf(
            'Tenant: <info>%s</info> (%s) | slug: <info>%s</info> | disk: <info>%s</info> | mode: <info>%s</info>%s',
            $bale->name,
            $bale->database_name,
            $slug,
            $service->disk(),
            $mode,
            $this->option('strict') ? ' | <comment>STRICT SCAN</comment>' : ''
        ));

        if (! $inline) {
            return $this->dispatchRun($bale, $slug, $limit);
        }

        return $this->runInline($service, $bale, $slug, $connection, $dryRun, $limit);
    }

    /**
     * Dispatch the media stage as a queue batch (one job per post).
     */
    protected function dispatchRun(object $bale, string $slug, int $limit): int
    {
        $meta = [
            'slug' => $slug,
            'wp_base' => $this->option('wp-base') ?: null,
            'source_dir' => $this->option('source-dir') ?: null,
            'quarantine_dir' => $this->option('quarantine-dir') ?: null,
            'strict' => (bool) $this->option('strict'),
            'source' => 'cli',
        ];

        $run = app(WpMigrationRunService::class)->startMediaRun($bale->id, $meta, null, $limit);

        if (! $run) {
            $this->info('No posts with media placeholders found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Dispatched media run <info>%s</info> with <info>%d</info> post(s) to the <info>%s</info> queue.',
            $run->id,
            $run->total,
            config('core.wp_migration.media_queue')
        ));

        $this->comment('Make sure a queue worker is running, e.g.: php artisan queue:work --queue=wp-import,wp-media');

        return self::SUCCESS;
    }

    /**
     * Run the migration synchronously in this process.
     */
    protected function runInline(
        MediaMigrationService $service,
        object $bale,
        string $slug,
        string $connection,
        bool $dryRun,
        int $limit
    ): int {
        // Make Cdn::url() resolve to the tenant we are migrating.
        app(WpTenant::class)->cdn($slug);

        if ($wpBase = $this->option('wp-base')) {
            $service->setBaseUrl($wpBase);
        }

        if ($sourceDir = $this->option('source-dir')) {
            $service->setSourceDir($sourceDir);
        }

        if ($quarantineDir = $this->option('quarantine-dir')) {
            $service->setQuarantineDir($quarantineDir);
        }

        if ($this->option('strict')) {
            $service->setStrict(true);
        }

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

        $totals = ['migrated' => 0, 'failed' => 0, 'blocked' => 0, 'posts' => 0];
        $failures = [];
        $security = [];

        foreach ($posts as $post) {
            $bar->advance();

            $content = is_string($post->content) ? json_decode($post->content, true) : (array) $post->content;

            if (! is_array($content)) {
                continue;
            }

            $result = $service->migrateContent($content, $slug);

            $totals['migrated'] += $result['migrated'];
            $totals['failed'] += $result['failed'];
            $totals['blocked'] += $result['blocked'] ?? 0;

            foreach ($result['errors'] as $error) {
                $failures[] = Str::limit($post->title, 45).': '.$error;
            }

            foreach (($result['security'] ?? []) as $finding) {
                $security[] = Str::limit($post->title, 40).': '.$finding;
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
                ['Media blocked (security)', $totals['blocked']],
            ]
        );

        if (! empty($security)) {
            $this->newLine();
            $this->error('Security scanner blocked '.count($security).' file(s) — placeholders kept:');
            foreach (array_slice($security, 0, 25) as $finding) {
                $this->line('  • '.$finding);
            }
            if (count($security) > 25) {
                $this->line('  … and '.(count($security) - 25).' more.');
            }
            if ($this->option('quarantine-dir')) {
                $this->line('  Quarantined payloads were written to: '.$this->option('quarantine-dir'));
            } else {
                $this->line('  Tip: re-run with --quarantine-dir=<path> to keep the rejected files for review.');
            }
        }

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
}
