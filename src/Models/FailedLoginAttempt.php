<?php

namespace Bale\Core\Models;

use Illuminate\Database\Eloquent\Model;

class FailedLoginAttempt extends Model
{
    public $timestamps = false;

    protected $table = 'failed_login_attempts';

    protected $fillable = [
        'tenant_id',
        'username',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];
}
