<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity implements ActivityContract
{
    protected $table = 'activity_log';

    protected function casts(): array
    {
        return [
            'attribute_changes' => 'collection',
            'properties' => 'collection',
        ];
    }

    public function subject(): MorphTo
    {
        if (config('activitylog.include_soft_deleted_subjects')) {
            return $this->morphTo()->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeInLog(Builder $query, string|array ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeForEvent(Builder $query, string|ActivityEvent $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->whereJsonContains('properties->tenant_id', $tenantId);
    }

    public function scopeForDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        return $query
            ->when($startDate, fn (Builder $q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->where('created_at', '<=', $endDate));
    }

    public function getTenantIdAttribute(): ?string
    {
        return $this->properties?->get('tenant_id');
    }

    public function getTenantNameAttribute(): ?string
    {
        return $this->properties?->get('tenant_name');
    }

    public function getIpAddressAttribute(): ?string
    {
        return $this->properties?->get('ip_address');
    }
}
