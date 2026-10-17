<?php

use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Jobs\Wp\MigrateWpThumbnailPostJob;
use Bale\Core\Jobs\Wp\StartWpMediaRunJob;
use Bale\Core\Jobs\Wp\StartWpThumbnailRunJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Bale\Core\Support\WpTenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (! function_exists('wp_test_fake_tenant')) {
    function wp_test_fake_tenant(): void
    {
        app()->instance(WpTenant::class, new class extends WpTenant
        {
            public function connection(string $baleId): string
            {
                return (string) config('database.default');
            }

            public function slug(string $baleId): string
            {
                return 'dindik';
            }
        });
    }
}

if (! function_exists('wp_test_posts_table')) {
    function wp_test_posts_table(): void
    {
        if (Schema::hasTable('posts')) {
            return;
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('author')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('content')->nullable();
            $table->boolean('published')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}

if (! function_exists('wp_service_thumbnail_column')) {
    /** The shared test posts table predates `thumbnail`; add it when missing. */
    function wp_service_thumbnail_column(): void
    {
        if (! Schema::hasColumn('posts', 'thumbnail')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('thumbnail')->nullable();
            });
        }
    }
}

if (! function_exists('wp_service_post')) {
    function wp_service_post(string $title, string $slug, ?string $content = null, ?string $thumbnail = null): string
    {
        wp_service_thumbnail_column();

        $id = (string) Str::uuid();

        DB::table('posts')->insert([
            'id' => $id,
            'author' => (string) Str::uuid(),
            'title' => $title,
            'slug' => $slug,
            'content' => $content ?? json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'x']]]]),
            'thumbnail' => $thumbnail,
            'published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('wp_service_imported')) {
    /**
     * Mark every item of a run as imported onto the given tenant post ids.
     *
     * @param  array<int, string>  $postIds
     */
    function wp_service_imported(string $runId, array $postIds): void
    {
        $items = WpMigrationItem::where('run_id', $runId)->orderBy('wp_post_id')->get();

        foreach ($items as $i => $item) {
            $item->forceFill([
                'status' => WpMigrationItem::STATUS_IMPORTED,
                'post_id' => $postIds[$i] ?? null,
            ])->save();
        }
    }
}

beforeEach(function () {
    wp_test_fake_tenant();
    wp_test_posts_table();
});

it('creates a SQL run with one item per post and dispatches one job per item', function () {
    Bus::fake();

    $posts = [
        ['ID' => '1', 'post_title' => 'A', 'post_content' => '<p>A</p>'],
        ['ID' => '2', 'post_title' => 'B', 'post_content' => '<p>B</p>'],
    ];

    $run = app(WpMigrationRunService::class)->startSqlRun('bale-1', 'author-1', $posts, ['slug' => 'dindik']);

    expect($run)->toBeInstanceOf(WpMigrationRun::class);
    expect($run->total)->toBe(2);
    expect(WpMigrationItem::where('run_id', $run->id)->count())->toBe(2);

    $items = WpMigrationItem::where('run_id', $run->id)->get();
    expect($items->pluck('status')->unique()->all())->toBe([WpMigrationItem::STATUS_PENDING]);
    expect($items->pluck('wp_post_id')->all())->toBe(['1', '2']);

    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 2
            && $batch->jobs->first() instanceof ImportWpPostJob;
    });
});

it('stays pending until the media chain resolves, then reports done', function () {
    Bus::fake();

    $posts = [
        ['ID' => '1', 'post_title' => 'A', 'post_content' => '<p>A</p>'],
        ['ID' => '2', 'post_title' => 'B', 'post_content' => '<p>B</p>'],
    ];

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun('bale-1', 'author-1', $posts, ['slug' => 'dindik']);

    $progress = $service->progress($run);
    expect($progress['done'])->toBeFalse();
    expect($progress['run']['total'])->toBe(2);
    expect($progress['run']['pending'])->toBe(2);

    // Simulate every import job finishing successfully.
    WpMigrationItem::where('run_id', $run->id)->update(['status' => WpMigrationItem::STATUS_IMPORTED]);

    $service->chainMedia($run->id);
    $run->refresh();

    expect($run->status)->toBe('finished');
    expect($run->meta['media_run_id'])->toBeNull();
    expect($run->finished_at)->not->toBeNull();

    $progress = $service->progress($run->fresh());
    expect($progress['done'])->toBeTrue();
    expect($progress['run']['counts']['imported'])->toBe(2);
    expect($progress['errors'])->toBe([]);
});

