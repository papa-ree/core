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
 * MigrateWpMediaPostJob
 *
 * Migrates every media placeholder of one post (WebP conversion, CDN upload,
 * security scan) and rewrites its EditorJS content. One job per post so a
 * single post with many assets cannot time out the whole run.
 *
 * Dispatched inside `Bus::batch()` by the WP migration run service, so it MUST
 * keep the {@see Batchable} trait — `PendingBatch` validates this when the batch
 * is constructed and throws a `RuntimeException` otherwise.
 */
class MigrateWpMediaPostJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;

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

            if (! $post) {
                $item->forceFill([
                    'status' => WpMigrationItem::STATUS_SKIPPED,
                    'result' => ['title' => $title, 'error' => 'post not found'],
                ])->save();

                return;
            }

            $content = is_string($post->content) ? json_decode($post->content, true) : (array) $post->content;

            if (! is_array($content)) {
                $item->forceFill([
                    'status' => WpMigrationItem::STATUS_FAILED,
                    'result' => ['title' => (string) $post->title, 'error' => 'invalid EditorJS content'],
                ])->save();

                return;
            }

            $result = $service->migrateContent($content, $slug);
            $blocked = (int) ($result['blocked'] ?? 0);

            $status = WpMigrationItem::STATUS_SKIPPED;

            if ($result['migrated'] > 0) {
                DB::connection($connection)
                    ->table('posts')
                    ->where('id', $post->id)
                    // Do NOT touch updated_at — keep the WordPress timestamps.
                    ->update([
                        'content' => json_encode($result['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);

                $status = WpMigrationItem::STATUS_MIGRATED;
            } elseif ($blocked > 0) {
                $status = WpMigrationItem::STATUS_BLOCKED;
            } elseif ($result['failed'] > 0) {
                $status = WpMigrationItem::STATUS_FAILED;
            }

            $item->forceFill([
                'status' => $status,
                'result' => [
                    'title' => Str::limit((string) $post->title, 60),
                    'migrated' => (int) $result['migrated'],
                    'failed' => (int) $result['failed'],
                    'blocked' => $blocked,
                    'error' => $result['errors'][0] ?? null,
                    'security' => $result['security'][0] ?? null,
                ],
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
