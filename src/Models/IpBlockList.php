<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Model;

class IpBlockList extends Model
{
    protected $table = 'ip_block_list';

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
        'block_count_24h',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
    ];
}
