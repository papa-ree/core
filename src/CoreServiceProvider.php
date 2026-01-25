<?php

namespace Bale\Core;

use Bale\Core\Middleware\VerifyCaptcha;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\Finder\Finder;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Method register()
     * 
     * Digunakan untuk mendaftarkan service, binding, atau command
     * ke dalam service container Laravel.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/core.php',
            'core'
        );

        // Register CDN as singleton
        $this->app->singleton('cdn', function ($app) {
            return new \Bale\Core\Support\Cdn();
        });

        $this->registerCommands();
    }

    protected function registerCommands(): void
    {
        $commands = [
            // 'command.core:install' => InstallBaleEmperanCommand::class,
        ];

        foreach ($commands as $key => $class) {
            $this->app->bind($key, $class);
        }

        $this->commands(array_keys($commands));
    }

    /**
     * Method boot()
     * 
     * Dipanggil setelah semua service diregistrasi.
     * Digunakan untuk load resource seperti:
     * - view
     * - migration
     * - konfigurasi
     * - Livewire component
     */
    public function boot(): void
    {
        app('router')->aliasMiddleware('recaptcha', VerifyCaptcha::class);
        app('router')->aliasMiddleware('role', RoleMiddleware::class);
        app('router')->aliasMiddleware('permission', PermissionMiddleware::class);
        app('router')->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
        // app('router')->aliasMiddleware('abilities', CheckAbilities::class);
        // app('router')->aliasMiddleware('ability', CheckForAnyAbility::class);
        // 'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        // 'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        // 'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        // 'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        // 'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        $this->offerPublishing();
        $this->registerViews();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();
        $this->registerViewComposers();
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'core'
        );
    }

    protected function registerBladeComponents(): void
    {
        $componentPath = __DIR__ . '/../resources/views/components';

        if (File::isDirectory($componentPath)) {
            foreach (File::allFiles($componentPath) as $file) {
                if ($file->getExtension() === 'blade') {
                    $componentName = str_replace('.blade.php', '', $file->getFilename());
                    // Register as <x-your-package-alias-component-name />
                    Blade::component('core::' . $componentName, 'core::' . $componentName);
                }
            }
        }
    }

    /**
     * Publish file agar bisa diubah oleh user.
     */

    protected function offerPublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/core.php' => config_path('core.php'),
        ], 'bale-core:config');

        $this->publishes($this->getMigrations(), 'bale-core:migrations');

        $this->publishes([
            __DIR__ . '/../resources/js/' => resource_path('js/'),
        ], 'bale-core:assets');
    }

    /**
     * Mengambil semua file migration dari direktori package.
     */
    protected function getMigrations(): array
    {
        $migrations = [];
        $sourcePath = __DIR__ . '/../database/migrations/';

        // Pastikan direktori ada
        if (!is_dir($sourcePath)) {
            return $migrations;
        }

        // Loop semua file migration (baik .php maupun .stub)
        foreach (glob($sourcePath . '*.{php,stub}', GLOB_BRACE) as $file) {
            $filename = basename($file);

            // Jika file stub, ganti menjadi nama migration yang benar di aplikasi
            $targetFile = $this->getMigrationFileName($filename);

            $migrations[$file] = $targetFile;
        }

        return $migrations;
    }

    /**
     * Membuat nama file migration yang sesuai dengan timestamp laravel.
     */
    protected function getMigrationFileName(string $filename): string
    {
        $timestamp = date('Y_m_d_His');
        $migrationName = str_replace('.stub', '.php', $filename);

        return database_path('migrations/' . $timestamp . '_' . $migrationName);
    }

    protected function registerLivewireComponents(): void
    {
        $namespace = "Bale\\Core\\Livewire";
        $basePath = __DIR__ . "/Livewire";

        // Jika folder Livewire tidak ada, hentikan proses
        if (!is_dir($basePath)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($basePath)->name('*.php');

        foreach ($finder as $file) {
            $relativePathname = $file->getRelativePathname();

            // Normalisasi path (Windows/Linux)
            $nsPath = str_replace(['/', '\\'], '\\', $relativePathname);

            // Konversi ke FQCN (Fully Qualified Class Name)
            $class = $namespace . '\\' . Str::beforeLast($nsPath, '.php');

            // Skip jika class tidak ditemukan
            if (!class_exists($class)) {
                continue;
            }

            // Skip jika bukan turunan Livewire\Component
            if (!is_subclass_of($class, LivewireComponent::class)) {
                continue;
            }

            // Buat alias berdasarkan struktur folder (kebab-case)
            $withoutExt = Str::replaceLast('.php', '', $relativePathname);
            $segments = preg_split('#[\\/\\\\]#', $withoutExt);
            $kebab = array_map(fn($s) => Str::kebab($s), $segments);

            $alias = 'core.' . implode('.', $kebab);

            // Registrasi komponen ke Livewire
            Livewire::component($alias, $class);
        }
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        \Illuminate\Support\Facades\View::composer('*', \Bale\Core\View\Composers\CdnViewComposer::class);
    }
}
