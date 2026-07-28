<?php

namespace Bale\Core\Livewire\Pages\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core::layouts.app')]
#[Title('Dashboard')]
class Index extends Component
{
    public function render()
    {
        return view('core::livewire.pages.dashboard.index');
    }
}