it('collects failure messages in the progress payload', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun('bale-1', 'author-1', [['ID' => '1', 'post_title' => 'A']], ['slug' => 'dindik']);

    WpMigrationItem::where('run_id', $run->id)->first()->forceFill([
        'status' => WpMigrationItem::STATUS_FAILED,
        'result' => ['title' => 'A', 'error' => 'boom'],
    ])->save();

    $progress = $service->progress($run->fresh());
    expect($progress['errors'])->toHaveCount(1);
    expect($progress['errors'][0])->toContain('boom');
});

it('creates a media run only for posts that still have placeholders', function () {
    Bus::fake();

    $withMedia = (string) Str::uuid();
    $withoutMedia = (string) Str::uuid();

    DB::table('posts')->insert([
        [
            'id' => $withMedia,
            'author' => (string) Str::uuid(),
            'title' => 'With media',
            'slug' => 'with-media',
            'content' => json_encode(['blocks' => [['type' => 'image-placeholder', 'data' => ['wp_src' => 'http://example.test/a.jpg']]]]),
            'published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $withoutMedia,
            'author' => (string) Str::uuid(),
            'title' => 'No media',
            'slug' => 'no-media',
            'content' => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'x']]]]),
            'published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $run = app(WpMigrationRunService::class)->startMediaRun('bale-1', ['slug' => 'dindik']);

    expect($run)->not->toBeNull();
    expect($run->type)->toBe('media');
    expect($run->total)->toBe(1);

    $items = WpMigrationItem::where('run_id', $run->id)->get();
    expect($items)->toHaveCount(1);
    expect($items->first()->post_id)->toBe($withMedia);

    Bus::assertBatched(fn ($batch) => $batch->jobs->first() instanceof MigrateWpMediaPostJob);
});

it('returns null from startMediaRun when no post has placeholders', function () {
    Bus::fake();

    DB::table('posts')->insert([
        'id' => (string) Str::uuid(),
        'author' => (string) Str::uuid(),
        'title' => 'Plain',
        'slug' => 'plain',
        'content' => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'x']]]]),
        'published' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(app(WpMigrationRunService::class)->startMediaRun('bale-1', ['slug' => 'dindik']))->toBeNull();
});

it('registers the media chain on both then and finally callbacks', function () {
    Bus::fake();

    app(WpMigrationRunService::class)->startSqlRun('bale-1', 'author-1', [
        ['ID' => '1', 'post_title' => 'A', 'post_content' => '<p>A</p>'],
    ], ['slug' => 'dindik']);

    // `then` is never invoked by Batch::recordFailedJob, so relying on it alone
    // would leave the media stage unstarted whenever the last import job failed.
    Bus::assertBatched(fn ($batch) => ($batch->thenCallbacks()[0] ?? null) instanceof StartWpMediaRunJob
        && ($batch->finallyCallbacks()[0] ?? null) instanceof StartWpMediaRunJob);
});

