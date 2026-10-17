<?php

namespace Bale\Core\Support;

/**
 * Sumber tunggal daftar permission milik bale/core.
 *
 * Dipakai oleh `core:install` dan `core:permissions:sync` agar daftar
 * permission inti tidak terduplikasi di banyak tempat.
 */
final class CorePermissions
{
    /**
     * Seluruh permission yang dimiliki bale/core.
     *
     * @var array<int, string>
     */
    public const ALL = [
        'role.read',
        'role.create',
        'role.update',
        'role.delete',
        'permission.read',
        'permission.create',
        'permission.update',
        'permission.delete',
        'user-management.read',
        'user-management.create',
        'user-management.update',
        'user-management.delete',
        'authentication-log.read',
        'authentication-log.update',
        'authentication-log.delete',
        'activity-log.read',
        'activity-log.delete',
        'activity-log.export',
        'entrance-gate.read',
        'entrance-gate.create',
        'entrance-gate.update',
        'entrance-gate.delete',
        'dashboard',
    ];
}
