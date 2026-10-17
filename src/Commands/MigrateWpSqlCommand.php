<?php

namespace Bale\Core\Commands;

use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Services\WpMigrationRunService;
use Bale\Core\Services\WpSqlParser;
use Bale\Core\Support\WpTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * core:migrate-wp-sql
 *
 * First stage of the WordPress → Bale CMS import. Parses a `wp_posts` dump and
 * imports every published post into a tenant database as EditorJS content.
 *
 * By default the work is dispatched to the queue — one job per post on the
 * `wp-import` queue — and the media stage is chained automatically once the
 * import batch finishes:
 *
 *   php artisan core:migrate-wp-sql --bale=<uuid> --author=<uuid> --file=dump.sql
 *   php artisan queue:work --queue=wp-import,wp-media
 *
 * Pass `--sync` to import inline (no queue worker required), or `--no-chain` to
 * skip the automatic media stage.
 */
class MigrateWpSqlCommand extends Command
{
    protected $signature = 'core:migrate-wp-sql
        {--bale= : BaleList UUID (tenant) destination}
        {--author= : Author UUID (users.uuid in the tenant database)}
        {--file= : Path to the WordPress .sql dump}
        {--prefix= : Table prefix, e.g. wp_ (empty = auto-detect)}
        {--slug= : Override organization slug; defaults to the BaleList slug}
        {--limit=0 : Maximum number of posts to import (0 = all)}
        {--no-fix-encoding : Import the dump bytes verbatim (skip the mojibake repair)}
        {--queue= : Queue name for the dispatched jobs (default: wp-import)}
        {--sync : Run inline instead of dispatching queued jobs}
        {--no-chain : Do not auto-start the media stage after the import}
        {--no-chain-thumbnails : Do not auto-start the thumbnail (featured image) stage after the media stage}
        {--wp-base= : Legacy WordPress base URL, forwarded to the chained media stage}
        {--source-dir= : Local uploads copy, forwarded to the media stage}
        {--quarantine-dir= : Quarantine directory, forwarded to the media stage}
        {--strict : Strict security scan, forwarded to the media stage}
        {--media-limit=0 : Maximum posts for the chained media stage (0 = all)}
        {--thumbnail-limit=0 : Maximum posts for the chained thumbnail stage (0 = all)}';

    protected $description = 'Import WordPress posts from a .sql dump into a Bale CMS tenant, one queued job per post';

    public function handle(WpSqlParser $parser): int
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

        $file = (string) $this->option('file');
        if ($file === '' || ! is_file($file)) {
            $this->error('The --file option must point to an existing .sql dump.');

            return self::FAILURE;
        }

        $author = (string) $this->option('author');
        if ($author === '') {
            $this->error('The --author option is required (users.uuid in the tenant database).');

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
        } catch (\Throwable $e) {
            $this->error('Failed to connect to tenant database: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! DB::connection($connection)->table('users')->where('uuid', $author)->exists()) {
            $this->error("Author [{$author}] was not found in the tenant database. Create a user first.");

            return self::FAILURE;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            $this->error("Unable to read [{$file}].");

            return self::FAILURE;
        }

        $prefix = (string) $this->option('prefix');
        $repairEncoding = ! (bool) $this->option('no-fix-encoding');
        $posts = $parser->parse($sql, $prefix, $repairEncoding);

        // Featured images (`wp_postmeta._thumbnail_id`) — available whenever the
        // dump is a full mysqldump. Empty for a posts-only export, in which case
        // the chained thumbnail stage simply resolves to "nothing to do".
        $thumbnails = $parser->parseThumbnails($sql, $prefix);

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $posts = array_slice($posts, 0, $limit);
        }

        $this->line(sprintf(
            'File: <info>%s</info> | rows found: <info>%d</info> | filtered out: <info>%d</info> | publishable posts: <info>%d</info>%s',
            basename($file),
            $parser->totalFoundCount,
            $parser->filteredCount,
            count($posts),
            $limit > 0 ? ' (limited to '.$limit.')' : ''
        ));

        $this->line(sprintf(
            'Featured images: <info>%d</info> attachment(s) referenced by <info>%d</info> post(s)%s',
            $parser->attachmentCount,
            count($thumbnails),
            $thumbnails === [] ? ' <comment>(posts-only dump: no wp_postmeta)</comment>' : ''
        ));

        if ($parser->encodingRepairedCount > 0) {
            $this->line(sprintf(
                'Detected a mojibake-encoded dump: repaired <info>%d</info> text value(s). Pass --no-fix-encoding to import the bytes verbatim.',
                $parser->encodingRepairedCount
            ));
        }

        if (empty($posts)) {
            $this->warn('No publishable WordPress posts found in the dump. Check the file and table prefix.');

            return self::SUCCESS;
        }

        $chainMedia = ! (bool) $this->option('no-chain');
        config(['core.wp_migration.chain_media' => $chainMedia]);

        $chainThumbnails = ! (bool) $this->option('no-chain-thumbnails');

