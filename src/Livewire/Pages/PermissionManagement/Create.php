<?php

namespace Bale\Core\Livewire\Pages\PermissionManagement;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{Layout, Title};

#[Layout('core::layouts.app')]
#[Title('Create Permission')]
class Create extends Component
{
    public $name;
    public $guard_name = 'web';

    public function mount()
    {
        if (!auth()->user()->can('permission.create')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function save()
    {
        if (!auth()->user()->can('permission.create')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'required',
        ]);

        Permission::create([
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ]);

        session()->flash('message', 'Permission created successfully.');
        return $this->redirect(route('permission.index'), navigate: true);
    }

    public function render()
    {
        return view('core::livewire.pages.permission-management.create');
    }
}
