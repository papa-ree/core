<?php

use App\Models\User;

/**
 * Tombol "Kembali ke Dashboard" hanya masuk akal untuk user yang sudah login.
 * Tamu yang kena 404/419/500 tidak punya dashboard untuk dituju.
 */
it('menyembunyikan tombol dashboard di halaman error untuk tamu', function () {
    $this->withoutVite();

    foreach (['401', '403', '404', '419', '429', '500', '503'] as $code) {
        expect(view("core::errors.$code", ['exception' => null])->render())
            ->not->toContain('Kembali ke Dashboard');
    }
});

it('menampilkan tombol dashboard di halaman error untuk user login', function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());

    foreach (['401', '403', '404', '419', '429', '500', '503'] as $code) {
        expect(view("core::errors.$code", ['exception' => null])->render())
            ->toContain('Kembali ke Dashboard');
    }
});
