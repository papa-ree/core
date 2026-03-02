<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    public $timestamps = false;

    protected $table = 'security_events';

    protected $fillable = [
        'event_type',
        'severity',
        'ip_address',
        'username',
        'tenant_id',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
