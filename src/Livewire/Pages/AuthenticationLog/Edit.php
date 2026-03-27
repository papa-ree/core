<?php

namespace Bale\Core\Livewire\Pages\AuthenticationLog;

use Bale\Core\Models\AuthenticationLog;
use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Computed};

#[Layout('core::layouts.app')]
#[Title('Edit Authentication Log')]
class Edit extends Component
{
    public $logId;
    public $ip_address;
    public $user_agent;
    public $login_at;
    public $logout_at;

    public function mount($id)
    {
        if (!auth()->user()->can('authentication-log.update')) {
            abort(403, 'Unauthorized action.');
        }

        $log = AuthenticationLog::findOrFail($id);

        $this->logId = $log->id;
        $this->ip_address = $log->ip_address;
        $this->user_agent = $log->user_agent;
        $this->login_at = $log->login_at?->format('Y-m-d\TH:i');
        $this->logout_at = $log->logout_at?->format('Y-m-d\TH:i');
    }

    public function render()
    {
        if (!auth()->user()->can('authentication-log.update')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.authentication-log.edit');
    }

    #[Computed]
    public function log()
    {
        return AuthenticationLog::with('authenticatable')->findOrFail($this->logId);
    }

    public function update()
    {
        if (!auth()->user()->can('authentication-log.update')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validate([
            'ip_address' => ['required', 'string', 'max:45'],
            'user_agent' => ['nullable', 'string'],
            'login_at' => ['nullable', 'date'],
            'logout_at' => ['nullable', 'date'],
        ]);

        $log = AuthenticationLog::findOrFail($this->logId);
        $log->update($validated);

        session()->flash('message', 'Authentication log updated successfully.');

        return $this->redirect(route('authentication-log'), navigate: true);
    }

    public function cancel()
    {
        return $this->redirect(route('authentication-log'), navigate: true);
    }
}
