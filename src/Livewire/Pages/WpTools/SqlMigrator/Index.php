<?php

namespace Bale\Core\Livewire\Pages\WpTools\SqlMigrator;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core::layouts.app')]
#[Title('WP SQL Migrator')]
class Index extends Component
{
    public function render()
    {
        return view('core::livewire.pages.wp-tools.sql-migrator.index');
    }
}
