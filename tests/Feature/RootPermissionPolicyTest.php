<?php

/*
 | ---------------------------------------------------------------------------
 | Kebijakan permission role `root`
 | ---------------------------------------------------------------------------
 |
 | Role `root` menerima seluruh permission platform, TETAPI tidak boleh
 | menerima permission guest-scope (`guest.sidebar`, `guest.dashboard`,
 | `select-bale`). Lihat Bale\Core\Support\RootPermissions.
 |
 */

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function seedGuestScopedPermissions(): void
{
    foreach (['guest.sidebar', 'guest.dashboard', 'select-bale'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
}

it('core:install tidak memberi root permission guest-scope', function () {
    seedGuestScopedPermissions();

    $this->artisan('core:install')
        ->expectsQuestion('Select installation mode:', 'role permission only')
        ->assertExitCode(0);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $root = Role::where('name', 'root')->first();

    expect($root)->not->toBeNull()
        ->and($root->hasPermissionTo('entrance-gate.read'))->toBeTrue()
        ->and($root->hasPermissionTo('role.read'))->toBeTrue();

    foreach (['guest.sidebar', 'guest.dashboard', 'select-bale'] as $name) {
        expect($root->hasPermissionTo($name))->toBeFalse();
    }
});

it('core:permissions:sync idempotent dan mengecualikan permission guest-scope', function () {
    seedGuestScopedPermissions();

    $this->artisan('core:permissions:sync')->assertExitCode(0);
    $this->artisan('core:permissions:sync')->assertExitCode(0);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $root = Role::where('name', 'root')->first();

    expect($root)->not->toBeNull()
        ->and($root->hasPermissionTo('entrance-gate.read'))->toBeTrue()
        ->and($root->hasPermissionTo('role.read'))->toBeTrue();

    foreach (['guest.sidebar', 'guest.dashboard', 'select-bale'] as $name) {
        expect($root->hasPermissionTo($name))->toBeFalse();
    }
});
