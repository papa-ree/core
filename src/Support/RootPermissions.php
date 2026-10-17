<?php

namespace Bale\Core\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * Aturan permission untuk role `root`.
 *
 * Role `root` menerima seluruh permission platform, TETAPI tidak boleh
 * menerima permission yang khusus dimiliki role `guest` (guest-scope).
 * Pengecualian ini dipakai seragam oleh seluruh installer paket --
 * `core:install`, `core:permissions:sync`, `rakaca:install`, dan
 * `gupa-panel:install` -- supaya `root` tidak tercampur dengan identitas
 * `guest` (mis. ikut memiliki `guest.sidebar`).
 *
 * Catatan: `bale/api` sengaja standalone (tanpa `bale/core`), sehingga
 * `api:install` memakai daftar inline yang identik dengan konstanta ini.
 */
final class RootPermissions
{
    /**
     * Permission guest-scope yang TIDAK boleh dimiliki role `root`.
     *
     * @var array<int, string>
     */
    public const GUEST_SCOPED = [
        'guest.sidebar',
        'guest.dashboard',
        'select-bale',
    ];

    /**
     * Seluruh permission yang boleh dimiliki role `root` (semua permission
     * kecuali permission guest-scope).
     *
     * @return Collection<int, Permission>
     */
    public static function assignable(): Collection
    {
        return Permission::query()
            ->whereNotIn('name', self::GUEST_SCOPED)
            ->get();
    }
}
