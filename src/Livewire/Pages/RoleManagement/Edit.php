<?php

namespace Bale\Core\Livewire\Pages\RoleManagement;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{Layout, Title, Computed};

#[Layout('core::layouts.app')]
#[Title('Edit Role')]
class Edit extends Component
{
    public $roleId;
    public $name;
    public $guard_name;

    // Permission assignment
    public $selectedPermissions = [];

    public function mount($id)
    {
        if (!auth()->user()->can('role.update')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::with('permissions')->findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->guard_name = $role->guard_name;

        // Load current permissions
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    #[Computed]
    public function availablePermissions()
    {
        return Permission::all();
    }

    public function save()
    {
        if (!auth()->user()->can('role.update')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'name' => 'required|unique:roles,name,' . $this->roleId,
            'guard_name' => 'required',
        ]);

        $role = Role::findOrFail($this->roleId);
        $role->update([
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ]);

        // Sync permissions
        $role->syncPermissions($this->selectedPermissions);

        session()->flash('message', 'Role updated successfully.');
        return $this->redirect(route('role.index'), navigate: true);
    }

    public function render()
    {
        return view('core::livewire.pages.role-management.edit');
    }
}
