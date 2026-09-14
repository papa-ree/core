<?php

namespace Bale\Core\Livewire\SharedComponents;

use Bale\Core\Services\MenuRegistry;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('core::layouts.app')]
class LandlordSidebar extends Component
{
    public function render()
    {
        return view('core::livewire.shared-components.landlord-sidebar');
    }

    /**
     * Mengembalikan daftar grup menu untuk landlord layout.
     * Setiap grup berisi key, label, icon, dan items yang sudah difilter
     * berdasarkan permission dan ketersediaan class.
     *
     * @return array<int, array{key: string, label: string, icon: string, items: array}>
     */
    #[Computed(persist: true)]
    public function menuGroups(): array
    {
        return app(MenuRegistry::class)->getLandlordGroups();
    }
}
