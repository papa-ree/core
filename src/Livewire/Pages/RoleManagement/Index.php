<?php

namespace Bale\Core\Livewire\Pages\RoleManagement;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\{Layout, Title, Computed, On};

#[Layout('core::layouts.app')]
#[Title('Role Management')]
class Index extends Component
{
    use WithPagination;

    public $query = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public function mount()
    {
        if (!auth()->user()->can('role.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (!auth()->user()->can('role.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.role-management.index');
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount('users')
            ->with('permissions')
            ->when($this->query, function ($query) {
                $query->where('name', 'like', '%' . $this->query . '%')
                    ->orWhere('guard_name', 'like', '%' . $this->query . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[On('deleteItem')]
    public function deleteRole($id)
    {
        if (!auth()->user()->can('role.delete')) {
            abort(403, 'Unauthorized action.');
        }

        Role::findOrFail($id)->delete();

        session()->flash('message', 'Role deleted successfully.');
        $this->dispatch('paginated');
    }
}
