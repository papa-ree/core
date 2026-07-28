<?php

namespace Bale\Core\Livewire\Pages\PermissionManagement;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[Layout('core::layouts.app')]
#[Title('Permission Management')]
class Index extends Component
{
    public function mount()
    {
        if (! auth()->user()->can('permission.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()->can('permission.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.permission-management.index');
    }

    #[On('deleteItem')]
    public function deletePermission($id)
    {
        if (! auth()->user()->can('permission.delete')) {
            abort(403, 'Unauthorized action.');
        }

        Permission::findOrFail($id)->delete();

        $this->dispatch('toast', message: 'Permission deleted successfully.', type: 'success');
        $this->dispatch('paginated');
    }
}
