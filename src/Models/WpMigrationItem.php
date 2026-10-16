<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WpMigrationItem
 *
 * One row per post handled by a {@see WpMigrationRun} — the unit of work for a
 * single queued job. `payload` keeps the raw WordPress row (SQL stage) so a
 * failed job can be retried; `result` keeps the per-post outcome (counts,
 * error message, security findings).
 */
class WpMigrationItem extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_MIGRATED = 'migrated';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_FAILED = 'failed';

    protected $table = 'wp_migration_items';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];

    /** @return BelongsTo<WpMigrationRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(WpMigrationRun::class, 'run_id');
    }
}
