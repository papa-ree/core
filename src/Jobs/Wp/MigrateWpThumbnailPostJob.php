<?php

namespace Bale\Core\Jobs\Wp;

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Support\WpTenant;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * MigrateWpThumbnailPostJob
 *
 * Sets `posts.thumbnail` for one imported post from the WordPress featured
 * image (`wp_postmeta._thumbnail_id` → attachment `guid`). One job per post so
 * a slow image cannot time out a whole run.
 *
 * Dispatched inside `Bus::batch()`, so it MUST keep the {@see Batchable} trait.
 *
 * Idempotent: a post that already has a thumbnail is skipped, and an item that
 * is no longer `pending` returns immediately.
 */
class MigrateWpThumbnailPostJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $runId,
        public string $itemId,
    ) {}

    public function handle(WpTenant $tenant, MediaMigrationService $service): void
    {
        $run = WpMigrationRun::find($this->runId);
        $item = WpMigrationItem::find($this->itemId);

        if (! $run || ! $item || $item->status !== WpMigrationItem::STATUS_PENDING) {
            return;
        }

        $meta = $run->meta ?? [];
        $title = (string) ($item->result['title'] ?? '');

        try {
            $slug = (string) ($meta['slug'] ?? $tenant->slug($run->bale_id));

            $connection = $tenant->connection($run->bale_id);

            // Cdn::url() caches statically; reset it for this tenant.
            $tenant->cdn($slug);

            $this->configureService($service, $meta);

            $post = DB::connection($connection)->table('posts')->where('id', $item->post_id)->first();

            if (! $post || ($post->deleted_at ?? null) !== null) {
                $item->forceFill([
                    'status' => WpMigrationItem::STATUS_SKIPPED,
                    'result' => ['title' => $title, 'error' => 'post not found'],
                ])->save();

                return;
            }

            if (! empty($post->thumbnail)) {
                $item->forceFill([
                    'status' => WpMigrationItem::STATUS_SKIPPED,
                    'result' => ['title' => Str::limit((string) $post->title, 60), 'error' => 'already has thumbnail'],
                ])->save();

                return;
            }

            $url = (string) ($item->payload['url'] ?? '');

            if ($url === '') {
                $item->forceFill([
                    'status' => WpMigrationItem::STATUS_SKIPPED,
                    'result' => ['title' => Str::limit((string) $post->title, 60), 'error' => 'no featured image'],
                ])->save();

                return;
            }

            $filename = $service->storeThumbnailFromUrl($url, $slug);

            if ($filename === null || $filename === '') {
                $scan = $service->lastScan();
                $blocked = $scan !== null && $scan->isBlocked();

                $item->forceFill([
                    'status' => $blocked
                        ? WpMigrationItem::STATUS_BLOCKED
                        : WpMigrationItem::STATUS_FAILED,
                    'result' => [
                        'title' => Str::limit((string) $post->title, 60),
                        'error' => $blocked ? 'blocked by security scanner' : 'thumbnail download failed',
                        'security' => $blocked ? $scan->summary() : null,
                        'source' => $url,
                    ],
                ])->save();

                return;
            }

            DB::connection($connection)
                ->table('posts')
                ->where('id', $post->id)
                // Do NOT touch updated_at — keep the WordPress timestamp.
                ->update(['thumbnail' => $filename]);

            $item->forceFill([
                'status' => WpMigrationItem::STATUS_MIGRATED,
                'result' => ['title' => Str::limit((string) $post->title, 60), 'thumbnail' => $filename],
            ])->save();
        } catch (Throwable $e) {
            $item->forceFill([
                'status' => WpMigrationItem::STATUS_FAILED,
                'result' => ['title' => $title, 'error' => $e->getMessage()],
            ])->save();

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function configureService(MediaMigrationService $service, array $meta): void
    {
        if ($base = ($meta['wp_base'] ?? null)) {
            $service->setBaseUrl((string) $base);
        }

        if ($dir = ($meta['source_dir'] ?? null)) {
            $service->setSourceDir((string) $dir);
        }

        if ($quarantine = ($meta['quarantine_dir'] ?? null)) {
            $service->setQuarantineDir((string) $quarantine);
        }

        if (! empty($meta['strict'])) {
            $service->setStrict(true);
        }
    }
}
