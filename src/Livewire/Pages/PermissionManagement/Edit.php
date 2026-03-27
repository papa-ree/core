<?php

namespace Bale\Core\Livewire\Pages\PermissionManagement;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{Layout, Title};

#[Layout('core::layouts.app')]
#[Title('Edit Permission')]
class Edit extends Component
{
    public $permissionId;
    public $name;
    public $guard_name;

    public function mount($id)
    {
        if (!auth()->user()->can('permission.update')) {
            abort(403, 'Unauthorized action.');
        }

        $permission = Permission::findOrFail($id);
        $this->permissionId = $permission->id;
        $this->name = $permission->name;
        $this->guard_name = $permission->guard_name;
    }

    public function save()
    {
        if (!auth()->user()->can('permission.update')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'name' => 'required|unique:permissions,name,' . $this->permissionId,
            'guard_name' => 'required',
        ]);

        $permission = Permission::findOrFail($this->permissionId);
        $permission->update([
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ]);

        session()->flash('message', 'Permission updated successfully.');
        return $this->redirect(route('permission.index'), navigate: true);
    }

    public function render()
    {
        return view('core::livewire.pages.permission-management.edit');
    }
}
