<?php

use Bale\Core\Supports\PackageChecker;

if (!function_exists('package_installed')) {
    function package_installed(string $package): bool
    {
        return PackageChecker::isInstalled($package);
    }
}
