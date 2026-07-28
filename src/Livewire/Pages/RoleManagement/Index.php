<?php

namespace Bale\Core\Livewire\Pages\RoleManagement;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('core::layouts.app')]
#[Title('Role Management')]
class Index extends Component
{
    public function mount()
    {
        if (! auth()->user()->can('role.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()->can('role.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.role-management.index');
    }

    #[On('deleteItem')]
    public function deleteRole($id)
    {
        if (! auth()->user()->can('role.delete')) {
            abort(403, 'Unauthorized action.');
        }

        Role::findOrFail($id)->delete();

        $this->dispatch('toast', message: 'Role deleted successfully.', type: 'success');
        $this->dispatch('paginated');
    }
}
