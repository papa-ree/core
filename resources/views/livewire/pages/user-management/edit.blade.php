<div>
    {{--
    |--------------------------------------------------------------------------
    | Edit User Page
    |--------------------------------------------------------------------------
    | Layout: Two-column split
    | Left → Profile card (avatar, name, meta info, account stats, permissions)
    | Right → Tabbed edit forms (Profile · Security · Role)
    |
    | Removed: Services tab (replaced with user info on the sidebar)
    | Role: single selection via radio
    --}}
    
    <div x-data="{ activeTab: @entangle('activeTab') }">
    
        <x-core::breadcrumb :items="[['label' => __('Users'), 'route' => 'user-management']]" :active="__('Edit User')" />
    
        {{-- Toast notifications are handled globally via dispatch('toast') --}}
    
        {{-- =====================================================================
        Main split layout: sidebar (left) + editor (right)
        ===================================================================== --}}
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 items-start">
    
            {{-- ========================
            LEFT: Profile Sidebar
            ======================== --}}
            <div class="space-y-4 lg:sticky lg:top-24">
    
                {{-- Profile Card --}}
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    
                    {{-- Avatar section --}}
                    <div class="relative h-20 bg-linear-to-br from-slate-700 to-slate-900">
                        <div class="absolute -bottom-8 left-6">
                            <div
                                class="w-16 h-16 rounded-2xl bg-linear-to-br from-indigo-500 to-purple-600 ring-4 ring-white dark:ring-gray-900 flex items-center justify-center shadow-lg">
                                <span class="text-xl font-bold text-white uppercase leading-none">
                                    {{ substr($this->user->name, 0, 1) }}
                                </span>
                            </div>
                        </div>
                        {{-- Role badge top-right --}}
                        @if($this->user->roles->first())
                            <div class="absolute top-3 right-3">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest bg-white/20 text-white backdrop-blur-sm border border-white/20">
                                    <x-lucide-shield class="w-3 h-3" />
                                    {{ $this->user->roles->first()->name }}
                                </span>
                            </div>
                        @endif
                    </div>
    
                    <div class="pt-10 px-6 pb-6">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $this->user->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $this->user->username }}</p>
    
                        {{-- Email --}}
                        <div class="flex items-center gap-2 mt-3">
                            <x-lucide-mail class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                            <span class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $this->user->email }}</span>
                        </div>
    
                        <div class="border-t border-gray-100 dark:border-gray-800 mt-4 pt-4 space-y-2.5">
                            {{-- Created at --}}
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                                    <x-lucide-calendar class="w-3.5 h-3.5" />
                                    {{ __('Member since') }}
                                </span>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $this->user->created_at->format('d M Y') }}
                                </span>
                            </div>
    
                            {{-- Last updated --}}
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                                    <x-lucide-clock class="w-3.5 h-3.5" />
                                    {{ __('Last updated') }}
                                </span>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $this->user->updated_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
    
                {{-- Permissions card --}}
                @php $allPerms = $this->user->getAllPermissions(); @endphp
                @if($allPerms->isNotEmpty())
                    <div
                        class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                {{ __('Permissions') }}
                            </h4>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $allPerms->count() }} {{ __('total') }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($allPerms->take(12) as $perm)
                                <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded-md
                                                     bg-gray-100 dark:bg-gray-800
                                                     text-gray-600 dark:text-gray-400">
                                    {{ $perm->name }}
                                </span>
                            @endforeach
                            @if($allPerms->count() > 12)
                                <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded-md
                                                     bg-indigo-100 dark:bg-indigo-900/30
                                                     text-indigo-600 dark:text-indigo-400">
                                    +{{ $allPerms->count() - 12 }} {{ __('more') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div> {{-- End Sidebar Column --}}

    
            {{-- ========================
            RIGHT: Tabbed Editor
            ======================== --}}
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    
                {{-- Tab bar --}}
                <div class="flex items-center gap-1 px-2 pt-2 border-b border-gray-100 dark:border-gray-800">
                    <button
                        type="button"
                        @click="activeTab = 'profile'"
                        :class="activeTab === 'profile'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm border border-gray-200 dark:border-gray-700'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl mb-2 transition-all duration-200 focus:outline-none"
                    >
                        <x-lucide-user class="w-4 h-4" />
                        {{ __('Profile') }}
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'security'"
                        :class="activeTab === 'security'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm border border-gray-200 dark:border-gray-700'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl mb-2 transition-all duration-200 focus:outline-none"
                    >
                        <x-lucide-lock-keyhole class="w-4 h-4" />
                        {{ __('Security') }}
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'role'"
                        :class="activeTab === 'role'
                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm border border-gray-200 dark:border-gray-700'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl mb-2 transition-all duration-200 focus:outline-none"
                    >
                        <x-lucide-shield-check class="w-4 h-4" />
                        {{ __('Role') }}
                    </button>
                </div>
    
                {{-- ===========================
                TAB: Profile
                =========================== --}}
                <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <form wire:submit="updateBasicInfo" class="p-6 space-y-6">
    
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Profile Information') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Update name, username, and email address') }}</p>
                        </div>
    
                        {{-- Full Name --}}
                        <div class="space-y-1.5">
                            <x-core::label for="name" :value="__('Full Name')" />
                            <x-core::input id="name" type="text" class="block w-full" wire:model="name"
                                placeholder="John Doe" required />
                            <x-core::input-error for="name" />
                        </div>
    
                        {{-- Username + Email in 2-col --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <x-core::label for="username" :value="__('Username')" />
                                <x-core::input id="username" type="text" class="block w-full" wire:model="username"
                                    placeholder="johndoe" required />
                                <x-core::input-error for="username" />
                            </div>
                            <div class="space-y-1.5">
                                <x-core::label for="email" :value="__('Email Address')" />
                                <x-core::input id="email" type="email" class="block w-full" wire:model="email"
                                    placeholder="john@example.com" required />
                                <x-core::input-error for="email" />
                            </div>
                        </div>
    
                        {{-- Footer actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <x-core::secondary-button wire:click="cancel" type="button" label="{{ __('Discard') }}" />
                            <x-core::button type="submit" label="{{ __('Save Profile') }}" spinner="updateBasicInfo">
                                <x-slot name="icon"><x-lucide-save class="w-4 h-4" /></x-slot>
                            </x-core::button>
                        </div>
                    </form>
                </div>
    
                {{-- ===========================
                TAB: Security
                =========================== --}}
                <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <form wire:submit="updateBasicInfo" class="p-6 space-y-6">
    
                        <div
                            class="flex items-start gap-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800">
                            <x-lucide-triangle-alert class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                            <div>
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-400">
                                    {{ __('Password Change') }}</p>
                                <p class="text-xs text-amber-700 dark:text-amber-500 mt-0.5">
                                    {{ __('Leave the fields blank if you do not want to change the password. The user will need to re-login after a password change.') }}
                                </p>
                            </div>
                        </div>
    
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- New Password --}}
                            <div class="space-y-1.5">
                                <x-core::label for="password" :value="__('New Password')" />
                                <x-core::input id="password" type="password" class="block w-full" wire:model="password"
                                    useGenPassword />
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                    {{ __('Min. 8 characters') }}</p>
                                <x-core::input-error for="password" />
                            </div>
    
                            {{-- Confirm Password --}}
                            <div class="space-y-1.5">
                                <x-core::label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-core::input id="password_confirmation" type="password" class="block w-full"
                                    wire:model="password_confirmation" />
                                <x-core::input-error for="password_confirmation" />
                            </div>
                        </div>
    
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <x-core::secondary-button wire:click="cancel" type="button" label="{{ __('Discard') }}" />
                            <x-core::button type="submit" label="{{ __('Update Password') }}" spinner="updateBasicInfo">
                                <x-slot name="icon"><x-lucide-lock-keyhole class="w-4 h-4" /></x-slot>
                            </x-core::button>
                        </div>
                    </form>
                </div>
    
                {{-- ===========================
                TAB: Role
                Single role → radio buttons
                =========================== --}}
                <div x-show="activeTab === 'role'" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <form wire:submit="updateRoles" class="p-6 space-y-5">
    
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Role Assignment') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Each user can only hold one role. Changing the role will immediately update their access permissions.') }}
                            </p>
                        </div>
    
                        {{-- Current assignment info banner --}}
                        @if($this->user->roles->first())
                            <div
                                class="flex items-center gap-3 p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-800">
                                <x-lucide-info class="w-4 h-4 text-indigo-500 shrink-0" />
                                <p class="text-xs text-indigo-700 dark:text-indigo-400">
                                    {{ __('Currently assigned:') }}
                                    <span class="font-bold capitalize">{{ $this->user->roles->first()->name }}</span>
                                    — {{ __('has') }} {{ $this->user->roles->first()->permissions->count() }}
                                    {{ __('permissions') }}
                                </p>
                            </div>
                        @endif
    
                        {{-- Role list --}}
                        <div class="space-y-2">
                            @forelse($this->availableRoles as $role)
                                <label class="flex items-center gap-4 p-4 rounded-xl cursor-pointer border transition-all duration-200
                                                  bg-gray-50 dark:bg-gray-800/50
                                                  border-gray-200 dark:border-gray-700
                                                  hover:bg-white dark:hover:bg-gray-800
                                                  hover:border-indigo-300 dark:hover:border-indigo-700
                                                  has-checked:bg-indigo-50 dark:has-checked:bg-indigo-900/10
                                                  has-checked:border-indigo-400 dark:has-checked:border-indigo-600
                                                  has-checked:shadow-sm">
                                    <input type="radio" name="selectedRole" wire:model="selectedRole" value="{{ $role->name }}"
                                        class="peer sr-only">
    
                                    {{-- Custom radio dot --}}
                                    <span class="shrink-0 flex items-center justify-center w-5 h-5 rounded-full border-2 transition-all duration-200
                                                     border-gray-300 dark:border-gray-600
                                                     peer-checked:border-indigo-500 peer-checked:bg-indigo-500">
                                        <span
                                            class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity"></span>
                                    </span>
    
                                    {{-- Role info --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white capitalize">{{ $role->name }}
                                        </p>
                                        @if($role->permissions->count() > 0)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ $role->permissions->count() }} {{ __('permissions included') }}
                                            </p>
                                        @else
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                {{ __('No permissions defined') }}</p>
                                        @endif
                                    </div>
    
                                    {{-- Permission count pill --}}
                                    @if($role->permissions->count() > 0)
                                        <span class="shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full
                                                             bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700
                                                             text-gray-500 dark:text-gray-400 peer-checked:border-indigo-300">
                                            {{ $role->permissions->count() }}
                                        </span>
                                    @endif
                                </label>
                            @empty
                                <div
                                    class="flex flex-col items-center justify-center py-14 text-center rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                    <x-lucide-shield-off class="w-10 h-10 text-gray-300 dark:text-gray-700 mb-3" />
                                    <p class="text-sm font-semibold text-gray-400 dark:text-gray-500">
                                        {{ __('No roles available') }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">
                                        {{ __('Create roles in the Role Management section first') }}</p>
                                </div>
                            @endforelse
                        </div>
    
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <x-core::secondary-button wire:click="cancel" type="button" label="{{ __('Discard') }}" />
                            <x-core::button type="submit" label="{{ __('Assign Role') }}" spinner="updateRoles">
                                <x-slot name="icon"><x-lucide-shield-check class="w-4 h-4" /></x-slot>
                            </x-core::button>
                        </div>
                    </form>
                </div>
    
            </div>{{-- end editor --}}
        </div>{{-- end grid --}}
    </div>
</div>