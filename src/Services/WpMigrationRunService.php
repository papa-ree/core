<?php

namespace Bale\Core\Services;

use Bale\Core\Jobs\Wp\FinalizeWpRunJob;
use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Jobs\Wp\MigrateWpThumbnailPostJob;
use Bale\Core\Jobs\Wp\StartWpMediaRunJob;
use Bale\Core\Jobs\Wp\StartWpThumbnailRunJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Support\WpTenant;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * WpMigrationRunService
 *
 * Orchestrates WordPress migration as queue batches instead of one long
 * synchronous request:
 *
 *   SQL run   → one {@see ImportWpPostJob} per post   (queue wp-import)
 *   Media run → one {@see MigrateWpMediaPostJob} per post (queue wp-media)
 *
 * Progress is persisted per post in `wp_migration_items`, so the Alpine UI can
 * poll a lightweight JSON endpoint without re-rendering Livewire.
 */
class WpMigrationRunService
{
    public const QUEUE_SQL = 'wp-import';

    public const QUEUE_MEDIA = 'wp-media';

    public function __construct(protected ?WpTenant $tenant = null)
    {
        $this->tenant ??= app(WpTenant::class);
    }

    /** Items inserted per bulk statement. */
    protected function chunkSize(): int
    {
        return max(1, (int) config('core.wp_migration.chunk_size', 500));
    }

    protected function sqlQueue(): string
    {
        return (string) config('core.wp_migration.sql_queue', self::QUEUE_SQL);
    }

    protected function mediaQueue(): string
    {
        return (string) config('core.wp_migration.media_queue', self::QUEUE_MEDIA);
    }

    public function tenant(): WpTenant
    {
        return $this->tenant;
    }

    /**
     * Create a SQL run (one item per parsed post) and dispatch the import batch.
     *
     * @param  array<int, array<string, string|null>>  $posts
     * @param  array<string, mixed>  $meta
     * @param  array<string, array{attachment_id: string, url: string, mime: string}>  $thumbnails
     */
    public function startSqlRun(string $baleId, string $authorId, array $posts, array $meta = [], array $thumbnails = []): WpMigrationRun
    {
        $run = $this->createSqlRun($baleId, $authorId, $posts, $meta, $thumbnails);

        $this->dispatchSqlBatch($run);

        return $run->refresh();
    }

    /**
     * Create the SQL run and its items without dispatching anything (used by
     * `--sync` runs that process the items inline).
     *
     * The featured image of each post (from `wp_postmeta._thumbnail_id`) is
     * carried inside the item payload, so the chained thumbnail stage never has
     * to parse the dump a second time.
     *
     * @param  array<int, array<string, string|null>>  $posts
     * @param  array<string, mixed>  $meta
     * @param  array<string, array{attachment_id: string, url: string, mime: string}>  $thumbnails
     */
    public function createSqlRun(string $baleId, string $authorId, array $posts, array $meta = [], array $thumbnails = []): WpMigrationRun
    {
        $run = WpMigrationRun::create([
            'bale_id' => $baleId,
            'type' => 'sql',
            'status' => 'running',
            'total' => count($posts),
            'started_at' => now(),
            'meta' => array_merge($meta, ['author' => $authorId]),
        ]);

        $now = now();
        $items = [];

        foreach ($posts as $post) {
            $wpId = (string) ($post['ID'] ?? '');

            $featured = $thumbnails[$wpId] ?? null;

            if (is_array($featured) && ($featured['url'] ?? '') !== '') {
                $post['thumbnail_url'] = $featured['url'];
                $post['thumbnail_mime'] = $featured['mime'] ?? '';
            }

            $items[] = [
                'id' => (string) Str::uuid(),
                'run_id' => $run->id,
                'wp_post_id' => $wpId,
                'post_id' => null,
                'status' => WpMigrationItem::STATUS_PENDING,
                'payload' => json_encode($post, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($items, $this->chunkSize()) as $chunk) {
            WpMigrationItem::insert($chunk);
        }

        return $run->refresh();
    }

    public function dispatchSqlBatch(WpMigrationRun $run): void
    {
        // Only pending items are dispatched: a re-dispatch (see
        // `core:wp-migration-redispatch`) must not queue 198 no-op jobs for
        // posts that were already imported.
        $itemIds = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_PENDING)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            $this->finish($run);
            $this->chainMedia($run->id);

            return;
        }

        $jobs = $itemIds->map(fn ($id) => new ImportWpPostJob($run->id, (string) $id))->all();

        $batch = Bus::batch($jobs)
            ->name('wp-import:'.$run->id)
            ->onQueue($this->sqlQueue())
            ->allowFailures()
            // `then` only fires when the last job to finish *succeeded*
            // (Batch::recordSuccessfulJob); Batch::recordFailedJob never
            // invokes `then`. Because the batch uses allowFailures(), a failed
            // last job would therefore leave the next stages permanently
            // unstarted — so we also register `finally`, which fires once every
            // job has run either way. The callback is idempotent, and it also
            // finishes the run when the chained stages are disabled, so the
            // status endpoint never polls forever.
            ->then(new StartWpMediaRunJob($run->id))
            ->finally(new StartWpMediaRunJob($run->id))
            ->dispatch();

        $run->forceFill(['batch_id' => $batch->id])->save();
    }

