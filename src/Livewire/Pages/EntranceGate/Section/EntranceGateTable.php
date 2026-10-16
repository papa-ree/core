<?php

namespace Bale\Core\Livewire\Pages\EntranceGate\Section;

use Bale\Core\Models\EntranceGateAllowlist;
use Bale\Core\Traits\HasDeleteOption;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tabel allowlist login.
 *
 * 🔴 Delete memakai event khusus `deleteEntranceGateIp`, bukan `deleteItem`.
 *
 * Komponen `core-shared-components::data-table` juga mendengarkan `deleteItem`
 * dan menghapus baris tanpa otorisasi. Bila kita memakai event bawaan, satu
 * klik delete akan dijalankan dua kali (sekali oleh tabel generik, sekali oleh
 * trait). Dengan event khusus, hanya komponen ini yang menangani -- lengkap
 * dengan cek permission.
 */
class EntranceGateTable extends Component
{
    use HasDeleteOption;

    protected string $modelClass = EntranceGateAllowlist::class;

    public function mount(): void
    {
        if (! auth()->user()?->can('entrance-gate.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    #[On('deleteEntranceGateIp')]
    public function deleteEntranceGateIp(int|string $id): void
    {
        if (! auth()->user()?->can('entrance-gate.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $this->deleteItem($id);
    }

    public function render()
    {
        if (! auth()->user()?->can('entrance-gate.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.entrance-gate.section.entrance-gate-table');
    }
}
