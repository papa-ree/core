<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Master wilayah (kecamatan + desa/kelurahan) pada database landlord.
 *
 * Sengaja tidak memakai UsesTenantConnection: satu dataset dipakai bersama
 * semua tenant, sementara koneksi tenant aktif hanya memengaruhi model tenant.
 */
class Region extends Model
{
    protected $fillable = [
        'district_code',
        'district_name',
        'village_code',
        'village_name',
    ];

    /**
     * @return Collection<int, static>
     */
    public static function districts(): Collection
    {
        return static::query()
            ->select('district_code', 'district_name')
            ->distinct()
            ->orderBy('district_name')
            ->get();
    }

    /**
     * @return Collection<int, static>
     */
    public static function villages(string $districtCode): Collection
    {
        return static::query()
            ->where('district_code', $districtCode)
            ->orderBy('village_name')
            ->get(['village_code', 'village_name']);
    }
}
