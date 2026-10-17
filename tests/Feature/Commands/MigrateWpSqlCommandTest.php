<?php

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
| core:migrate-wp-sql — chained stage guards
|--------------------------------------------------------------------------
|
| The import chains the media and featured-image stages automatically. The
| thumbnail stage only downloads from the host of `--wp-base` (or a local
| `--source-dir` mirror) because a dump is untrusted input — its `guid`
| values are never followed blindly. Without either basis every thumbnail
| would be rejected, so the command must fail fast instead of importing
| hundreds of posts that silently end up without an image.
|
*/

if (! function_exists('wp_sql_cmd_tenant')) {
    function wp_sql_cmd_tenant(): void
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

if (! function_exists('wp_sql_cmd_tables')) {
    function wp_sql_cmd_tables(): void
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
        }
    }
}

if (! function_exists('wp_sql_cmd_bale')) {
    function wp_sql_cmd_bale(): string
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

if (! function_exists('wp_sql_cmd_author')) {
    function wp_sql_cmd_author(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('users')->insert([
            'uuid' => $uuid,
            'name' => 'Bale Admin',
            'username' => 'baleadmin'.Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $uuid;
    }
}

if (! function_exists('wp_sql_cmd_dump')) {
    /**
     * A one-post dump; `$withFeaturedImage` adds `wp_postmeta._thumbnail_id`
     * plus the attachment row, which is what makes the dump a "full" export.
     */
    function wp_sql_cmd_dump(bool $withFeaturedImage = true): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bale-wp-sql-cmd');

        $sql = <<<'SQL'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_modified`, `post_content`, `post_title`, `post_type`, `post_status`, `post_name`, `guid`) VALUES
(101, 3, '2023-05-01 10:00:00', '2023-05-02 11:00:00', '<p>Halo dunia</p>', 'Berita Pertama', 'post', 'publish', 'berita-pertama', 'https://dindik.test/?p=101');
SQL;

        if ($withFeaturedImage) {
            $sql .= <<<'SQL'

INSERT INTO `wp_posts` (`ID`, `post_title`, `guid`, `post_type`, `post_mime_type`) VALUES
(55, 'Foto A', 'https://dindik.test/wp-content/uploads/2024/06/a.jpg', 'attachment', 'image/jpeg');
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(1, 101, '_thumbnail_id', '55');
SQL;
        }

        file_put_contents($path, $sql);

        return $path;
    }
}

beforeEach(function () {
    wp_sql_cmd_tenant();
    wp_sql_cmd_tables();
});

it('refuses to start when featured images have no source to download from', function () {
    $dump = wp_sql_cmd_dump();

    try {
        $exit = Artisan::call('core:migrate-wp-sql', [
            '--bale' => wp_sql_cmd_bale(),
            '--author' => wp_sql_cmd_author(),
            '--file' => $dump,
        ]);

        $output = Artisan::output();

        expect($exit)->toBe(1)
            ->and($output)->toContain('featured image')
            ->and($output)->toContain('--wp-base');

        // Nothing was created: the guard runs before the run row is written.
        expect(WpMigrationRun::count())->toBe(0);
    } finally {
        @unlink($dump);
    }
});

it('starts normally once a WordPress base URL is given', function () {
    Bus::fake();

    $dump = wp_sql_cmd_dump();

    try {
        $exit = Artisan::call('core:migrate-wp-sql', [
            '--bale' => wp_sql_cmd_bale(),
            '--author' => wp_sql_cmd_author(),
            '--file' => $dump,
            '--wp-base' => 'https://dindik.test',
        ]);

        expect($exit)->toBe(0);

        $run = WpMigrationRun::where('type', 'sql')->latest()->first();

        expect($run)->not->toBeNull()
            ->and($run->total)->toBe(1);
    } finally {
        @unlink($dump);
    }
});

it('does not need a source when the dump has no featured images at all', function () {
    Bus::fake();

    $dump = wp_sql_cmd_dump(withFeaturedImage: false);

    try {
        $exit = Artisan::call('core:migrate-wp-sql', [
            '--bale' => wp_sql_cmd_bale(),
            '--author' => wp_sql_cmd_author(),
            '--file' => $dump,
        ]);

        expect($exit)->toBe(0)
            ->and(Artisan::output())->toContain('posts-only dump');
    } finally {
        @unlink($dump);
    }
});

it('lets --no-chain-thumbnails import posts without a source', function () {
    Bus::fake();

    $dump = wp_sql_cmd_dump();

    try {
        $exit = Artisan::call('core:migrate-wp-sql', [
            '--bale' => wp_sql_cmd_bale(),
            '--author' => wp_sql_cmd_author(),
            '--file' => $dump,
            '--no-chain-thumbnails' => true,
        ]);

        expect($exit)->toBe(0)
            ->and(WpMigrationRun::where('type', 'sql')->count())->toBe(1);
    } finally {
        @unlink($dump);
    }
});
