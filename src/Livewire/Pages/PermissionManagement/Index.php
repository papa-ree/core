<?php

namespace Bale\Core\Livewire\Pages\PermissionManagement;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\{Layout, Title, Computed, On};

#[Layout('core::layouts.app')]
#[Title('Permission Management')]
class Index extends Component
{
    use WithPagination;

    public $query = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public function mount()
    {
        if (!auth()->user()->can('permission.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (!auth()->user()->can('permission.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.permission-management.index');
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->with('roles')
            ->when($this->query, function ($query) {
                $query->where('name', 'like', '%' . $this->query . '%')
                    ->orWhere('guard_name', 'like', '%' . $this->query . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
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
    public function deletePermission($id)
    {
        if (!auth()->user()->can('permission.delete')) {
            abort(403, 'Unauthorized action.');
        }

        Permission::findOrFail($id)->delete();

        session()->flash('message', 'Permission deleted successfully.');
        $this->dispatch('paginated');
    }
}
