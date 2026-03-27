<?php

namespace Bale\Core\Livewire\SharedComponents;

use Livewire\Component;

class ItemActions extends Component
{
    public $editUrl;
    public $deleteId;
    public $modelName; // e.g. 'Post', 'Category' for event naming or confirmation
    public $confirmMessage;
    public $deleteEvent = 'deleteItem'; // Event to dispatch on parent (matches HasDeleteOption trait)
    public $navigate = true;

    public function mount($editUrl = null, $deleteId = null, $modelName = null, $confirmMessage = 'Are you sure you want to delete this item?', $deleteEvent = 'deleteItem', $navigate = true)
    {
        $this->editUrl = $editUrl;
        $this->deleteId = $deleteId;
        $this->modelName = $modelName;
        $this->confirmMessage = $confirmMessage;
        $this->deleteEvent = $deleteEvent;
        $this->navigate = $navigate;
    }

    public function delete()
    {
        $this->dispatch($this->deleteEvent, id: $this->deleteId);
    }

    public function render()
    {
        return view('core::livewire.shared-components.item-actions');
    }
}
