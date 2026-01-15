<?php

namespace Bale\Core\Livewire\Pages\UserManagement;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Support\Facades\Hash;

#[Layout('core::layouts.app')]
#[Title('Create User')]
class Create extends Component
{
    // Form fields
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    // Permission check on mount
    public function mount()
    {
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.user-management.create');
    }

    public function createUser()
    {
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        session()->flash('message', 'User created successfully.');

        // Redirect to edit page to assign roles and services
        return $this->redirect(route('user-management.edit', $user->id), navigate: true);
    }

    public function cancel()
    {
        return $this->redirect(route('user-management'), navigate: true);
    }
}
