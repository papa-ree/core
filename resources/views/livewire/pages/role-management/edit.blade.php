<div>
    <x-core::breadcrumb :items="[['label' => __('Roles'), 'route' => 'role.index']]" :active="__('Edit Role')" />

    <form wire:submit="save" class="mt-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-7">
            <!-- Metadata (Left) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info Card -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800 bg-linear-to-r from-indigo-50/50 to-purple-50/50 dark:from-indigo-900/10 dark:to-purple-900/10">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Basic Information') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ __('Modify role details') }}
                        </p>
                    </div>
                    <div class="p-6 space-y-6">
                        <div>
                            <x-core::input label="{{ __('Role Name') }}" wire:model="name" name="name" required />
                            <x-core::input-error for="name" />
                        </div>

                        <div>
                            <x-core::input label="{{ __('Guard Name') }}" wire:model="guard_name" name="guard_name" required />
                            <x-core::input-error for="guard_name" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editor (Right) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden flex flex-col" x-data="permissionManager()">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800 bg-linear-to-r from-emerald-50/50 to-teal-50/50 dark:from-emerald-900/10 dark:to-teal-900/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Permissions') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Assign system access permissions') }}
                            </p>
                        </div>
                        <label class="flex items-center gap-3 px-4 py-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <input type="checkbox" x-model="selectAll" x-on:change="toggleAll" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-600 dark:bg-slate-900 dark:border-slate-700">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Select All') }}</span>
                        </label>
                    </div>

                    @php
                        $groupedPermissions = collect($this->availablePermissions)->groupBy(function($item) {
                            $parts = explode('.', $item['name']);
                            return count($parts) > 1 ? $parts[0] : 'general';
                        });
                    @endphp

                    <div class="p-6 flex-1 bg-gray-50 dark:bg-slate-800/30">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($groupedPermissions as $group => $permissions)
                                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm flex flex-col">
                                    <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 bg-gray-50/80 dark:bg-slate-700/50 flex justify-between items-center">
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 capitalize">{{ $group }}</h4>
                                        <label class="cursor-pointer">
                                            <input type="checkbox" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-600 dark:bg-slate-900 dark:border-slate-700"
                                                x-model="groupStatus['{{ $group }}']"
                                                x-on:change="toggleGroup('{{ $group }}')">
                                        </label>
                                    </div>
                                    <div class="p-3 space-y-1 flex-1">
                                        @foreach ($permissions as $permission)
                                            <label class="flex items-start gap-3 p-2 rounded-lg cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 border border-transparent hover:border-emerald-100 dark:hover:border-emerald-900/30 transition-colors">
                                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}"
                                                    x-model="selected"
                                                    x-on:change="updateStatus"
                                                    class="mt-0.5 w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-600 dark:bg-slate-900 dark:border-slate-700">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $permission->name }}</span>
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-500 uppercase tracking-wider">{{ $permission->guard_name }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="p-6 border-t border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                        <x-core::secondary-button type="button" link href="{{ route('role.index') }}" label="Cancel" />
                        
                        <x-core::button type="submit" label="Update Role" spinner="save">
                            <x-slot name="icon"><x-lucide-check class="w-4 h-4" /></x-slot>
                        </x-core::button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    @script
    <script>
        Alpine.data('permissionManager', () => ({
            selected: $wire.entangle('selectedPermissions'),
            selectAll: false,
            groupStatus: {},
            allPermissions: @js($this->availablePermissions->pluck('name')),
            
            @php
                $mappedGroupPermissions = collect($groupedPermissions)->map(function($perms) {
                    return $perms->pluck('name');
                })->toArray();
            @endphp
            permissionsByGroup: @js($mappedGroupPermissions),
            
            init() {
                // Initialize groupStatus structure
                for (const group in this.permissionsByGroup) {
                    this.groupStatus[group] = false;
                }
                
                this.$watch('selected', (value) => {
                    this.updateStatus();
                });
                
                this.updateStatus(); // check initial checked status
            },
            
            toggleAll() {
                if (this.selectAll) {
                    this.selected = [...this.allPermissions];
                } else {
                    this.selected = [];
                }
            },
            
            toggleGroup(group) {
                const groupPerms = this.permissionsByGroup[group];
                
                if (this.groupStatus[group]) {
                    // Create a set of currently selected to ensure uniqueness, then add group's perms
                    let currentSet = new Set(this.selected);
                    groupPerms.forEach(p => currentSet.add(p));
                    this.selected = Array.from(currentSet);
                } else {
                    // Filter out the group's perms from selected
                    this.selected = this.selected.filter(p => !groupPerms.includes(p));
                }
            },
            
            updateStatus() {
                if(this.allPermissions.length === 0) return;
                
                // Update main select all
                this.selectAll = this.allPermissions.every(p => this.selected.includes(p));
                
                // Update per-group
                for (const group in this.permissionsByGroup) {
                    const groupPerms = this.permissionsByGroup[group];
                    this.groupStatus[group] = groupPerms.length > 0 && groupPerms.every(p => this.selected.includes(p));
                }
            }
        }))
    </script>
    @endscript
</div>