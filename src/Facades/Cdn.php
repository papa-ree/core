<?php

namespace Bale\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CDN Facade
 * 
 * @method static string asset(string $path, ?string $customDir = null)
 * @method static string url(string $path, ?string $customDir = null)
 * @method static bool enabled()
 * @method static string|null baseUrl()
 * @method static string prefix()
 * 
 * @see \Bale\Core\Support\Cdn
 */
class Cdn extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cdn';
    }
}
