<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WpMigrationRun
 *
 * One orchestration record per dispatched queue batch:
 *   - type = "sql"   → importing WordPress posts into a tenant database
 *   - type = "media" → migrating the media referenced by those posts
 *
 * Lives on the landlord (default) connection, next to Laravel's `jobs` /
 * `job_batches` tables, so it is shared across tenants.
 */
class WpMigrationRun extends Model
{
    use HasUuids;

    protected $table = 'wp_migration_runs';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'total' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** @return HasMany<WpMigrationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(WpMigrationItem::class, 'run_id');
    }
}
