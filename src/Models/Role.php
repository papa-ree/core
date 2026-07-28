<?php

namespace Bale\Core\Models;

use Bale\Core\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsActivity;
}
