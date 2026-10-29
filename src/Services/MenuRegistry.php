<?php

namespace Bale\Core\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * MenuRegistry
 *
 * Singleton service yang bertanggung jawab mengumpulkan definisi menu
 * dari setiap package via file `src/menu.php`. Setiap package mendaftarkan
 * dirinya melalui `registerFromProvider()` yang dipanggil oleh CoreServiceProvider
 * saat aplikasi selesai boot.
 *
 * Format `menu.php` yang diharapkan:
 * ```php
 * return [
 *     'type'   => 'landlord',  // 'landlord' | 'tenant'
 *     'groups' => [
 *         [
 *             'key'   => 'my-package',
 *             'label' => 'My Package',
 *             'icon'  => 'box',
 *             'items' => [
 *                 // 'permission' => null berarti semua user bisa akses
 *                 // 'class'      => ClassName::class untuk cek class_exists (opsional)
 *                 // 'table'      => 'table_name' — untuk tenant menus, filter dilakukan
 *                 //                  oleh CmsSidebar.filterMenus() yg punya koneksi tenant
 *                 ['label' => 'Item', 'url' => 'path/to', 'icon' => 'circle', 'permission' => 'item.read'],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * Pemisahan tanggung jawab:
 * - MenuRegistry: filter permission + class_exists (aman saat boot, tanpa DB)
 * - CmsSidebar  : filter 'table' menggunakan koneksi tenant aktif per-request
 * - LandlordSidebar: hanya pakai getLandlordGroups(), tidak perlu table filter
 */
class MenuRegistry
{
    /** @var array<int, array{key: string, label: string, icon: string, items: array, _type: string}> */
    protected array $groups = [];

    /**
     * Tombol switch layout di sidebar landlord, didaftarkan package yang
     * punya panel kedua (mis. rakaca: landlord ↔ guest).
     *
     * @var array{label: string, icon?: string, route: string, permission?: string}|null
     */
    protected ?array $switcher = null;

    /**
     * Membaca dan mendaftarkan menu dari file `src/menu.php` milik provider yang diberikan.
     * Lokasi menu.php dideteksi secara otomatis via refleksi.
     */
    public function registerFromProvider(string $providerClass): void
    {
        if (! class_exists($providerClass)) {
            return;
        }

        try {
            $reflection = new \ReflectionClass($providerClass);
            $basePath = dirname($reflection->getFileName());

            // `menu.php`      -> landlord/tenant
            // `menu-guest.php`-> panel guest (rakaca), type => 'guest'
            $this->registerMenuFile($basePath.'/menu.php');
            $this->registerMenuFile($basePath.'/menu-guest.php');
        } catch (\Throwable $e) {
            // Gagal diam-diam agar tidak memblokir boot aplikasi
        }
    }

    /**
     * Baca satu file menu dan tambahkan grupnya ke registry.
     */
    protected function registerMenuFile(string $menuPath): void
    {
        if (! file_exists($menuPath)) {
            return;
        }

        $config = include $menuPath;

        if (! is_array($config)) {
            return;
        }

        // Backward compat: format lama (flat array tanpa key 'type' dan 'groups')
        // dilewati agar tidak error — format lama ditangani CmsSidebar secara terpisah.
        if (! isset($config['type']) || ! isset($config['groups'])) {
            return;
        }

        foreach ($config['groups'] as $group) {
            $this->groups[] = array_merge($group, [
                '_type' => $config['type'],
            ]);
        }
    }

    /**
     * Mengembalikan daftar grup menu untuk layout landlord,
     * sudah difilter berdasarkan permission dan class_exists.
     *
     * @return array<int, array{key: string, label: string, icon: string, items: array}>
     */
    public function getLandlordGroups(): array
    {
        return $this->resolveGroups('landlord');
    }

    /**
     * Mengembalikan daftar grup menu untuk layout tenant (CMS),
     * sudah difilter berdasarkan permission dan class_exists.
     *
     * @return array<int, array{key: string, label: string, icon: string, items: array}>
     */
    public function getTenantGroups(): array
    {
        return $this->resolveGroups('tenant');
    }

    public function getGuestGroups(): array
    {
        return $this->resolveGroups('guest');
    }

    /**
     * Daftarkan tombol switch layout untuk sidebar landlord.
     *
     * @param  array{label: string, icon?: string, route: string, permission?: string}|null  $switcher
     */
    public function setSwitcher(?array $switcher): void
    {
        $this->switcher = $switcher;
    }

    /**
     * @return array{label: string, icon?: string, route: string, permission?: string}|null
     */
    public function getSwitcher(): ?array
    {
        return $this->switcher;
    }

    /**
     * Mengambil semua grup berdasarkan type, kemudian filter item di dalamnya.
     * Grup yang tidak memiliki item setelah filter akan dibuang.
     */
    protected function resolveGroups(string $type): array
    {
        $userId = Auth::id() ?? 'guest';
        $cacheKey = "core.menu.{$type}.{$userId}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = [];

        foreach ($this->groups as $group) {
            if (($group['_type'] ?? '') !== $type) {
                continue;
            }

            $filteredItems = $this->filterItems($group['items'] ?? []);

            if (empty($filteredItems)) {
                continue;
            }

            $result[] = [
                'key' => $group['key'] ?? 'unknown',
                'label' => $group['label'] ?? 'Menu',
                'icon' => $group['icon'] ?? 'box',
                'items' => $filteredItems,
            ];
        }

        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Filter item menu berdasarkan:
     * - `class`:      cek `class_exists()` (opsional, untuk package opsional seperti Kosadata)
     * - `permission`: cek `auth()->check()` + `auth()->user()->can()` (null = semua boleh)
     *
     * CATATAN: filter 'table' (tenant DB) TIDAK dilakukan di sini karena
     * MenuRegistry tidak memiliki akses ke koneksi tenant. Filter 'table'
     * adalah tanggung jawab CmsSidebar.filterMenus() yang dipanggil dalam
     * konteks request dengan koneksi tenant sudah aktif.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function filterItems(array $items): array
    {
        // Optimasi: eager load permissions sekali per request + cache 5 menit per user per type
        static $permsCache = [];
        $userId = Auth::id() ?? 'guest';
        if (! isset($permsCache[$userId])) {
            $user = Auth::user();
            if ($user) {
                $user->loadMissing('roles', 'permissions');
                $permsCache[$userId] = $user->getAllPermissions()->pluck('name')->toArray();
            } else {
                $permsCache[$userId] = [];
            }
        }
        $perms = $permsCache[$userId];

        return array_values(array_filter($items, function (array $item) use ($perms): bool {
            if (isset($item['class']) && ! class_exists($item['class'])) {
                return false;
            }
            if (isset($item['permission']) && $item['permission'] !== null) {
                if (! in_array($item['permission'], $perms, true)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Reset registry (berguna untuk testing).
     */
    public function flush(): void
    {
        $this->groups = [];
    }
}
