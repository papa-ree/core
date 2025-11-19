<?php

namespace Bale\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bale\Core\Core
 */
class Core extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bale\Core\Core::class;
    }
}
