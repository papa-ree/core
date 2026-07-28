<?php

namespace Bale\Core\Livewire\Pages\ActivityLog;

use Bale\Core\Models\ActivityLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core::layouts.app')]
#[Title('Activity Log')]
class Index extends Component
{
    public function mount(): void
    {
        if (! auth()->user()->can('activity-log.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()->can('activity-log.read')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.activity-log.index');
    }

    #[On('deleteItem')]
    public function deleteLog($id): void
    {
        if (! auth()->user()->can('activity-log.delete')) {
            abort(403, 'Unauthorized action.');
        }

        ActivityLog::findOrFail($id)->delete();

        $this->dispatch('toast', message: 'Log deleted successfully.', type: 'success');
        $this->dispatch('paginated');
    }
}
