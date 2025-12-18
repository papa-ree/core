<?php

namespace Bale\Core\Livewire\SharedComponents;

use Livewire\Component;
use Livewire\Attributes\{Layout};

class AccountDropdown extends Component
{
    public function render()
    {
        return view('core::livewire.shared-components.account-dropdown');
    }
}