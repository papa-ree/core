<?php

namespace Bale\Core\Commands;

use Bale\Core\Support\CorePermissions;
use Bale\Core\Support\RootPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sinkronisasi permission inti secara idempotent.
 *
 * Aman dijalankan berulang pada instalasi yang sudah berjalan (upgrade):
 * membuat permission baru milik bale/core (mis. `entrance-gate.*`) tanpa
 * perlu `core:install` interaktif, lalu menyinkronkan role `root` ke
 * seluruh permission platform kecuali permission guest-scope.
 *
 * Permission milik paket lain TIDAK dibuat di sini -- masing-masing paket
 * memiliki installer sendiri (`rakaca:install`, `api:install`, dst) --
 * namun bila permission tersebut sudah ada, ia tetap di-assign ke `root`.
 */
class SyncPermissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'core:permissions:sync';

    /**
     * @var string
     */
    protected $description = 'Sinkronkan permission bale/core ke database dan role root (idempotent)';

    public function handle(): int
    {
        $this->info('Sinkronisasi permission bale/core...');

        foreach (CorePermissions::ALL as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->info(count(CorePermissions::ALL).' permission inti dipastikan ada.');

        $rootRole = Role::firstOrCreate(['name' => 'root']);
        $rootRole->syncPermissions(RootPermissions::assignable());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Menu sidebar di-cache per user oleh MenuRegistry (TTL 5 menit);
        // bersihkan agar perubahan permission langsung terlihat.
        Cache::flush();

        $this->info('Role root berhasil disinkronkan.');
        $this->info('Permission guest-scope dikecualikan: '.implode(', ', RootPermissions::GUEST_SCOPED));
        $this->info('Cache permission & menu dibersihkan.');

        return self::SUCCESS;
    }
}
