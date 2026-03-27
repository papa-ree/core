<?php

namespace Bale\Core\Livewire\Pages\RoleManagement;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\{Layout, Title};

#[Layout('core::layouts.app')]
#[Title('Create Role')]
class Create extends Component
{
    public $name;
    public $guard_name = 'web';

    public function mount()
    {
        if (!auth()->user()->can('role.create')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function save()
    {
        if (!auth()->user()->can('role.create')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required',
        ]);

        Role::create([
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ]);

        session()->flash('message', 'Role created successfully.');
        return $this->redirect(route('role.index'), navigate: true);
    }

    public function render()
    {
        return view('core::livewire.pages.role-management.create');
    }
}