it('starts the media stage only once even when the chain callback fires twice', function () {
    Bus::fake();

    DB::table('posts')->insert([
        'id' => (string) Str::uuid(),
        'author' => (string) Str::uuid(),
        'title' => 'With media',
        'slug' => 'with-media-once',
        'content' => json_encode(['blocks' => [['type' => 'image-placeholder', 'data' => ['wp_src' => 'http://example.test/a.jpg']]]]),
        'published' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun('bale-1', 'author-1', [
        ['ID' => '1', 'post_title' => 'A'],
    ], ['slug' => 'dindik']);

    // On the happy path the batch invokes both `then` and `finally`.
    $service->chainMedia($run->id);
    $service->chainMedia($run->id);

    expect(WpMigrationRun::where('parent_id', $run->id)->count())->toBe(1)
        ->and($run->fresh()->meta['media_run_id'])->not->toBeNull()
        ->and($run->fresh()->status)->toBe('finished');
});

it('resolves the chain without a media run when no post has placeholders', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun('bale-1', 'author-1', [
        ['ID' => '1', 'post_title' => 'A'],
    ], ['slug' => 'dindik']);

    // Simulate the import batch having imported every post.
    WpMigrationItem::where('run_id', $run->id)->update(['status' => WpMigrationItem::STATUS_IMPORTED]);

    // Invoked twice: `then` and `finally` both fire on the happy path, and the
    // "nothing to migrate" resolution must stay idempotent too.
    $service->chainMedia($run->id);
    $service->chainMedia($run->id);

    expect(WpMigrationRun::where('parent_id', $run->id)->count())->toBe(0)
        ->and($run->fresh()->meta['media_run_id'])->toBeNull()
        ->and($service->progress($run->fresh())['done'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// Thumbnail chain (SQL → media → featured images)
// ---------------------------------------------------------------------------

it('carries the featured image into the SQL item payload', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);

    $run = $service->createSqlRun(
        'bale-1',
        'author-1',
        [
            ['ID' => '1', 'post_title' => 'A'],
            ['ID' => '2', 'post_title' => 'B'],
        ],
        ['slug' => 'dindik'],
        [
            '1' => ['attachment_id' => '10', 'url' => 'https://old.test/a.jpg', 'mime' => 'image/jpeg'],
        ],
    );

    $withImage = WpMigrationItem::where('run_id', $run->id)->where('wp_post_id', '1')->first();
    expect($withImage->payload['thumbnail_url'])->toBe('https://old.test/a.jpg')
        ->and($withImage->payload['thumbnail_mime'])->toBe('image/jpeg');

    // A post without a featured image must stay untouched (no null keys).
    $withoutImage = WpMigrationItem::where('run_id', $run->id)->where('wp_post_id', '2')->first();
    expect($withoutImage->payload)->not->toHaveKey('thumbnail_url');
});

it('chains a thumbnail run after the SQL stage when there is no media', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);

    $run = $service->createSqlRun(
        'bale-1',
        'author-1',
        [
            ['ID' => '1', 'post_title' => 'A'],
            ['ID' => '2', 'post_title' => 'B'],
        ],
        ['slug' => 'dindik', 'wp_base' => 'https://old.test'],
        [
            '1' => ['attachment_id' => '10', 'url' => 'https://old.test/a.jpg', 'mime' => 'image/jpeg'],
            '2' => ['attachment_id' => '11', 'url' => 'https://old.test/b.jpg', 'mime' => 'image/jpeg'],
        ],
    );

    wp_service_imported($run->id, [wp_service_post('A', 'a'), wp_service_post('B', 'b')]);

    // Invoked twice: `then` and `finally` both fire on the happy path.
    $service->chainMedia($run->id);
    $service->chainMedia($run->id);

    $run->refresh();

    expect($run->meta['media_run_id'])->toBeNull()
        ->and($run->meta['thumbnail_run_id'])->not->toBeNull();

    $thumbnail = WpMigrationRun::where('parent_id', $run->id)->where('type', 'thumbnail')->first();

    expect($thumbnail)->not->toBeNull()
        ->and($thumbnail->total)->toBe(2)
        ->and($thumbnail->meta['wp_base'])->toBe('https://old.test')
        ->and($thumbnail->meta['source'])->toBe('chain');

    expect($thumbnail->parent_id)->toBe($run->id);

    $items = WpMigrationItem::where('run_id', $thumbnail->id)->get();
    expect($items)->toHaveCount(2)
        ->and($items->pluck('payload')->pluck('url')->all())
        ->toBe(['https://old.test/a.jpg', 'https://old.test/b.jpg']);

    Bus::assertBatched(fn ($batch) => $batch->jobs->first() instanceof MigrateWpThumbnailPostJob);

    // Not done while the thumbnail stage still has pending work.
    $progress = $service->progress($run->fresh());
    expect($progress['done'])->toBeFalse()
        ->and($progress['thumbnail']['total'])->toBe(2)
        ->and($progress['thumbnail']['pending'])->toBe(2);

    WpMigrationItem::where('run_id', $thumbnail->id)->update(['status' => WpMigrationItem::STATUS_MIGRATED]);

    expect($service->progress($run->fresh())['done'])->toBeTrue();
});

it('resolves the thumbnail stage to none for a posts-only dump', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun('bale-1', 'author-1', [['ID' => '1', 'post_title' => 'A']], ['slug' => 'dindik']);

    wp_service_imported($run->id, [wp_service_post('A', 'a')]);

    $service->chainMedia($run->id);

    $run->refresh();

    expect($run->meta['thumbnail_run_id'])->toBeNull()
        ->and(WpMigrationRun::where('parent_id', $run->id)->where('type', 'thumbnail')->count())->toBe(0)
        ->and($service->progress($run->fresh())['thumbnail'])->toBeNull()
        ->and($service->progress($run->fresh())['done'])->toBeTrue();
});

