<?php

use Bale\Core\Models\Region;

it('memakai koneksi landlord, bukan koneksi tenant', function () {
    expect((new Region)->getConnection()->getName())->toBe(config('database.default'));
});

it('menyinkronkan dataset Ponorogo secara idempotent', function () {
    $this->artisan('core:seed-regions')->assertSuccessful();

    $total = Region::query()->count();

    expect($total)->toBeGreaterThan(250)
        ->and(Region::districts())->toHaveCount(21);

    $this->artisan('core:seed-regions')->assertSuccessful();

    expect(Region::query()->count())->toBe($total);
});

it('mengambil desa/kelurahan berdasarkan kode kecamatan', function () {
    $this->artisan('core:seed-regions')->assertSuccessful();

    $district = Region::districts()->firstOrFail();
    $villages = Region::villages($district->district_code);

    expect($villages)->not->toBeEmpty()
        ->and($villages->every(
            fn (Region $village) => str_starts_with($village->village_code, $district->district_code)
        ))->toBeTrue();
});

it('menolak dataset yang tidak ada', function () {
    $this->artisan('core:seed-regions', ['--file' => 'tidak-ada.json'])->assertFailed();
});
