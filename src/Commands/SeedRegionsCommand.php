<?php

namespace Bale\Core\Commands;

use Bale\Core\Models\Region;
use Illuminate\Console\Command;
use JsonException;

class SeedRegionsCommand extends Command
{
    protected $signature = 'core:seed-regions {--file= : Path JSON alternatif (default: dataset Ponorogo bawaan package)}';

    protected $description = 'Sinkronkan master kecamatan/desa ke database landlord (idempotent)';

    public function handle(): int
    {
        $path = $this->option('file') ?: __DIR__.'/../../database/data/regions/ponorogo.json';

        if (! is_file($path)) {
            $this->error("Dataset tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error('Dataset bukan JSON valid: '.$e->getMessage());

            return self::FAILURE;
        }

        $now = now();
        $rows = [];

        foreach ((array) $data as $row) {
            if (! is_array($row)) {
                continue;
            }

            $clean = [
                'district_code' => trim((string) ($row['district_code'] ?? '')),
                'district_name' => trim((string) ($row['district_name'] ?? '')),
                'village_code' => trim((string) ($row['village_code'] ?? '')),
                'village_name' => trim((string) ($row['village_name'] ?? '')),
            ];

            if (in_array('', $clean, true)) {
                continue;
            }

            $rows[] = $clean + ['created_at' => $now, 'updated_at' => $now];
        }

        if ($rows === []) {
            $this->error('Dataset tidak berisi baris wilayah yang valid.');

            return self::FAILURE;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Region::upsert($chunk, ['village_code'], ['district_code', 'district_name', 'village_name', 'updated_at']);
        }

        $this->info('Master wilayah tersinkron: '.count($rows).' desa/kelurahan.');

        return self::SUCCESS;
    }
}
