<?php

namespace Bale\Core\Livewire\SharedComponents;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Bale\Cms\Models\BaleList;

class Topbar extends Component
{
    public function render()
    {
        return view('core::livewire.shared-components.topbar');
    }

    #[Computed]
    public function activeBale()
    {
        // Try to load bale if we are in tenant context
        $uuid = session('bale_active_uuid');

        if (!$uuid) {
            return null;
        }

        // We check if BaleList class exists to avoid crash if cms package is missing
        if (!class_exists('Bale\Cms\Models\BaleList')) {
            return null;
        }

        return BaleList::with('organization')->find($uuid);
    }
}