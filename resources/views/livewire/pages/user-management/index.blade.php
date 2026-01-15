<div>
    <x-core::page-header title="User Management" subtitle="Kelola pengguna sistem">
        <x-slot name="actions">
            @can('user management')
                <a href="{{ route('user-management.create') }}" wire:navigate
                    class="inline-flex items-center gap-x-2 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                        </path>
                    </svg>
                    Add New User
                </a>
            @endcan
        </x-slot>
    </x-core::page-header>

    <x-core::table :links="$this->users" header>
        <x-slot name="thead">
            <tr>
                <x-core::table-th label="User" sortBy="name" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                <x-core::table-th class="hidden md:table-cell" label="Username" sortBy="username"
                    :sortField="$sortField" :sortDirection="$sortDirection" />
                <x-core::table-th class="hidden lg:table-cell" label="Email" sortBy="email"
                    :sortField="$sortField" :sortDirection="$sortDirection" />
                <x-core::table-th class="hidden sm:table-cell" label="Roles" />
                @can('user management')
                    <x-core::table-th label="Action" />
                @endcan
            </tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($this->users as $user)
                <tr wire:key='user-{{ $user->id }}'
                    class="transition-colors duration-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    {{-- User --}}
                    <td class="w-full py-4 pl-4 pr-3 text-sm font-medium text-gray-900 max-w-0 sm:w-auto sm:max-w-none">
                        <div class="block py-3 pe-6">
                            <div class="flex items-center gap-x-3">
                                <div
                                    class="flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-semibold text-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="grow">
                                    <span
                                        class="block text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $user->name }}</span>
                                    <dl class="font-normal md:hidden">
                                        <dt class="sr-only">Username</dt>
                                        <dd class="text-xs text-gray-500 dark:text-gray-400">{{ $user->username }}</dd>
                                        <dt class="sr-only lg:hidden">Email</dt>
                                        <dd class="text-xs text-gray-500 dark:text-gray-400 lg:hidden">
                                            {{ $user->email }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Username --}}
                    <td class="hidden px-3 py-4 text-sm text-gray-500 md:table-cell">
                        <span class="block text-sm text-gray-800 dark:text-gray-200">{{ $user->username }}</span>
                    </td>

                    {{-- Email --}}
                    <td class="hidden px-3 py-4 text-sm text-gray-500 lg:table-cell">
                        <span class="block text-sm text-gray-800 dark:text-gray-200">{{ $user->email }}</span>
                    </td>

                    {{-- Roles --}}
                    <td class="hidden px-3 py-4 text-sm text-gray-500 sm:table-cell">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->getRoleNames() as $role)
                                <span
                                    class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $role }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400 dark:text-gray-600">No roles</span>
                            @endforelse
                        </div>
                    </td>

                    {{-- Actions --}}
                    @can('user management')
                        <td class="size-px whitespace-nowrap">
                            <div class="px-6 py-1.5">
                                <livewire:core.shared-components.item-actions
                                    :editUrl="route('user-management.edit', $user->id)" :deleteId="$user->id"
                                    wire:key="item-actions-{{ $user->id }}"
                                    confirmMessage="Are you sure you want to delete this user?" />
                            </div>
                        </td>
                    @endcan
                </tr>
            @endforeach
        </x-slot>
    </x-core::table>
</div>