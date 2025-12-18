<?php

namespace Bale\Core\Supports;

class PackageChecker
{
    protected static ?array $packages = null;

    public static function isInstalled(string $package): bool
    {
        if (self::$packages === null) {
            $installedPath = base_path('vendor/composer/installed.json');

            if (!file_exists($installedPath)) {
                self::$packages = [];
            } else {
                $data = json_decode(
                    file_get_contents($installedPath),
                    true
                );

                self::$packages = collect($data['packages'] ?? $data)
                    ->pluck('name')
                    ->all();
            }
        }

        return in_array($package, self::$packages, true);
    }
}
