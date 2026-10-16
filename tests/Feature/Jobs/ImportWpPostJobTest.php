<?php

use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\PostCleanerService;
use Bale\Core\Support\WpTenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

if (! function_exists('wp_test_make_sql_item')) {
    /**
     * @param  array<string, string|null>  $payload
     * @return array{0: WpMigrationRun, 1: WpMigrationItem}
     */
    function wp_test_make_sql_item(array $payload): array
    {
        $run = WpMigrationRun::create([
            'bale_id' => 'bale-1',
            'type' => 'sql',
            'status' => 'running',
            'total' => 1,
            'started_at' => now(),
            'meta' => ['author' => 'author-1', 'slug' => 'dindik'],
        ]);

        $item = WpMigrationItem::create([
            'run_id' => $run->id,
            'wp_post_id' => (string) ($payload['ID'] ?? '1'),
            'status' => WpMigrationItem::STATUS_PENDING,
            'payload' => $payload,
        ]);

        return [$run, $item];
    }
}

beforeEach(function () {
    wp_test_fake_tenant();
    wp_test_posts_table();
});

it('imports a post as EditorJS and preserves the WordPress dates', function () {
    [$run, $item] = wp_test_make_sql_item([
        'ID' => '77',
        'post_title' => 'Berita Pertama',
        'post_content' => '<p>Halo <strong>dunia</strong></p>',
        'post_date' => '2023-05-01 10:00:00',
        'post_modified' => '2023-05-02 11:00:00',
    ]);

    (new ImportWpPostJob($run->id, $item->id))->handle(app(WpTenant::class), app(PostCleanerService::class));

    $post = DB::table('posts')->first();

    expect($post)->not->toBeNull();
    expect($post->title)->toBe('Berita Pertama');
    expect($post->slug)->toBe('berita-pertama');
    expect((int) $post->published)->toBe(1);
    expect((string) $post->created_at)->toStartWith('2023-05-01');
    expect((string) $post->published_at)->toStartWith('2023-05-01');
    expect((string) $post->updated_at)->toStartWith('2023-05-02');

    $content = json_decode($post->content, true);
    expect($content)->toBeArray();
    expect($content['blocks'])->not->toBeEmpty();

    expect($item->fresh()->status)->toBe(WpMigrationItem::STATUS_IMPORTED);
    expect($item->fresh()->post_id)->toBe($post->id);
});

it('is idempotent when the same job is delivered twice', function () {
    [$run, $item] = wp_test_make_sql_item([
        'ID' => '78',
        'post_title' => 'Berita Idempoten',
        'post_content' => '<p>Isi</p>',
        'post_date' => '2023-05-01 10:00:00',
        'post_modified' => '2023-05-01 10:00:00',
    ]);

    $job = new ImportWpPostJob($run->id, $item->id);
    $job->handle(app(WpTenant::class), app(PostCleanerService::class));
    $job->handle(app(WpTenant::class), app(PostCleanerService::class));

    expect(DB::table('posts')->count())->toBe(1);
});

it('uses a deterministic post id derived from the WordPress id', function () {
    [$runA, $itemA] = wp_test_make_sql_item([
        'ID' => '99',
        'post_title' => 'Deterministik',
        'post_content' => '<p>A</p>',
        'post_date' => '2023-05-01 10:00:00',
        'post_modified' => '2023-05-01 10:00:00',
    ]);

    (new ImportWpPostJob($runA->id, $itemA->id))->handle(app(WpTenant::class), app(PostCleanerService::class));
    $firstId = DB::table('posts')->value('id');

    // A fresh run for the same WordPress post must resolve to the same id, so
    // a re-import never duplicates the row.
    [$runB, $itemB] = wp_test_make_sql_item([
        'ID' => '99',
        'post_title' => 'Deterministik',
        'post_content' => '<p>A</p>',
        'post_date' => '2023-05-01 10:00:00',
        'post_modified' => '2023-05-01 10:00:00',
    ]);

    (new ImportWpPostJob($runB->id, $itemB->id))->handle(app(WpTenant::class), app(PostCleanerService::class));

    expect(DB::table('posts')->count())->toBe(1);
    expect(DB::table('posts')->value('id'))->toBe($firstId);
});
