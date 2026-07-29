<?php

namespace Bale\Core\Traits;

use Bale\Cms\Services\TenantManager;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity as SpatieLogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivity
{
    use SpatieLogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $inConsole = app()->runningInConsole();

        $tenantName = $inConsole ? 'system' : (session('bale_active_slug') ?? 'landlord');
        $causer = $activity->causer;

        $activity->properties = collect($activity->properties ?? [])->merge([
            'tenant_id' => $this->resolveTenantId(),
            'tenant_name' => $tenantName,
            'logged_by' => $causer ? 'user' : 'system',
            'ip_address' => $inConsole ? null : Request::ip(),
            'user_agent' => $inConsole ? null : Request::userAgent(),
        ]);

        $loggedBy = $causer ? 'by '.get_class($causer).'#'.$causer->getKey() : 'by system';

        $model = class_basename(static::class);
        $event = ucfirst($eventName);

        $activity->description = $tenantName === 'landlord'
            ? "{$model} was {$event} ({$loggedBy})"
            : "{$tenantName} {$model} was {$event} ({$loggedBy})";
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(config('activitylog.activity_model'), 'subject');
    }

    protected function resolveTenantId(): ?string
    {
        if (! class_exists(TenantManager::class)) {
            return null;
        }

        try {
            return TenantManager::getActiveConnection();
        } catch (\Throwable) {
            return null;
        }
    }
}
