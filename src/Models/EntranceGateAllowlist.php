<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Allowlist alamat IP untuk halaman form login (`entrance.gate`).
 *
 * Satu baris = satu alamat IP tunggal (`192.168.1.10`) atau sebuah CIDR
 * (`10.0.0.0/8`). Entri disimpan pada database default (landlord) karena
 * `bale/core` adalah fondasi, bukan bagian dari CMS multi-tenant.
 */
class EntranceGateAllowlist extends Model
{
    protected $table = 'entrance_gate_allowlist';

    protected $fillable = [
        'ip_address',
        'label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime:d M Y H:i',
        'updated_at' => 'datetime:d M Y H:i',
    ];

    /**
     * Hanya entri yang aktif yang dipakai untuk pencocokan.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
