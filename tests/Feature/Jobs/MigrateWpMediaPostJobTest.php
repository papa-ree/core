<?php

use Bale\Core\Jobs\Wp\MigrateWpMediaPostJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Support\WpTenant;
use Illuminate\Database\Schema\Blueprint;
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

it('marks a post as skipped when it has no media placeholders left', function () {
    $postId = (string) Str::uuid();

    DB::table('posts')->insert([
        'id' => $postId,
        'author' => (string) Str::uuid(),
        'title' => 'Sudah bersih',
        'slug' => 'sudah-bersih',
        'content' => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Halo']]]]),
        'published' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $run = WpMigrationRun::create([
        'bale_id' => 'bale-1',
        'type' => 'media',
        'status' => 'running',
        'total' => 1,
        'started_at' => now(),
        'meta' => ['slug' => 'dindik'],
    ]);

    $item = WpMigrationItem::create([
        'run_id' => $run->id,
        'post_id' => $postId,
        'status' => WpMigrationItem::STATUS_PENDING,
        'result' => ['title' => 'Sudah bersih'],
    ]);

    (new MigrateWpMediaPostJob($run->id, $item->id))
        ->handle(app(WpTenant::class), app(MediaMigrationService::class));

    $item->refresh();

    expect($item->status)->toBe(WpMigrationItem::STATUS_SKIPPED);
    expect($item->result['migrated'])->toBe(0);

    // Content must be untouched (no placeholders, nothing to rewrite).
    $content = json_decode(DB::table('posts')->where('id', $postId)->value('content'), true);
    expect($content['blocks'][0]['type'])->toBe('paragraph');
});

it('is a no-op when the item was already processed', function () {
    $postId = (string) Str::uuid();

    DB::table('posts')->insert([
        'id' => $postId,
        'author' => (string) Str::uuid(),
        'title' => 'Sudah diproses',
        'slug' => 'sudah-diproses',
        'content' => json_encode(['blocks' => [['type' => 'image-placeholder', 'data' => ['wp_src' => 'http://example.test/a.jpg']]]]),
        'published' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $run = WpMigrationRun::create([
        'bale_id' => 'bale-1',
        'type' => 'media',
        'status' => 'running',
        'total' => 1,
        'started_at' => now(),
        'meta' => ['slug' => 'dindik'],
    ]);

    $item = WpMigrationItem::create([
        'run_id' => $run->id,
        'post_id' => $postId,
        'status' => WpMigrationItem::STATUS_MIGRATED,
        'result' => ['title' => 'Sudah diproses', 'migrated' => 1],
    ]);

    (new MigrateWpMediaPostJob($run->id, $item->id))
        ->handle(app(WpTenant::class), app(MediaMigrationService::class));

    expect($item->fresh()->status)->toBe(WpMigrationItem::STATUS_MIGRATED);

    // The placeholder must still be there — the job returned before doing work.
    $content = json_decode(DB::table('posts')->where('id', $postId)->value('content'), true);
    expect($content['blocks'][0]['type'])->toBe('image-placeholder');
});
