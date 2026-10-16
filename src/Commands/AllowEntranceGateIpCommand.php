<?php

namespace Bale\Core\Commands;

use Bale\Core\Models\EntranceGateAllowlist;
use Bale\Core\Services\EntranceGateService;
use Illuminate\Console\Command;

class AllowEntranceGateIpCommand extends Command
{
    protected $signature = 'core:entrance-gate:allow {ip : Alamat IP atau CIDR} {--label= : Keterangan opsional}';

    protected $description = 'Tambahkan IP/CIDR ke allowlist halaman login (entrance.gate)';

    public function handle(EntranceGateService $service): int
    {
        $ip = trim((string) $this->argument('ip'));

        if (! $service->isValidEntry($ip)) {
            $this->error("IP/CIDR tidak valid: {$ip}");

            return self::FAILURE;
        }

        $label = $this->option('label');

        $entry = EntranceGateAllowlist::firstOrCreate(
            ['ip_address' => $ip],
            ['label' => $label, 'is_active' => true]
        );

        if (! $entry->wasRecentlyCreated) {
            $entry->is_active = true;

            if ($label !== null) {
                $entry->label = $label;
            }

            $entry->save();
        }

        $this->info(($entry->wasRecentlyCreated ? 'Ditambahkan' : 'Diaktifkan').": {$ip}");

        return self::SUCCESS;
    }
}
