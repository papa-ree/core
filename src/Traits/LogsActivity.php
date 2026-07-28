<?php

namespace Bale\Core\Traits;

use Bale\Cms\Services\TenantManager;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        $tenantName = session('bale_active_slug') ?? 'landlord';

        $activity->properties = collect($activity->properties ?? [])->merge([
            'tenant_id' => $this->resolveTenantId(),
            'tenant_name' => $tenantName,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        $model = class_basename(static::class);
        $event = ucfirst($eventName);

        $activity->description = $tenantName === 'landlord'
            ? "{$model} was {$event}"
            : "{$tenantName} {$model} was {$event}";
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(config('activitylog.activity_model'), 'subject');
    }

    protected function resolveTenantId(): ?string
    {
        if (class_exists(TenantManager::class)) {
            return TenantManager::getActiveConnection();
        }

        return null;
    }
}