        if (! $chainThumbnails) {
            config(['core.wp_migration.chain_thumbnails' => false]);
        }

        // The thumbnail stage only downloads from the host of --wp-base (or a
        // local --source-dir mirror): a dump is untrusted input, so its `guid`
        // values are never followed blindly. Without either basis every single
        // featured image would be rejected, so fail fast instead of importing
        // 198 posts that silently end up without a thumbnail. A posts-only dump
        // has nothing to fetch, so it is exempt.
        if ($chainThumbnails && $thumbnails !== [] && ! $this->option('wp-base') && ! $this->option('source-dir')) {
            $this->error(sprintf(
                'This dump references %d featured image(s), but the thumbnail stage needs a source to download from.',
                count($thumbnails)
            ));
            $this->line('Pass --wp-base=https://situs-lama.go.id (or --source-dir=<local uploads copy>).');
            $this->line('To import posts and inline media only, pass --no-chain-thumbnails.');

            return self::FAILURE;
        }

        if ($queue = $this->option('queue')) {
            config(['core.wp_migration.sql_queue' => (string) $queue]);
        }

        $meta = [
            'slug' => $slug,
            'wp_base' => $this->option('wp-base') ?: null,
            'source_dir' => $this->option('source-dir') ?: null,
            'quarantine_dir' => $this->option('quarantine-dir') ?: null,
            'strict' => (bool) $this->option('strict'),
            'media_limit' => (int) $this->option('media-limit'),
            'thumbnail_limit' => (int) $this->option('thumbnail-limit'),
            'file' => basename($file),
            'table_prefix' => $prefix,
            'repair_encoding' => $repairEncoding,
            'encoding_repaired' => $parser->encodingRepairedCount,
            'source' => 'cli',
        ];

        $runs = app(WpMigrationRunService::class);

        if ((bool) $this->option('sync')) {
            return $this->runSync($runs, $bale->id, $author, $posts, $meta, $chainMedia, $thumbnails);
        }

        $run = $runs->startSqlRun($bale->id, $author, $posts, $meta, $thumbnails);

        $this->info(sprintf(
            'Dispatched SQL run <info>%s</info> with <info>%d</info> post(s) to the <info>%s</info> queue.',
            $run->id,
            count($posts),
            config('core.wp_migration.sql_queue')
        ));

        if ($chainMedia) {
            $stages = ['media'];
            if (config('core.wp_migration.chain_thumbnails')) {
                $stages[] = 'thumbnail';
            }
            $this->comment(sprintf(
                'The %s stage(s) will start automatically once the previous batch finishes.',
                implode(' + ', $stages)
            ));
        }

        $this->comment('Make sure a queue worker is running, e.g.: php artisan queue:work --queue=wp-import,wp-media');

        return self::SUCCESS;
    }

    /**
     * Import everything inline (no queue worker required).
     *
     * @param  array<int, array<string, string|null>>  $posts
     * @param  array<string, mixed>  $meta
     * @param  array<string, array{attachment_id: string, url: string, mime: string}>  $thumbnails
     */
    protected function runSync(
        WpMigrationRunService $runs,
        string $baleId,
        string $author,
        array $posts,
        array $meta,
        bool $chainMedia,
        array $thumbnails = []
    ): int {
        $run = $runs->createSqlRun($baleId, $author, $posts, $meta, $thumbnails);

        $bar = $this->output->createProgressBar(count($posts));
        $bar->start();

        $itemIds = WpMigrationItem::where('run_id', $run->id)->pluck('id');

        foreach ($itemIds as $itemId) {
            Bus::dispatchSync(new ImportWpPostJob($run->id, (string) $itemId));

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $counts = $runs->counts($run);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Posts imported', $counts[WpMigrationItem::STATUS_IMPORTED] ?? 0],
                ['Posts failed', $counts[WpMigrationItem::STATUS_FAILED] ?? 0],
                ['Posts skipped', $counts[WpMigrationItem::STATUS_SKIPPED] ?? 0],
            ]
        );

        $failures = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_FAILED)
            ->get()
            ->map(fn (WpMigrationItem $item) => Str::limit((string) ($item->result['title'] ?? $item->wp_post_id), 45)
                .': '.($item->result['error'] ?? 'unknown error'));

        foreach ($failures as $failure) {
            $this->error('  • '.$failure);
        }

        // Mark the run as finished. Sync runs are not followed by the UI, so we
        // resolve the chained-stage flags explicitly (no queued batches here).
        $run->forceFill([
            'status' => 'finished',
            'finished_at' => now(),
            'meta' => array_merge($meta, ['media_run_id' => null, 'thumbnail_run_id' => null]),
        ])->save();

        if ($chainMedia) {
            $this->comment('Run the next stages separately, e.g.: php artisan core:migrate-wp-media --bale='.$baleId.' --wp-base=<url>');
            $this->comment('Then: php artisan core:migrate-wp-thumbnails --bale='.$baleId.' --file=<full dump> --wp-base=<url> --sync');
        }

        return self::SUCCESS;
    }
}
