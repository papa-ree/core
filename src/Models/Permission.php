<?php

namespace Bale\Core\Models;

use Bale\Core\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use LogsActivity;
}
