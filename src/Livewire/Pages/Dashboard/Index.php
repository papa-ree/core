<?php

namespace Bale\Core\Livewire\Pages\Dashboard;

use Livewire\Component;
use Livewire\Attributes\{Layout, Title};

#[Layout('core::layouts.app')]
#[Title('Dashboard')]
class Index extends Component
{
    public function render()
    {
        return view('core::livewire.pages.dashboard.index');
    }
}