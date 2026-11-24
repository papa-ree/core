<?php

/*
 | ---------------------------------------------------------------------------
 | Menu sidebar harus langsung mengikuti perubahan role/permission
 | ---------------------------------------------------------------------------
 |
 | Dulu hasil filter menu di-cache 5 menit per user, sehingga user yang baru
 | di-assign role (atau role yang baru diberi permission) masih melihat menu
 | lama sampai `optimize:clear` dijalankan di server.
 |
 */

use App\Models\User;
use Bale\Core\Services\MenuRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function freshMenuRegistry(string $permission): MenuRegistry
{
    $registry = new MenuRegistry;

    (function () use ($permission) {
        $this->groups = [[
            'key' => 'test',
            'label' => 'Test',
            'icon' => 'box',
            '_type' => 'landlord',
            'items' => [[
                'label' => 'Fitur',
                'url' => 'fitur',
                'icon' => 'circle',
                'permission' => $permission,
            ]],
        ]];
    })->call($registry);

    return $registry;
}

it('menu muncul begitu user di-assign role tanpa optimize:clear', function () {
    Permission::firstOrCreate(['name' => 'menu-fresh.read', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'menu-fresh-role', 'guard_name' => 'web']);
    $role->givePermissionTo('menu-fresh.read');

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(freshMenuRegistry('menu-fresh.read')->getLandlordGroups())->toBe([]);

    $user->assignRole($role);

    $groups = freshMenuRegistry('menu-fresh.read')->getLandlordGroups();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['items'][0]['label'])->toBe('Fitur');
});

it('menu muncul begitu role user diberi permission tanpa optimize:clear', function () {
    Permission::firstOrCreate(['name' => 'menu-fresh.update', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'menu-fresh-editor', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    expect(freshMenuRegistry('menu-fresh.update')->getLandlordGroups())->toBe([]);

    $role->givePermissionTo('menu-fresh.update');

    // Request berikutnya: relasi role yang sudah termuat tidak boleh ikut terbawa.
    $this->actingAs($user->fresh());

    expect(freshMenuRegistry('menu-fresh.update')->getLandlordGroups())->toHaveCount(1);
});
