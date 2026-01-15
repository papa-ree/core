<?php

namespace Bale\Core\Livewire\Pages\UserManagement;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title, Computed, On};

#[Layout('core::layouts.app')]
#[Title('User Management')]
class Index extends Component
{
    use WithPagination;

    // Search and pagination
    public $query = '';

    // Sorting
    public $sortField = 'name';
    public $sortDirection = 'asc';

    // Permission check on mount
    public function mount()
    {
        // Check permission
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        // Check permission
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.user-management.index');
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->query, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->query . '%')
                        ->orWhere('email', 'like', '%' . $this->query . '%')
                        ->orWhere('username', 'like', '%' . $this->query . '%');
                });
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
    public function deleteUser($id)
    {
        // Check permission
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user->delete();

        session()->flash('message', 'User deleted successfully.');

        // Dispatch paginated event for table component
        $this->dispatch('paginated');
    }
}
