<?php

namespace Bale\Core\Services;

use Bale\Core\Jobs\Wp\FinalizeWpRunJob;
use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Jobs\Wp\StartWpMediaRunJob;
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
     */
    public function startSqlRun(string $baleId, string $authorId, array $posts, array $meta = []): WpMigrationRun
    {
        $run = $this->createSqlRun($baleId, $authorId, $posts, $meta);

        $this->dispatchSqlBatch($run);

        return $run->refresh();
    }

    /**
     * Create the SQL run and its items without dispatching anything (used by
     * `--sync` runs that process the items inline).
     *
     * @param  array<int, array<string, string|null>>  $posts
     * @param  array<string, mixed>  $meta
     */
    public function createSqlRun(string $baleId, string $authorId, array $posts, array $meta = []): WpMigrationRun
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
            $items[] = [
                'id' => (string) Str::uuid(),
                'run_id' => $run->id,
                'wp_post_id' => (string) ($post['ID'] ?? ''),
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
        $itemIds = WpMigrationItem::where('run_id', $run->id)->pluck('id');

        if ($itemIds->isEmpty()) {
            $this->finish($run);
            $this->chainMedia($run->id);

            return;
        }

        $jobs = $itemIds->map(fn ($id) => new ImportWpPostJob($run->id, (string) $id))->all();

        $batch = Bus::batch($jobs)
            ->name('wp-import:'.$run->id)
            ->onQueue($this->sqlQueue())
            ->allowFailures();

        if ($this->shouldChainMedia()) {
            // `then` only fires when the last job to finish *succeeded*
            // (Batch::recordSuccessfulJob); Batch::recordFailedJob never
            // invokes `then`. Because the batch uses allowFailures(), a failed
            // last job would therefore leave the media stage permanently
            // unstarted — so we also register `finally`, which fires once every
            // job has run either way. chainMedia() is idempotent, so both
            // firing on the happy path is harmless.
            $batch->then(new StartWpMediaRunJob($run->id))
                ->finally(new StartWpMediaRunJob($run->id));
        }

        $batch = $batch->dispatch();

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
        $itemIds = WpMigrationItem::where('run_id', $run->id)->pluck('id');

        if ($itemIds->isEmpty()) {
            $this->finish($run);

            return;
        }

        $jobs = $itemIds->map(fn ($id) => new MigrateWpMediaPostJob($run->id, (string) $id))->all();

        $batch = Bus::batch($jobs)
            ->name('wp-media:'.$run->id)
            ->onQueue($this->mediaQueue())
            ->allowFailures()
            // `finally` (not just `then`) so a failing last job cannot leave the
            // run stuck in `running` and make the status endpoint poll forever.
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

        // Already chained (either resolved to a media run, or resolved to "no
        // media needed" by an explicit null) → nothing left to start.
        if (array_key_exists('media_run_id', $meta) || $this->mediaRunExists($sqlRunId)) {
            return;
        }

        $mediaRun = $this->startMediaRun(
            $sqlRun->bale_id,
            $meta,
            $sqlRun->id,
            (int) ($meta['media_limit'] ?? 0),
        );

        // Record that the chain resolved (even when there was no media), so the
        // status endpoint knows it should stop waiting.
        $sqlRun->forceFill([
            'meta' => array_merge($meta, ['media_run_id' => $mediaRun?->id]),
        ])->save();
    }

    protected function mediaRunExists(string $sqlRunId): bool
    {
        return WpMigrationRun::where('parent_id', $sqlRunId)->exists();
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
        $mediaResolved = true;

        if ($run->type === 'sql') {
            $meta = $run->meta ?? [];
            $media = WpMigrationRun::where('parent_id', $run->id)->latest()->first();

            if ($media) {
                $mediaCounts = $this->counts($media);
                $mediaPending = (int) ($mediaCounts[WpMigrationItem::STATUS_PENDING] ?? 0);
                $mediaResolved = $mediaPending === 0;

                $mediaPayload = [
                    'id' => $media->id,
                    'status' => $media->status,
                    'total' => (int) $media->total,
                    'pending' => $mediaPending,
                    'counts' => $mediaCounts,
                ];
            } else {
                // Either the chain has not run yet (wait) or it ran and found
                // nothing to migrate (media_run_id explicitly null → resolved).
                $mediaResolved = array_key_exists('media_run_id', $meta)
                    && ($meta['media_run_id'] ?? null) === null;
            }
        }

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
            'errors' => $errors,
            'security' => $security,
            'done' => $pending === 0 && $mediaResolved,
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
}
