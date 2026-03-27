<?php

namespace Bale\Core\Livewire\Pages\AuthenticationLog;

use Bale\Core\Models\AuthenticationLog;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title, Computed, On};

#[Layout('core::layouts.app')]
#[Title('Authentication Log')]
class Index extends Component
{
    use WithPagination;

    // Search and pagination
    public $query = '';

    // Sorting
    public $sortField = 'login_at';
    public $sortDirection = 'desc';

    // Permission check on mount
    public function mount()
    {
        // Check permission
        if (!auth()->user()->can('authentication-log.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        // Check permission
        if (!auth()->user()->can('authentication-log.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.authentication-log.index');
    }

    #[Computed]
    public function logs()
    {
        return AuthenticationLog::query()
            ->with('authenticatable')
            ->when($this->query, function ($query) {
                $query->where(function ($q) {
                    $q->where('ip_address', 'like', '%' . $this->query . '%')
                        ->orWhere('user_agent', 'like', '%' . $this->query . '%')
                        ->orWhereHasMorph('authenticatable', ['App\Models\User'], function ($q) {
                            $q->where('name', 'like', '%' . $this->query . '%')
                                ->orWhere('email', 'like', '%' . $this->query . '%')
                                ->orWhere('username', 'like', '%' . $this->query . '%');
                        });
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
    public function deleteLog($id)
    {
        // Check permission
        if (!auth()->user()->can('authentication-log.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $log = AuthenticationLog::findOrFail($id);
        $log->delete();

        session()->flash('message', 'Log deleted successfully.');

        // Dispatch paginated event for table component
        $this->dispatch('paginated');
    }
}
