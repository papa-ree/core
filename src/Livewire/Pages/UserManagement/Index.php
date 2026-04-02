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
    // Permission check on mount
    public function mount()
    {
        // Check permission
        if (!auth()->user()->can('user-management.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        // Check permission
        if (!auth()->user()->can('user-management.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.user-management.index');
    }

    #[On('deleteItem')]
    public function deleteUser($id)
    {
        // Check permission
        if (!auth()->user()->can('user-management.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        // Prevent deleting own account
        if ($user->id === auth()->id()) {
             $this->dispatch('toast', message: 'You cannot delete your own account.', type: 'error');
            return;
        }

        $user->delete();

        $this->dispatch('toast', message: 'User deleted successfully.', type: 'success');

        // Dispatch paginated event for table component
        $this->dispatch('paginated');
    }
}
