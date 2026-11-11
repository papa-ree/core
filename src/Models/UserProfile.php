<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Data profil user di luar Keycloak (nomor HP dan asal instansi).
 *
 * `phone` memakai cast `encrypted`, jadi `where('phone', ...)` tidak akan
 * pernah cocok. Pencarian lewat `wherePhone()` yang memakai blind index HMAC.
 */
class UserProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_uuid',
        'phone',
        'asal_instansi',
    ];

    protected $casts = [
        'phone' => 'encrypted',
    ];

    protected static function booted(): void
    {
        // Mutator `setPhoneAttribute` akan mem-bypass cast `encrypted`, jadi
        // normalisasi dan index dihitung di sini.
        static::saving(function (self $profile) {
            $profile->phone = static::normalize($profile->phone);
            $profile->phone_index = static::index($profile->phone);
        });
    }

    public function scopeWherePhone($query, ?string $phone)
    {
        $index = static::index($phone);

        return $index === null ? $query->whereRaw('1 = 0') : $query->where('phone_index', $index);
    }

    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }

    protected static function index(?string $phone): ?string
    {
        $normalized = static::normalize($phone);

        return $normalized === null ? null : hash_hmac('sha256', $normalized, static::indexKey());
    }

    /**
     * Sengaja tidak fallback ke APP_KEY: kunci yang sama untuk enkripsi dan
     * blind index membuat dump tabel cukup untuk mencoba mendekripsi `phone`.
     */
    protected static function indexKey(): string
    {
        $key = trim((string) config('core.index_key'));

        if ($key === '') {
            throw new \RuntimeException('CORE_INDEX_KEY belum diisi di .env.');
        }

        return $key;
    }
}