it('skips posts that already have a thumbnail', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);

    $run = $service->createSqlRun(
        'bale-1',
        'author-1',
        [['ID' => '1', 'post_title' => 'A']],
        ['slug' => 'dindik'],
        ['1' => ['attachment_id' => '10', 'url' => 'https://old.test/a.jpg', 'mime' => 'image/jpeg']],
    );

    wp_service_imported($run->id, [wp_service_post('A', 'a', null, 'already-there.webp')]);

    $service->chainMedia($run->id);
    $run->refresh();

    expect($run->meta['thumbnail_run_id'])->toBeNull()
        ->and(WpMigrationRun::where('parent_id', $run->id)->where('type', 'thumbnail')->count())->toBe(0);
});

it('ignores a featured image that does not point at an image mime type', function () {
    Bus::fake();

    $service = app(WpMigrationRunService::class);

    $run = $service->createSqlRun(
        'bale-1',
        'author-1',
        [['ID' => '1', 'post_title' => 'A']],
        ['slug' => 'dindik'],
        ['1' => ['attachment_id' => '10', 'url' => 'https://old.test/evil.pdf', 'mime' => 'application/pdf']],
    );

    wp_service_imported($run->id, [wp_service_post('A', 'a')]);

    $service->chainMedia($run->id);

    expect($run->fresh()->meta['thumbnail_run_id'])->toBeNull();
});

it('finishes the run when every chained stage is disabled', function () {
    Bus::fake();

    config([
        'core.wp_migration.chain_media' => false,
        'core.wp_migration.chain_thumbnails' => false,
    ]);

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun(
        'bale-1',
        'author-1',
        [['ID' => '1', 'post_title' => 'A']],
        ['slug' => 'dindik'],
        ['1' => ['attachment_id' => '10', 'url' => 'https://old.test/a.jpg', 'mime' => 'image/jpeg']],
    );

    // The import stage has to be finished as well for the run to report done.
    WpMigrationItem::where('run_id', $run->id)->update(['status' => WpMigrationItem::STATUS_IMPORTED]);

    $service->chainMedia($run->id);
    $run->refresh();

    expect(WpMigrationRun::where('parent_id', $run->id)->count())->toBe(0)
        ->and($run->status)->toBe('finished')
        ->and($run->meta['media_run_id'])->toBeNull()
        ->and($run->meta['thumbnail_run_id'])->toBeNull()
        ->and($service->progress($run->fresh())['done'])->toBeTrue();
});

it('still chains the thumbnail stage when only the media stage is disabled', function () {
    Bus::fake();

    config(['core.wp_migration.chain_media' => false]);

    $service = app(WpMigrationRunService::class);
    $run = $service->createSqlRun(
        'bale-1',
        'author-1',
        [['ID' => '1', 'post_title' => 'A']],
        ['slug' => 'dindik'],
        ['1' => ['attachment_id' => '10', 'url' => 'https://old.test/a.jpg', 'mime' => 'image/jpeg']],
    );

    wp_service_imported($run->id, [wp_service_post('A', 'a')]);

    $service->chainMedia($run->id);
    $run->refresh();

    expect(WpMigrationRun::where('parent_id', $run->id)->where('type', 'media')->count())->toBe(0)
        ->and($run->meta['media_run_id'])->toBeNull()
        ->and($run->meta['thumbnail_run_id'])->not->toBeNull();
});

it('registers the thumbnail chain on the media batch callbacks', function () {
    Bus::fake();

    wp_service_post(
        'With media',
        'with-media-cb',
        json_encode(['blocks' => [['type' => 'image-placeholder', 'data' => ['wp_src' => 'http://example.test/a.jpg']]]]),
    );

    app(WpMigrationRunService::class)->startMediaRun('bale-1', ['slug' => 'dindik'], 'sql-run-1');

    // The media batch is what starts the thumbnail stage, so both callbacks must
    // be the chaining one — not the plain FinalizeWpRunJob.
    Bus::assertBatched(fn ($batch) => ($batch->thenCallbacks()[0] ?? null) instanceof StartWpThumbnailRunJob
        && ($batch->finallyCallbacks()[0] ?? null) instanceof StartWpThumbnailRunJob);
});
