<?php

use Illuminate\Support\Facades\Blade;

describe('x-core::date-picker', function () {
    it('merender input baca-saja dengan popup kalender bertema Bale', function () {
        $html = Blade::render('<x-core::date-picker model="dari" mode="date" />');

        expect($html)
            ->toContain('baleDatePicker')
            ->toContain('readonly')
            ->toContain('role="dialog"')
            ->toContain('name="dari"')
            ->toContain('Hari ini')
            ->not->toContain('flatpickr');
    });

    it('hanya menampilkan input jam pada mode datetime', function () {
        expect(Blade::render('<x-core::date-picker model="dari" mode="date" />'))
            ->not->toContain('type="time"');

        expect(Blade::render('<x-core::date-picker model="mulai" mode="datetime" />'))
            ->toContain('type="time"');
    });

    it('meneruskan batas tanggal ke konfigurasi Alpine', function () {
        $html = Blade::render(
            '<x-core::date-picker model="dari" mode="date" min-date="2026-01-01" max-date="2026-12-31" />'
        );

        expect($html)
            ->toContain('2026-01-01')
            ->toContain('2026-12-31');
    });

    it('meneruskan atribut tambahan ke input', function () {
        $html = Blade::render('<x-core::date-picker model="dari" aria-label="Dari tanggal" />');

        expect($html)->toContain('aria-label="Dari tanggal"');
    });
});
