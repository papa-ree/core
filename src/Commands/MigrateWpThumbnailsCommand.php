<?php

namespace Bale\Core\Commands;

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Services\WpMigrationRunService;
use Bale\Core\Services\WpSqlParser;
use Bale\Core\Support\WpTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * core:migrate-wp-thumbnails
 *
 * Backfills `posts.thumbnail` for posts that were already imported, using the
 * WordPress featured image:
 *
 *   wp_postmeta._thumbnail_id → wp_posts (post_type = attachment).guid
 *
 * A "posts-only" dump has no `wp_postmeta`, so this needs the full mysqldump.
 * Thumbnails are re-encoded exactly like `EditPost::uploadThumbnail()` — WebP
 * q80, maxWidth 800, `{slug}/thumbnails/{uuid}.webp` — and every download goes
 * through {@see MediaMigrationService}'s security scan first, because the
 * legacy site is untrusted.
 *
 * Existing thumbnails are left alone unless `--overwrite` is given. Nothing is
 * re-imported: the post ↔ WordPress post id relation comes from
 * `wp_migration_items` of the previous SQL run.
 */
class MigrateWpThumbnailsCommand extends Command
{
    protected $signature = 'core:migrate-wp-thumbnails
        {--bale= : BaleList UUID (tenant) whose posts should be updated}
        {--slug= : Override organization slug (storage/CDN directory); defaults to the BaleList slug}
        {--file= : Path to the WordPress dump (full dump, must contain wp_postmeta)}
        {--wp-base= : Base URL of the legacy WordPress site, used to resolve and allow-list image URLs}
        {--source-dir= : Local path to a copied wp-content (or uploads) directory to read files from, instead of HTTP}
        {--limit=0 : Maximum number of posts to process (0 = all)}
        {--quarantine-dir= : Directory to copy files rejected by the security scanner into (for manual review)}
        {--strict : Also block files with low/medium security findings, not just high}
        {--overwrite : Replace thumbnails on posts that already have one}
        {--queue= : Queue name for the dispatched jobs (default: wp-media)}
        {--sync : Run inline instead of dispatching queued jobs}
        {--dry-run : Report what would change without writing to the database (runs inline)}';

    protected $description = 'Backfill post thumbnails from the WordPress featured image (_thumbnail_id)';

    public function handle(MediaMigrationService $service, WpSqlParser $parser): int
    {
        $baleId = (string) $this->option('bale');
        if ($baleId === '') {
            $this->error('The --bale option is required (BaleList UUID).');

            return self::FAILURE;
        }

        $file = (string) $this->option('file');
        if ($file === '' || ! is_file($file)) {
            $this->error('The --file option must point to an existing WordPress dump.');

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

        if (! $this->option('wp-base') && ! $this->option('source-dir')) {
            $this->error('Pass --wp-base and/or --source-dir so image downloads stay inside the legacy site.');

            return self::FAILURE;
        }

        $connection = app(WpTenant::class)->connection($bale->id);

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Failed to connect to tenant database: '.$e->getMessage());

            return self::FAILURE;
        }

        $sql = @file_get_contents($file);
        if ($sql === false || $sql === '') {
            $this->error("Unable to read dump: {$file}");

            return self::FAILURE;
        }

        $thumbnails = $parser->parseThumbnails($sql);

        if ($parser->thumbnailMetaCount === 0) {
            $this->error('This dump has no wp_postmeta._thumbnail_id — it looks like a posts-only export.');
            $this->line('Use the full mysqldump (it contains wp_postmeta and the attachment rows).');

            return self::FAILURE;
        }

        if ($thumbnails === []) {
            $this->error('No featured image could be resolved (missing attachment rows?).');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Dump: <info>%d</info> featured image(s) · <info>%d</info> attachment(s) · resolved: <info>%d</info>',
            $parser->thumbnailMetaCount,
            $parser->attachmentCount,
            count($thumbnails)
        ));

