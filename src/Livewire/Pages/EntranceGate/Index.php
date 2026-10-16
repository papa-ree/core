<?php

namespace Bale\Core\Livewire\Pages\EntranceGate;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman konfigurasi IP allowlist untuk form login (`entrance.gate`).
 *
 * Komponen ini hanya merakit header + tabel. Mengubah data dilakukan lewat
 * `Form` (create/edit) dan `Section\EntranceGateTable` (delete).
 */
#[Layout('core::layouts.app')]
#[Title('Login Allowlist')]
class Index extends Component
{
    public function mount(): void
    {
        if (! auth()->user()?->can('entrance-gate.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()?->can('entrance-gate.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.entrance-gate.index');
    }
}
