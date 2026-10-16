<?php

namespace Bale\Core\Services;

use Bale\Core\Models\EntranceGateAllowlist;
use Illuminate\Support\Str;
use Throwable;

/**
 * Logika pembatasan akses halaman form login (`entrance.gate`).
 *
 * Sumber allowlist:
 *   1. `config('core.entrance_gate.allow')` — daftar statis (env), jalur darurat.
 *   2. Tabel `entrance_gate_allowlist` — dikelola lewat halaman "Login Allowlist".
 *
 * Perilaku fail-open: bila fitur nonaktif ATAU tidak ada satu pun entri,
 * semua IP diizinkan. Tujuannya agar admin tidak terkunci saat salah
 * konfigurasi. `127.0.0.1` dan `::1` selalu diizinkan sebagai jalur darurat.
 */
class EntranceGateService
{
    public function isEnabled(): bool
    {
        return (bool) config('core.entrance_gate.enabled', false);
    }

    /**
     * Daftar statis dari env, bisa berupa string dipisah koma atau array.
     *
     * @return array<int, string>
     */
    public function staticAllowlist(): array
    {
        $raw = config('core.entrance_gate.allow', []);

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        return array_values(array_filter(array_map(
            fn ($value): string => trim((string) $value),
            (array) $raw
        ), fn (string $value): bool => $value !== ''));
    }

    /**
     * Entri aktif dari database. Aman bila tabel belum ada (fail-open).
     *
     * @return array<int, string>
     */
    public function databaseAllowlist(): array
    {
        try {
            return EntranceGateAllowlist::query()
                ->where('is_active', true)
                ->pluck('ip_address')
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Apakah sebuah IP boleh mengakses halaman login?
     */
    public function isAllowed(?string $ip): bool
    {
        // Fitur nonaktif → semua boleh.
        if (! $this->isEnabled()) {
            return true;
        }

        $ip = $this->normalize($ip);

        if ($ip === null) {
            return false;
        }

        // Localhost selalu lolos (anti-lockout) bila diaktifkan.
        if (config('core.entrance_gate.allow_localhost', true) && $this->isLocalhost($ip)) {
            return true;
        }

        $entries = array_merge($this->staticAllowlist(), $this->databaseAllowlist());

        // Tidak ada entri sama sekali → fail-open.
        if ($entries === []) {
            return true;
        }

        foreach ($entries as $entry) {
            if ($this->matches($ip, (string) $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi bahwa sebuah nilai adalah IP tunggal atau CIDR yang sah.
     */
    public function isValidEntry(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (! Str::contains($value, '/')) {
            return $this->isIp($value);
        }

        [$subnet, $prefix] = array_pad(explode('/', $value, 2), 2, null);

        if ($subnet === null || $prefix === null || ! ctype_digit((string) $prefix)) {
            return false;
        }

        if (! $this->isIp($subnet)) {
            return false;
        }

        $maxBits = $this->isIpV4($subnet) ? 32 : 128;
        $prefix = (int) $prefix;

        return $prefix >= 0 && $prefix <= $maxBits;
    }

    /**
     * Cocokkan sebuah IP dengan satu entri (IP tunggal atau CIDR).
     */
    public function matches(string $ip, string $entry): bool
    {
        $entry = trim($entry);

        if ($entry === '') {
            return false;
        }

        if (! Str::contains($entry, '/')) {
            return hash_equals($entry, $ip);
        }

        return $this->matchesCidr($ip, $entry);
    }

    protected function matchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);

        if ($subnet === null || $prefix === null || ! ctype_digit((string) $prefix)) {
            return false;
        }

        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false) {
            return false;
        }

        // Cegah pencampuran keluarga alamat (IPv4 vs IPv6).
        if (strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $maxBits = strlen($ipBinary) * 8;

        if ((int) $prefix > $maxBits) {
            return false;
        }

        $bytes = intdiv((int) $prefix, 8);
        $bits = (int) $prefix % 8;

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($subnetBinary, 0, $bytes)) {
            return false;
        }

        if ($bits > 0) {
            $mask = (0xFF << (8 - $bits)) & 0xFF;

            return (ord($ipBinary[$bytes]) & $mask) === (ord($subnetBinary[$bytes]) & $mask);
        }

        return true;
    }

    /**
     * Normalisasi IP: rapi-trim, ubah IPv4-mapped IPv6 menjadi IPv4.
     */
    protected function normalize(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        $ip = trim($ip);

        if (Str::startsWith($ip, '::ffff:')) {
            $candidate = substr($ip, 7);

            if ($this->isIpV4($candidate)) {
                return $candidate;
            }
        }

        return $ip;
    }

    protected function isLocalhost(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1'], true);
    }

    protected function isIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function isIpV4(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
}
