<?php

namespace Bale\Core\Traits;

use Bale\Cms\Services\TenantManager;
use Illuminate\Support\Facades\Request;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

trait LogsRoleChanges
{
    use HasRoles;

    public function assignRole(...$roles): static
    {
        $roles = $this->collectRoles($roles);

        $model = $this->getModel();

        if ($model->exists) {
            $currentRoles = $this->roles->map(fn ($role) => $role->getKey())->toArray();
            $this->roles()->attach(array_diff($roles, $currentRoles));
            $model->unsetRelation('roles');
        }

        $this->forgetWildcardPermissionIndex();

        $this->logRoleChange('role assigned', $roles);

        return $this;
    }

    public function removeRole(...$roles): static
    {
        $roles = $this->collectRoles($roles);

        $this->roles()->detach($roles);
        $this->unsetRelation('roles');
        $this->forgetWildcardPermissionIndex();

        $this->logRoleChange('role removed', $roles);

        return $this;
    }

    public function givePermissionTo(...$permissions): static
    {
        $permissions = $this->collectPermissions($permissions);

        $model = $this->getModel();

        if ($model->exists) {
            $currentPermissions = $this->permissions->map(fn ($p) => $p->getKey())->toArray();
            $this->permissions()->attach(array_diff($permissions, $currentPermissions));
            $model->unsetRelation('permissions');
        }

        $this->forgetWildcardPermissionIndex();

        $this->logPermissionChange('permission granted', $permissions);

        return $this;
    }

    public function revokePermissionTo($permissions): static
    {
        $storedPermission = $this->getStoredPermission($permissions);

        $this->permissions()->detach($storedPermission);
        $this->forgetWildcardPermissionIndex();

        $this->logPermissionChange('permission revoked', $permissions);

        return $this;
    }

    protected function logRoleChange(string $event, $roles): void
    {
        $roleNames = collect($roles)->map(
            fn ($r) => $r instanceof Role ? $r->name : (string) $r
        )->all();

        $tenantName = session('bale_active_slug') ?? 'landlord';
        $model = class_basename(static::class);

        activity('core')
            ->performedOn($this->getModel())
            ->event(ucfirst($event))
            ->withProperties([
                'roles' => $roleNames,
                'tenant_id' => TenantManager::getActiveConnection() ?? null,
                'tenant_name' => $tenantName,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ])
            ->log($tenantName === 'landlord'
                ? "{$model} {$event}"
                : "{$tenantName} {$model} was " . ucfirst($event)
            );
    }

    protected function logPermissionChange(string $event, $permissions): void
    {
        $permissionNames = collect($permissions)->map(
            fn ($p) => is_string($p) ? $p : $p->name ?? $p
        )->all();

        $tenantName = session('bale_active_slug') ?? 'landlord';
        $model = class_basename(static::class);

        activity('core')
            ->performedOn($this->getModel())
            ->event(ucfirst($event))
            ->withProperties([
                'permissions' => $permissionNames,
                'tenant_id' => TenantManager::getActiveConnection() ?? null,
                'tenant_name' => $tenantName,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ])
            ->log($tenantName === 'landlord'
                ? "{$model} {$event}"
                : "{$tenantName} {$model} was " . ucfirst($event)
            );
    }
}
