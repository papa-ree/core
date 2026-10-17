<?php

use Bale\Core\Jobs\Wp\MigrateWpThumbnailPostJob;
use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\MediaMigrationService;
use Bale\Core\Support\WpTenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| MigrateWpThumbnailPostJob
|--------------------------------------------------------------------------
|
| Backfills `posts.thumbnail` from the WordPress featured image. The post rows
| were imported by an earlier run (posts-only dump), so the job only touches
| the thumbnail column and must never overwrite an existing image.
|
*/

if (! function_exists('wp_thumbnail_test_tenant')) {
    function wp_thumbnail_test_tenant(): void
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

if (! function_exists('wp_thumbnail_test_posts_table')) {
    function wp_thumbnail_test_posts_table(): void
    {
        if (! Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('author')->nullable();
                $table->string('title');
                $table->string('slug')->unique();
                $table->json('content')->nullable();
                $table->string('thumbnail')->nullable();
                $table->boolean('published')->default(0);
                $table->timestamp('published_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('posts', 'thumbnail')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('thumbnail')->nullable();
            });
        }
    }
}

if (! function_exists('wp_thumbnail_test_post')) {
    function wp_thumbnail_test_post(string $title, ?string $thumbnail = null): string
    {
        $id = (string) Str::uuid();

        DB::table('posts')->insert([
            'id' => $id,
            'author' => (string) Str::uuid(),
            'title' => $title,
            'slug' => 'slug-'.Str::lower(Str::random(8)),
            'content' => json_encode(['blocks' => []]),
            'thumbnail' => $thumbnail,
            'published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('wp_thumbnail_test_run_item')) {
    /**
     * @param  array<string, mixed>  $meta
     * @return array{0: WpMigrationRun, 1: WpMigrationItem}
     */
    function wp_thumbnail_test_run_item(string $postId, string $url, array $meta = []): array
    {
        $run = WpMigrationRun::create([
            'bale_id' => 'bale-1',
            'type' => 'thumbnail',
            'status' => 'running',
            'total' => 1,
            'started_at' => now(),
            'meta' => array_merge(['slug' => 'dindik'], $meta),
        ]);

        $item = WpMigrationItem::create([
            'run_id' => $run->id,
            'post_id' => $postId,
            'status' => WpMigrationItem::STATUS_PENDING,
            'payload' => ['url' => $url],
            'result' => ['title' => 'judul'],
        ]);

        return [$run, $item];
    }
}

beforeEach(function () {
    wp_thumbnail_test_tenant();
    wp_thumbnail_test_posts_table();
});

it('skips a post that already has a thumbnail', function () {
    $postId = wp_thumbnail_test_post('Sudah punya thumbnail', 'lama.webp');
    [$run, $item] = wp_thumbnail_test_run_item($postId, 'https://dindik.test/wp-content/uploads/a.jpg', [
        'wp_base' => 'https://dindik.test',
    ]);

    (new MigrateWpThumbnailPostJob($run->id, $item->id))
        ->handle(app(WpTenant::class), app(MediaMigrationService::class));

    $item->refresh();

    expect($item->status)->toBe(WpMigrationItem::STATUS_SKIPPED)
        ->and($item->result['error'])->toBe('already has thumbnail')
        ->and(DB::table('posts')->where('id', $postId)->value('thumbnail'))->toBe('lama.webp');
});

it('skips a post that has no featured image in the dump', function () {
    $postId = wp_thumbnail_test_post('Tanpa featured image');
    [$run, $item] = wp_thumbnail_test_run_item($postId, '');

    (new MigrateWpThumbnailPostJob($run->id, $item->id))
        ->handle(app(WpTenant::class), app(MediaMigrationService::class));

    $item->refresh();

    expect($item->status)->toBe(WpMigrationItem::STATUS_SKIPPED)
        ->and($item->result['error'])->toBe('no featured image')
        ->and(DB::table('posts')->where('id', $postId)->value('thumbnail'))->toBeNull();
});

it('refuses to download from a host outside the configured base URL', function () {
    // A dump is untrusted input: `guid` must never turn the migration into an
    // SSRF vector. The job fails the item and leaves the column untouched.
    $postId = wp_thumbnail_test_post('Host tidak dikenal');
    [$run, $item] = wp_thumbnail_test_run_item($postId, 'https://evil.test/wp-content/uploads/x.jpg', [
        'wp_base' => 'https://dindik.test',
    ]);

    (new MigrateWpThumbnailPostJob($run->id, $item->id))
        ->handle(app(WpTenant::class), app(MediaMigrationService::class));

    $item->refresh();

    expect($item->status)->toBe(WpMigrationItem::STATUS_FAILED)
        ->and($item->result['error'])->toBe('thumbnail download failed')
        ->and(DB::table('posts')->where('id', $postId)->value('thumbnail'))->toBeNull();
});

it('is a no-op when the item was already processed', function () {
    $postId = wp_thumbnail_test_post('Sudah diproses');
    [$run, $item] = wp_thumbnail_test_run_item($postId, 'https://dindik.test/wp-content/uploads/a.jpg');
    $item->forceFill(['status' => WpMigrationItem::STATUS_MIGRATED])->save();

    (new MigrateWpThumbnailPostJob($run->id, $item->id))
        ->handle(app(WpTenant::class), app(MediaMigrationService::class));

    expect($item->fresh()->status)->toBe(WpMigrationItem::STATUS_MIGRATED)
        ->and(DB::table('posts')->where('id', $postId)->value('thumbnail'))->toBeNull();
});

it('stores the featured image as a webp thumbnail from a local uploads mirror', function () {
    if (! function_exists('imagewebp')) {
        $this->markTestSkipped('GD imagewebp tidak tersedia.');
    }

    $sourceDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bale-wp-thumb-'.Str::uuid();
    @mkdir($sourceDir.'/uploads/2024/06', 0777, true);

    $gd = imagecreatetruecolor(1200, 800);
    imagefill($gd, 0, 0, imagecolorallocate($gd, 30, 120, 200));
    imagejpeg($gd, $sourceDir.'/uploads/2024/06/foto.jpg', 90);
    imagedestroy($gd);

    $postId = wp_thumbnail_test_post('Berita dengan thumbnail');
    $url = 'https://dindik.test/wp-content/uploads/2024/06/foto.jpg';
    [$run, $item] = wp_thumbnail_test_run_item($postId, $url, [
        'wp_base' => 'https://dindik.test',
        'source_dir' => $sourceDir,
    ]);

    try {
        $before = DB::table('posts')->where('id', $postId)->value('updated_at');

        (new MigrateWpThumbnailPostJob($run->id, $item->id))
            ->handle(app(WpTenant::class), app(MediaMigrationService::class));

        $item->refresh();
        $thumbnail = DB::table('posts')->where('id', $postId)->value('thumbnail');

        expect($item->status)->toBe(WpMigrationItem::STATUS_MIGRATED)
            ->and($thumbnail)->toMatch('/^[0-9a-f-]{36}\.webp$/')
            ->and(Storage::disk('public')->exists('dindik/thumbnails/'.$thumbnail))->toBeTrue();

        // The original WordPress timestamp must be preserved.
        expect(DB::table('posts')->where('id', $postId)->value('updated_at'))->toBe($before);
    } finally {
        Storage::disk('public')->deleteDirectory('dindik/thumbnails');
        @unlink($sourceDir.'/uploads/2024/06/foto.jpg');
        @rmdir($sourceDir.'/uploads/2024/06');
        @rmdir($sourceDir.'/uploads/2024');
        @rmdir($sourceDir.'/uploads');
        @rmdir($sourceDir);
    }
});
