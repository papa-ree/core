<?php

use Bale\Core\Models\WpMigrationItem;
use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Support\WpTenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| core:migrate-wp-thumbnails
|--------------------------------------------------------------------------
|
| Backfills `posts.thumbnail` for posts that were imported from a posts-only
| dump. The mapping post ↔ WordPress id comes from the SQL run items, and the
| featured image from `wp_postmeta._thumbnail_id` + attachment `guid`.
|
*/

if (! function_exists('wp_thumb_cmd_tenant')) {
    function wp_thumb_cmd_tenant(): void
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

if (! function_exists('wp_thumb_cmd_tables')) {
    function wp_thumb_cmd_tables(): void
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
        } elseif (! Schema::hasColumn('posts', 'thumbnail')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('thumbnail')->nullable();
            });
        }
    }
}

if (! function_exists('wp_thumb_cmd_bale')) {
    function wp_thumb_cmd_bale(): string
    {
        $id = (string) Str::uuid();

        DB::table('bale_lists')->insert([
            'id' => $id,
            'organization_id' => (string) Str::uuid(),
            'name' => 'Dinas Pendidikan',
            'slug' => 'dindik',
            'database_host' => '127.0.0.1',
            'database_name' => 'bale_dindik_'.Str::lower(Str::random(6)),
            'database_username' => 'root',
            'database_password' => 'secret',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('wp_thumb_cmd_post')) {
    function wp_thumb_cmd_post(string $title, ?string $thumbnail = null): string
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

if (! function_exists('wp_thumb_cmd_sql_run')) {
    /**
     * Create a finished SQL run whose items map WordPress ids to tenant posts.
     *
     * @param  array<int, array{wp: string, post: string}>  $links
     */
    function wp_thumb_cmd_sql_run(string $baleId, array $links): WpMigrationRun
    {
        $run = WpMigrationRun::create([
            'bale_id' => $baleId,
            'type' => 'sql',
            'status' => 'finished',
            'total' => count($links),
            'started_at' => now(),
            'finished_at' => now(),
            'meta' => ['slug' => 'dindik'],
        ]);

        foreach ($links as $link) {
            WpMigrationItem::create([
                'run_id' => $run->id,
                'wp_post_id' => $link['wp'],
                'post_id' => $link['post'],
                'status' => WpMigrationItem::STATUS_IMPORTED,
                'result' => ['title' => 'judul'],
            ]);
        }

        return $run;
    }
}

if (! function_exists('wp_thumb_cmd_dump')) {
    function wp_thumb_cmd_dump(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bale-wp-dump');

        file_put_contents($path, <<<'SQL'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(1, 101, '_thumbnail_id', '55'),
(2, 102, '_thumbnail_id', '57'),
(3, 103, '_thumbnail_id', '59');
INSERT INTO `wp_posts` (`ID`, `post_title`, `guid`, `post_type`, `post_mime_type`) VALUES
(55, 'Foto A', 'https://dindik.test/wp-content/uploads/2024/06/a.jpg', 'attachment', 'image/jpeg'),
(57, 'Foto B', 'https://dindik.test/wp-content/uploads/2024/06/b.jpg', 'attachment', 'image/jpeg'),
(59, 'Foto C', 'https://dindik.test/wp-content/uploads/2024/06/c.jpg', 'attachment', 'image/jpeg');
SQL);

        return $path;
    }
}

beforeEach(function () {
    wp_thumb_cmd_tenant();
    wp_thumb_cmd_tables();
});

it('reports the backfill plan in dry-run without touching the database', function () {
    $baleId = wp_thumb_cmd_bale();

    $withThumb = wp_thumb_cmd_post('Sudah ada thumbnail', 'lama.webp');
    $withoutThumb = wp_thumb_cmd_post('Belum ada thumbnail');

    // 101 → already has a thumbnail, 102 → will be filled, 103 → not imported.
    wp_thumb_cmd_sql_run($baleId, [
        ['wp' => '101', 'post' => $withThumb],
        ['wp' => '102', 'post' => $withoutThumb],
    ]);

    $dump = wp_thumb_cmd_dump();

    try {
        $exit = Artisan::call('core:migrate-wp-thumbnails', [
            '--bale' => $baleId,
            '--file' => $dump,
            '--wp-base' => 'https://dindik.test',
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        expect($exit)->toBe(0)
            ->and($output)->toContain('Will be set')
            ->and($output)->toContain('Dry run complete');

        expect(DB::table('posts')->where('id', $withThumb)->value('thumbnail'))->toBe('lama.webp')
            ->and(DB::table('posts')->where('id', $withoutThumb)->value('thumbnail'))->toBeNull();
    } finally {
        @unlink($dump);
    }
});

it('dispatches one queued job per post that needs a thumbnail', function () {
    Bus::fake();

    $baleId = wp_thumb_cmd_bale();
    $postA = wp_thumb_cmd_post('Berita A');
    $postB = wp_thumb_cmd_post('Berita B');

    wp_thumb_cmd_sql_run($baleId, [
        ['wp' => '101', 'post' => $postA],
        ['wp' => '102', 'post' => $postB],
    ]);

    $dump = wp_thumb_cmd_dump();

    try {
        $exit = Artisan::call('core:migrate-wp-thumbnails', [
            '--bale' => $baleId,
            '--file' => $dump,
            '--wp-base' => 'https://dindik.test',
        ]);

        expect($exit)->toBe(0);

        $run = WpMigrationRun::where('type', 'thumbnail')->latest()->first();

        expect($run)->not->toBeNull()
            ->and($run->total)->toBe(2)
            ->and(WpMigrationItem::where('run_id', $run->id)->count())->toBe(2);

        Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2);
    } finally {
        @unlink($dump);
    }
});

it('fails clearly when the dump has no wp_postmeta (posts-only export)', function () {
    $baleId = wp_thumb_cmd_bale();

    $dump = tempnam(sys_get_temp_dir(), 'bale-wp-only');
    file_put_contents($dump, "INSERT INTO `wp_posts` (`ID`, `post_title`, `post_type`) VALUES (1, 'A', 'post');");

    try {
        $exit = Artisan::call('core:migrate-wp-thumbnails', [
            '--bale' => $baleId,
            '--file' => $dump,
            '--wp-base' => 'https://dindik.test',
        ]);

        expect($exit)->toBe(1)
            ->and(Artisan::output())->toContain('posts-only');
    } finally {
        @unlink($dump);
    }
});

it('refuses to run without a base URL or a local uploads mirror', function () {
    $baleId = wp_thumb_cmd_bale();
    $dump = wp_thumb_cmd_dump();

    try {
        $exit = Artisan::call('core:migrate-wp-thumbnails', [
            '--bale' => $baleId,
            '--file' => $dump,
        ]);

        expect($exit)->toBe(1)
            ->and(Artisan::output())->toContain('--wp-base');
    } finally {
        @unlink($dump);
    }
});
