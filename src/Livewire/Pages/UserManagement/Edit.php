<?php

namespace Bale\Core\Livewire\Pages\UserManagement;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Computed};
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Paparee\Rakaca\Models\RakacaService;

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

    // Role assignment
    public $selectedRoles = [];

    // Service assignment
    public $selectedServices = [];

    // Active tab
    public $activeTab = 'basic-info';

    public function mount($id)
    {
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::with(['roles', 'services'])->findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;

        // Load current roles
        $this->selectedRoles = $user->roles->pluck('name')->toArray();

        // Load current services
        $this->selectedServices = $user->services->pluck('id')->toArray();
    }

    public function render()
    {
        if (!auth()->user()->can('user management')) {
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
        return Role::all();
    }

    #[Computed]
    public function availableServices()
    {
        return RakacaService::all();
    }

    public function updateBasicInfo()
    {
        if (!auth()->user()->can('user management')) {
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

        session()->flash('message', 'User basic info updated successfully.');
    }

    public function updateRoles()
    {
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($this->userId);

        // Sync roles using Spatie's syncRoles method
        $user->syncRoles($this->selectedRoles);

        session()->flash('message', 'User roles updated successfully.');
    }

    public function updateServices()
    {
        if (!auth()->user()->can('user management')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($this->userId);

        // Sync services using HasServiceRelation trait
        $user->services()->sync($this->selectedServices);

        session()->flash('message', 'User services updated successfully.');
    }

    public function cancel()
    {
        return $this->redirect(route('user-management'), navigate: true);
    }
}
