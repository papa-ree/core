<?php

namespace Bale\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CoreProfileCheckCommand extends Command
{
    protected $signature = 'core:profile-check';

    protected $description = 'Periksa konfigurasi blind index dan isi tabel user_profiles';

    public function handle(): int
    {
        $ok = true;

        $key = trim((string) config('core.index_key'));

        if ($key === '') {
            $this->error('CORE_INDEX_KEY belum diisi di .env.');
            $ok = false;
        } elseif (hash_equals((string) config('app.key'), $key)) {
            $this->error('CORE_INDEX_KEY tidak boleh sama dengan APP_KEY.');
            $ok = false;
        } else {
            $this->info('CORE_INDEX_KEY terisi dan berbeda dari APP_KEY.');
        }

        if (! Schema::hasTable('user_profiles')) {
            $this->error('Tabel user_profiles belum ada. Jalankan migrasi.');

            return self::FAILURE;
        }

        $total = DB::table('user_profiles')->count();
        $withPhone = DB::table('user_profiles')->whereNotNull('phone')->count();

        $this->info("user_profiles: {$total} baris, {$withPhone} dengan phone, ".($total - $withPhone).' tanpa phone.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
