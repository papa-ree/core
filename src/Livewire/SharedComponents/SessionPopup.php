<?php

namespace Bale\Core\Livewire\SharedComponents;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SessionPopup extends Component
{
    public bool $isLoggedIn = false;
    public ?string $userName = null;
    public ?string $dashboardUrl = null;

    public function mount(): void
    {
        $hasKeycloakSession = session()->has('keycloak_id_token');

        if ($hasKeycloakSession && Auth::check()) {
            $this->isLoggedIn = true;
            $this->userName = Auth::user()->name;

            try {
                $this->dashboardUrl = route('dashboard');
            } catch (\Exception $e) {
                $this->dashboardUrl = '/dashboard-selector';
            }
        }
    }

    public function render()
    {
        return view('core::livewire.shared-components.session-popup');
    }
}