    /**
     * Create a media run (one item per post that still has placeholders) and
     * dispatch the media batch. Returns null when there is nothing to migrate.
     *
     * @param  array<string, mixed>  $meta
     */
    public function startMediaRun(string $baleId, array $meta = [], ?string $parentId = null, int $limit = 0): ?WpMigrationRun
    {
        $connection = $this->tenant->connection($baleId);

        $query = DB::connection($connection)
            ->table('posts')
            ->select('id', 'title')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('content', 'like', '%image-placeholder%')
                    ->orWhere('content', 'like', '%media-placeholder%');
            })
            ->orderBy('created_at');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $run = WpMigrationRun::create([
            'bale_id' => $baleId,
            'type' => 'media',
            'parent_id' => $parentId,
            'status' => 'running',
            'total' => $posts->count(),
            'started_at' => now(),
            'meta' => $meta,
        ]);

        $now = now();
        $items = [];

        foreach ($posts as $post) {
            $items[] = [
                'id' => (string) Str::uuid(),
                'run_id' => $run->id,
                'wp_post_id' => null,
                'post_id' => (string) $post->id,
                'status' => WpMigrationItem::STATUS_PENDING,
                'payload' => null,
                'result' => json_encode(['title' => (string) $post->title], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($items, $this->chunkSize()) as $chunk) {
            WpMigrationItem::insert($chunk);
        }

        $this->dispatchMediaBatch($run);

        return $run->refresh();
    }

    public function dispatchMediaBatch(WpMigrationRun $run): void
    {
        $itemIds = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_PENDING)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            $this->finish($run);

            // Nothing to dispatch means no callback will fire; keep the chain
            // moving so the thumbnail stage is not stranded.
            if ($run->parent_id) {
                $this->chainThumbnails((string) $run->parent_id);
            }

            return;
        }

        $jobs = $itemIds->map(fn ($id) => new MigrateWpMediaPostJob($run->id, (string) $id))->all();

        $batch = Bus::batch($jobs)
            ->name('wp-media:'.$run->id)
            ->onQueue($this->mediaQueue())
            ->allowFailures()
            // Also chains the thumbnail stage, so `finally` (not just `then`)
            // keeps the chain alive even when the last media job failed.
            ->then(new StartWpThumbnailRunJob($run->id))
            ->finally(new StartWpThumbnailRunJob($run->id))
            ->dispatch();

        $run->forceFill(['batch_id' => $batch->id])->save();
    }

    /**
     * Create a thumbnail backfill run (one item per post that still needs a
     * featured image) and dispatch its batch. Returns null when there is
     * nothing to do.
     *
     * @param  array<int, array{post_id: string, url: string, wp_post_id?: string|null, title?: string|null}>  $posts
     * @param  array<string, mixed>  $meta
     */
    public function startThumbnailRun(string $baleId, array $posts, array $meta = [], int $limit = 0, ?string $parentId = null): ?WpMigrationRun
    {
        if ($posts === []) {
            return null;
        }

        if ($limit > 0) {
            $posts = array_slice($posts, 0, $limit);
        }

        $run = WpMigrationRun::create([
            'bale_id' => $baleId,
            'type' => 'thumbnail',
            'parent_id' => $parentId,
            'status' => 'running',
            'total' => count($posts),
            'started_at' => now(),
            'meta' => $meta,
        ]);

        $now = now();
        $items = [];

        foreach ($posts as $post) {
            $items[] = [
                'id' => (string) Str::uuid(),
                'run_id' => $run->id,
                'wp_post_id' => isset($post['wp_post_id']) ? (string) $post['wp_post_id'] : null,
                'post_id' => (string) $post['post_id'],
                'status' => WpMigrationItem::STATUS_PENDING,
                'payload' => json_encode(['url' => $post['url']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'result' => json_encode(['title' => $post['title'] ?? null], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($items, $this->chunkSize()) as $chunk) {
            WpMigrationItem::insert($chunk);
        }

        $this->dispatchThumbnailBatch($run);

        return $run->refresh();
    }

    public function dispatchThumbnailBatch(WpMigrationRun $run): void
    {
        $itemIds = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_PENDING)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            $this->finish($run);

            return;
        }

        $jobs = $itemIds->map(fn ($id) => new MigrateWpThumbnailPostJob($run->id, (string) $id))->all();

        $batch = Bus::batch($jobs)
            ->name('wp-thumbnail:'.$run->id)
            ->onQueue($this->mediaQueue())
            ->allowFailures()
            ->then(new FinalizeWpRunJob($run->id))
            ->finally(new FinalizeWpRunJob($run->id))
            ->dispatch();

        $run->forceFill(['batch_id' => $batch->id])->save();
    }

    /**
     * Batch callback: the SQL stage finished — mark it done and start the media
     * stage for the posts that were just imported.
     *
     * Idempotent: it is registered as both `then` and `finally` on the SQL batch,
     * so on the happy path it is invoked twice for the same run. The second call
     * must not create a second media run.
     *
     * Owns the whole downstream chain: when the media stage is disabled or finds
     * nothing to migrate, the thumbnail stage is started right here instead of
     * waiting for a media batch callback that will never fire.
     */
    public function chainMedia(string $sqlRunId): void
    {
        $sqlRun = WpMigrationRun::find($sqlRunId);

        if (! $sqlRun) {
            return;
        }

        $meta = $sqlRun->meta ?? [];

        // The SQL stage is done either way, even when the chain was already
        // resolved by a previous invocation.
        $this->finish($sqlRun);

        if ($this->shouldChainMedia()) {
            $mediaRun = $this->childRun($sqlRunId, 'media');

            if ($mediaRun) {
                // A media batch owns the next link. When it already finished
                // (callback lost, redispatch, older run) chain right away.
                if ($mediaRun->status === 'finished') {
                    $this->chainThumbnails($sqlRunId);
                }

                return;
            }

            // Already resolved to "no media needed" → fall through to thumbnails.
            if (! array_key_exists('media_run_id', $meta)) {
                $mediaRun = $this->startMediaRun(
                    $sqlRun->bale_id,
                    $meta,
                    $sqlRun->id,
                    (int) ($meta['media_limit'] ?? 0),
                );

                // Record that the chain resolved (even when there was no media),
                // so the status endpoint knows it should stop waiting.
                $sqlRun->forceFill([
                    'meta' => array_merge($meta, ['media_run_id' => $mediaRun?->id]),
                ])->save();

                if ($mediaRun) {
                    // The media batch callback chains the thumbnail stage.
                    return;
                }
            }
        } elseif (! array_key_exists('media_run_id', $meta)) {
            $sqlRun->forceFill(['meta' => array_merge($meta, ['media_run_id' => null])])->save();
        }

        $this->chainThumbnails($sqlRunId);
    }

    /**
     * Second link of the chain: start the thumbnail stage for an imported SQL
     * run, using the featured images carried in the item payloads.
     *
     * Idempotent, and it always records `thumbnail_run_id` — including an
     * explicit `null` when there is nothing to do — so the progress endpoint can
     * tell "not chained yet" apart from "chained and nothing to migrate".
     */
    public function chainThumbnails(string $sqlRunId): void
    {
        $sqlRun = WpMigrationRun::find($sqlRunId);

        if (! $sqlRun) {
            return;
        }

        $meta = $sqlRun->meta ?? [];

        if (array_key_exists('thumbnail_run_id', $meta) || $this->childRun($sqlRunId, 'thumbnail')) {
            return;
        }

        $run = $this->shouldChainThumbnails() ? $this->startThumbnailRunFromSql($sqlRun) : null;

        $sqlRun->forceFill([
            'meta' => array_merge($meta, ['thumbnail_run_id' => $run?->id]),
        ])->save();
    }

    /**
     * Build (and dispatch) the thumbnail run for the posts of a finished SQL run
     * that carry a featured image and do not have a thumbnail yet.
     *
     * Returns null when there is nothing left to do — e.g. the dump was
     * posts-only, or every post already has a thumbnail.
     */
    public function startThumbnailRunFromSql(WpMigrationRun $sqlRun): ?WpMigrationRun
    {
        $items = WpMigrationItem::where('run_id', $sqlRun->id)
            ->where('status', WpMigrationItem::STATUS_IMPORTED)
            ->whereNotNull('post_id')
            ->get();

        $candidates = [];

        foreach ($items as $item) {
            $payload = $item->payload ?? [];
            $url = (string) ($payload['thumbnail_url'] ?? '');

            if ($url === '') {
                continue;
            }

            $mime = (string) ($payload['thumbnail_mime'] ?? '');

            if ($mime !== '' && ! str_starts_with($mime, 'image/')) {
                continue;
            }

            $candidates[] = [
                'post_id' => (string) $item->post_id,
                'wp_post_id' => (string) $item->wp_post_id,
                'url' => $url,
                'title' => (string) ($item->result['title'] ?? ''),
            ];
        }

        if ($candidates === []) {
            return null;
        }

        // Never re-download a thumbnail that is already there (re-runs,
        // redispatch, a manual backfill that ran first).
        $connection = $this->tenant->connection($sqlRun->bale_id);

        $already = DB::connection($connection)
            ->table('posts')
            ->whereIn('id', array_column($candidates, 'post_id'))
            ->whereNotNull('thumbnail')
            ->pluck('id')
            ->all();

        if ($already !== []) {
            $skip = array_flip($already);
            $candidates = array_values(array_filter(
                $candidates,
                fn (array $candidate) => ! isset($skip[$candidate['post_id']])
            ));
        }

        if ($candidates === []) {
            return null;
        }

        $meta = array_diff_key($sqlRun->meta ?? [], array_flip(['media_run_id', 'thumbnail_run_id']));

        return $this->startThumbnailRun(
            $sqlRun->bale_id,
            $candidates,
            array_merge($meta, ['source' => 'chain']),
            (int) ($meta['thumbnail_limit'] ?? 0),
            $sqlRun->id,
        );
    }

    protected function childRun(string $parentId, string $type): ?WpMigrationRun
    {
        return WpMigrationRun::where('parent_id', $parentId)
            ->where('type', $type)
            ->latest()
            ->first();
    }

    public function finish(WpMigrationRun $run, string $status = 'finished'): void
    {
        $run->forceFill(['status' => $status, 'finished_at' => now()])->save();
    }

    /**
     * Progress payload consumed by the Alpine poller.
     *
     * @return array<string, mixed>
     */
    public function progress(WpMigrationRun $run): array
    {
        $counts = $this->counts($run);
        $pending = (int) ($counts[WpMigrationItem::STATUS_PENDING] ?? 0);

        $mediaPayload = null;
        $thumbnailPayload = null;
        $mediaResolved = true;
        $thumbnailResolved = true;

        if ($run->type === 'sql') {
            $meta = $run->meta ?? [];

            $media = $this->childRun($run->id, 'media');
            $thumbnail = $this->childRun($run->id, 'thumbnail');

            if ($media) {
                $mediaCounts = $this->counts($media);
                $mediaPending = (int) ($mediaCounts[WpMigrationItem::STATUS_PENDING] ?? 0);

                $mediaPayload = [
                    'id' => $media->id,
                    'status' => $media->status,
                    'total' => (int) $media->total,
                    'pending' => $mediaPending,
                    'counts' => $mediaCounts,
                ];

                $mediaResolved = $mediaPending === 0;
            } else {
                // Either the chain has not run yet (wait) or it ran and found
                // nothing to migrate (media_run_id explicitly null → resolved).
                $mediaResolved = array_key_exists('media_run_id', $meta)
                    && ($meta['media_run_id'] ?? null) === null;
            }

            if ($thumbnail) {
                $thumbnailCounts = $this->counts($thumbnail);
                $thumbnailPending = (int) ($thumbnailCounts[WpMigrationItem::STATUS_PENDING] ?? 0);

                $thumbnailPayload = [
                    'id' => $thumbnail->id,
                    'status' => $thumbnail->status,
                    'total' => (int) $thumbnail->total,
                    'pending' => $thumbnailPending,
                    'counts' => $thumbnailCounts,
                ];

                $thumbnailResolved = $thumbnailPending === 0;
            } else {
                // The thumbnail stage only starts after the media batch is done,
                // so it may legitimately not exist yet.
                $thumbnailResolved = array_key_exists('thumbnail_run_id', $meta)
                    && ($meta['thumbnail_run_id'] ?? null) === null;
            }
        }

        $stagesResolved = $mediaResolved && $thumbnailResolved;

        $errors = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_FAILED)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (WpMigrationItem $item) => $this->itemLabel($item).': '.($item->result['error'] ?? 'unknown error'))
            ->all();

        $security = WpMigrationItem::where('run_id', $run->id)
            ->where('status', WpMigrationItem::STATUS_BLOCKED)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (WpMigrationItem $item) => $this->itemLabel($item).': '.($item->result['security'] ?? 'blocked by security scanner'))
            ->all();

        return [
            'run' => [
                'id' => $run->id,
                'type' => $run->type,
                'status' => $run->status,
                'total' => (int) $run->total,
                'pending' => $pending,
                'counts' => $counts,
                'started_at' => optional($run->started_at)->toIso8601String(),
                'finished_at' => optional($run->finished_at)->toIso8601String(),
            ],
            'media' => $mediaPayload,
            'thumbnail' => $thumbnailPayload,
            'errors' => $errors,
            'security' => $security,
            'done' => $pending === 0 && $stagesResolved,
        ];
    }

    /** @return array<string, int> */
    public function counts(WpMigrationRun $run): array
    {
        return WpMigrationItem::where('run_id', $run->id)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    protected function itemLabel(WpMigrationItem $item): string
    {
        return (string) ($item->result['title'] ?? $item->post_id ?? $item->wp_post_id ?? 'post');
    }

    protected function shouldChainMedia(): bool
    {
        return (bool) config('core.wp_migration.chain_media', true);
    }

    protected function shouldChainThumbnails(): bool
    {
        return (bool) config('core.wp_migration.chain_thumbnails', true);
    }
}
