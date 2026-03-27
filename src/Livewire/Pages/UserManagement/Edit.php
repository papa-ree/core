<?php

namespace Bale\Core\Livewire\Pages\UserManagement;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Computed};
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

#[Layout('core::layouts.app')]
#[Title('Edit User')]
class Edit extends Component
{
    public $userId;
    public $name;
    public $username;
    public $email;
    public $password;
    public $password_confirmation;

    // Role assignment — one user can only hold one role
    public $selectedRole = '';

    // Active tab
    public $activeTab = 'profile';

    public function mount($id)
    {
        if (!auth()->user()->can('user-management.update')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::with('roles')->findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;

        // Load current role (single)
        $this->selectedRole = $user->roles->first()?->name ?? '';
    }

    public function render()
    {
        if (!auth()->user()->can('user-management.update')) {
            abort(403, 'Unauthorized action.');
        }

        return view('core::livewire.pages.user-management.edit');
    }

    #[Computed]
    public function user()
    {
        return User::findOrFail($this->userId);
    }

    #[Computed]
    public function availableRoles()
    {
        return Role::with('permissions')->get();
    }

    public function updateBasicInfo()
    {
        if (!auth()->user()->can('user-management.update')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::findOrFail($this->userId);

        $data = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        $this->dispatch('toast', message: 'User basic info updated successfully.', type: 'success');
    }

    public function updateRoles()
    {
        if (!auth()->user()->can('user-management.update')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($this->userId);

        // Sync single role using Spatie's syncRoles method
        $user->syncRoles(array_filter([$this->selectedRole]));

        $this->dispatch('toast', message: 'User roles updated successfully.', type: 'success');
    }

    public function cancel()
    {
        return $this->redirect(route('user-management'), navigate: true);
    }
}
