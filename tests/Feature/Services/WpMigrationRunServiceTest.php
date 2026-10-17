<?php

use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Jobs\Wp\StartWpMediaRunJob;
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
