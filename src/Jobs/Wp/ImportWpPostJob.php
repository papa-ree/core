<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\PostCleanerService;
use Bale\Core\Support\WpTenant;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * ImportWpPostJob
 *
 * Imports exactly one WordPress post (SQL stage) into the tenant's `posts`
 * table. Runs on the `wp-import` queue so a large dump never blocks a request.
 *
 * Dispatched inside `Bus::batch()` by the WP migration run service, so it MUST
 * keep the {@see Batchable} trait — `PendingBatch` validates this when the batch
 * is constructed and throws a `RuntimeException` otherwise.
 *
 * Idempotent: the row id is a UUIDv5 derived from the WordPress post id, and
 * the insert uses `insertOrIgnore`, so retries never duplicate a post.
 */
class ImportWpPostJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $runId,
        public string $itemId,
    ) {}

    public function handle(WpTenant $tenant, PostCleanerService $cleaner): void
    {
        $run = WpMigrationRun::find($this->runId);
        $item = WpMigrationItem::find($this->itemId);

        if (! $run || ! $item) {
            return;
        }

        // Already handled (retry / redelivery) → no-op.
        if ($item->status !== WpMigrationItem::STATUS_PENDING) {
            return;
        }

        $payload = $item->payload ?? [];
        $title = (string) ($payload['post_title'] ?? '');

        try {
            $connection = $tenant->connection($run->bale_id);
            $author = (string) ($run->meta['author'] ?? '');

            $slug = $this->resolveSlug($payload, (string) $item->wp_post_id);

            $content = json_encode(
                $cleaner->clean((string) ($payload['post_content'] ?? '')),
                JSON_UNESCAPED_UNICODE
            );

            // Preserve the original WordPress dates:
            //   post_date     → created_at + published_at
            //   post_modified → updated_at
            $publishedAt = $payload['post_date'] ?? now();
            $modifiedAt = $payload['post_modified'] ?? $publishedAt;

            $id = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'wp:'.$run->bale_id.':'.$item->wp_post_id);

            if ($this->slugTaken($connection, $slug, $id)) {
                $slug = $slug.'-'.Str::lower(Str::random(5));
            }

            DB::connection($connection)->table('posts')->insertOrIgnore([
                'id' => $id,
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'author' => $author,
                'published' => true,
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => $modifiedAt,
            ]);

            $item->forceFill([
                'status' => WpMigrationItem::STATUS_IMPORTED,
                'post_id' => $id,
                'result' => ['title' => Str::limit($title, 60), 'slug' => $slug],
            ])->save();
        } catch (Throwable $e) {
            $item->forceFill([
                'status' => WpMigrationItem::STATUS_FAILED,
                'result' => ['title' => Str::limit($title, 60), 'error' => $e->getMessage()],
            ])->save();

            throw $e;
        }
    }

    protected function slugTaken(string $connection, string $slug, string $exceptId): bool
    {
        return DB::connection($connection)
            ->table('posts')
            ->where('slug', $slug)
            ->where('id', '!=', $exceptId)
            ->exists();
    }

    /**
     * Resolve the post slug.
     *
     * WordPress' `post_name` wins whenever it exists so the migrated site keeps
     * the original permalinks (backlinks and search rankings stay intact).
     * `post_name` is stored URL-encoded for non-ASCII titles (`%f0%9f%8e%89…`),
     * so it is decoded back to real UTF-8 — a browser re-encodes it to exactly
     * the same URL. Only when it is missing do we fall back to the title.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function resolveSlug(array $payload, string $wpPostId): string
    {
        $wpSlug = trim(rawurldecode((string) ($payload['post_name'] ?? '')));

        if ($wpSlug !== '' && trim($wpSlug, '-') !== '') {
            return $wpSlug;
        }

        $slug = Str::slug((string) ($payload['post_title'] ?? ''));

        return $slug !== '' ? $slug : 'post-'.$wpPostId;
    }
}