        $result = $this->buildCandidates($bale->id, $connection, $thumbnails);

        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $inline = $dryRun || (bool) $this->option('sync');
        $overwrite = (bool) $this->option('overwrite');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Imported posts', $result['imported']],
                ['Will be set', count($result['candidates'])],
                ['Already has thumbnail', $result['stats']['has_thumbnail']],
                ['No featured image in dump', $result['stats']['no_featured']],
                ['Post missing in tenant', $result['stats']['missing_post']],
                ['Post not imported', $result['stats']['unmapped']],
                ['Not an image attachment', $result['stats']['not_image']],
            ]
        );

        if ($result['candidates'] === []) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        if (! $overwrite && $result['stats']['has_thumbnail'] > 0) {
            $this->comment(sprintf(
                '%d post(s) already have a thumbnail and were skipped. Pass --overwrite to replace them.',
                $result['stats']['has_thumbnail']
            ));
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('Dry run complete — no downloads and no database changes were made.');

            return self::SUCCESS;
        }

        if ($inline) {
            return $this->runInline($service, $connection, $slug, $result['candidates'], $limit);
        }

        return $this->dispatchRun($bale, $slug, $result['candidates'], $limit);
    }

    /**
     * Map the dump's featured images onto tenant posts.
     *
     * @param  array<string, array{attachment_id: string, url: string, mime: string}>  $thumbnails
     * @return array{candidates: array<int, array<string, string|null>>, stats: array<string, int>, imported: int}
     */
    protected function buildCandidates(string $baleId, string $connection, array $thumbnails): array
    {
        // post ↔ WordPress post id, recorded by the SQL import stage.
        $runIds = WpMigrationRun::where('bale_id', $baleId)->where('type', 'sql')->pluck('id');

        $map = WpMigrationItem::whereIn('run_id', $runIds)
            ->where('status', WpMigrationItem::STATUS_IMPORTED)
            ->whereNotNull('post_id')
            ->orderBy('updated_at')
            ->get(['wp_post_id', 'post_id'])
            ->mapWithKeys(fn (WpMigrationItem $item) => [(string) $item->wp_post_id => (string) $item->post_id])
            ->all();

        $stats = [
            'has_thumbnail' => 0,
            'no_featured' => 0,
            'missing_post' => 0,
            'unmapped' => 0,
            'not_image' => 0,
        ];

        if ($map === []) {
            return ['candidates' => [], 'stats' => $stats, 'imported' => 0];
        }

        $posts = DB::connection($connection)
            ->table('posts')
            ->select('id', 'title', 'thumbnail')
            ->whereNull('deleted_at')
            ->whereIn('id', array_values($map))
            ->get()
            ->keyBy('id');

        $overwrite = (bool) $this->option('overwrite');
        $candidates = [];
        $covered = 0;

        foreach ($thumbnails as $wpPostId => $attachment) {
            $postId = $map[(string) $wpPostId] ?? null;

            if ($postId === null) {
                $stats['unmapped']++;

                continue;
            }

            $covered++;
            $post = $posts[$postId] ?? null;

            if ($post === null) {
                $stats['missing_post']++;

                continue;
            }

            if (! $overwrite && ! empty($post->thumbnail)) {
                $stats['has_thumbnail']++;

                continue;
            }

            if ($attachment['mime'] !== '' && ! str_starts_with($attachment['mime'], 'image/')) {
                $stats['not_image']++;

                continue;
            }

            if ($attachment['url'] === '') {
                $stats['missing_post']++;

                continue;
            }

            $candidates[] = [
                'post_id' => (string) $post->id,
                'wp_post_id' => (string) $wpPostId,
                'url' => $attachment['url'],
                'title' => (string) $post->title,
            ];
        }

        $stats['no_featured'] = count($map) - $covered;

        return ['candidates' => $candidates, 'stats' => $stats, 'imported' => count($map)];
    }

    /**
     * Dispatch the backfill as a queue batch (one job per post).
     *
     * @param  array<int, array<string, string|null>>  $candidates
     */
    protected function dispatchRun(object $bale, string $slug, array $candidates, int $limit): int
    {
        $meta = [
            'slug' => $slug,
            'wp_base' => $this->option('wp-base') ?: null,
            'source_dir' => $this->option('source-dir') ?: null,
            'quarantine_dir' => $this->option('quarantine-dir') ?: null,
            'strict' => (bool) $this->option('strict'),
            'source' => 'cli',
        ];

        $queue = (string) ($this->option('queue') ?: config('core.wp_migration.media_queue'));
        config(['core.wp_migration.media_queue' => $queue]);

        $run = app(WpMigrationRunService::class)->startThumbnailRun($bale->id, $candidates, $meta, $limit);

        if (! $run) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Dispatched thumbnail run <info>%s</info> with <info>%d</info> post(s) to the <info>%s</info> queue.',
            $run->id,
            $run->total,
            $queue
        ));

        $this->comment('Make sure a queue worker is running, e.g.: php artisan queue:work --queue=wp-import,wp-media');

        return self::SUCCESS;
    }

    /**
     * Run the backfill synchronously in this process.
     *
     * @param  array<int, array<string, string|null>>  $candidates
     */
    protected function runInline(
        MediaMigrationService $service,
        string $connection,
        string $slug,
        array $candidates,
        int $limit
    ): int {
        if ($limit > 0) {
            $candidates = array_slice($candidates, 0, $limit);
        }

        // Make Cdn::url() resolve to the tenant we are migrating.
        app(WpTenant::class)->cdn($slug);

        $this->configureService($service);

        $this->info(sprintf('Processing %d post(s)…', count($candidates)));

        $bar = $this->output->createProgressBar(count($candidates));
        $bar->start();

        $totals = ['set' => 0, 'failed' => 0, 'blocked' => 0];
        $failures = [];

        foreach ($candidates as $candidate) {
            $bar->advance();

            $filename = $service->storeThumbnailFromUrl($candidate['url'], $slug);

            if ($filename !== null && $filename !== '') {
                DB::connection($connection)
                    ->table('posts')
                    ->where('id', $candidate['post_id'])
                    // Intentionally do NOT touch updated_at so the original
                    // WordPress timestamps are preserved.
                    ->update(['thumbnail' => $filename]);

                $totals['set']++;

                continue;
            }

            if ($service->lastScan()?->isBlocked()) {
                $totals['blocked']++;
                $failures[] = Str::limit((string) $candidate['title'], 40).': blocked by security scanner';

                continue;
            }

            $totals['failed']++;
            $failures[] = Str::limit((string) $candidate['title'], 40).': download failed';
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Thumbnails set', $totals['set']],
                ['Failed', $totals['failed']],
                ['Blocked (security)', $totals['blocked']],
            ]
        );

        if (! empty($failures)) {
            $this->newLine();
            $this->warn('Not migrated (re-runnable):');
            foreach (array_slice($failures, 0, 25) as $failure) {
                $this->line('  • '.$failure);
            }
            if (count($failures) > 25) {
                $this->line('  … and '.(count($failures) - 25).' more.');
            }
        }

        return self::SUCCESS;
    }

    protected function configureService(MediaMigrationService $service): void
    {
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
    }
}
