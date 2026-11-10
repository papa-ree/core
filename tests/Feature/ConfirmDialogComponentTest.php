<?php

use Illuminate\Support\Facades\Blade;

describe('x-core::confirm-dialog', function () {
    it('merender trigger, judul, pesan, dan tombol konfirmasi', function () {
        $html = Blade::render(<<<'BLADE'
            <x-core::confirm-dialog title="Hapus berkas?" message="Berkas akan dihapus." confirm-label="Ya, Hapus">
                <x-slot name="trigger">
                    <button type="button" x-on:click="open = true">Hapus</button>
                </x-slot>
            </x-core::confirm-dialog>
            BLADE);

        expect($html)
            ->toContain('Hapus berkas?')
            ->toContain('Berkas akan dihapus.')
            ->toContain('Ya, Hapus')
            ->toContain('Batal')
            ->toContain('x-on:click="open = true"')
            ->toContain('role="dialog"')
            ->toContain('aria-modal="true"');
    });

    it('menjalankan method Livewire pada tombol konfirmasi', function () {
        $html = Blade::render('<x-core::confirm-dialog action="deleteItem" message="Yakin?" />');

        expect($html)->toContain('wire:click="deleteItem"');
    });

    it('mengirim event dengan parameter saat dipakai di dalam komponen lain', function () {
        $html = Blade::render(
            '<x-core::confirm-dialog event="cancelSubmission" :params="[\'id\' => \'abc\']" message="Yakin?" />'
        );

        expect($html)
            ->toContain("\$dispatch('cancelSubmission'")
            ->toContain('abc')
            ->not->toContain('wire:click="cancelSubmission"');
    });

    it('memakai warna danger dan ikon peringatan untuk aksi merusak', function () {
        $html = Blade::render('<x-core::confirm-dialog variant="danger" title="Tolak?" message="Yakin?" />');

        expect($html)
            ->toContain('bg-red-100')
            ->toContain('<svg');
    });
});
