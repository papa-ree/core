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

    #[On('deleteItem')]
    public function deleteLog($id)
    {
        // Check permission
        if (!auth()->user()->can('authentication-log.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $log = AuthenticationLog::findOrFail($id);
        $log->delete();

        $this->dispatch('toast', message: 'Log deleted successfully.', type: 'success');

        // Dispatch paginated event for table component
        $this->dispatch('paginated');
    }
}
