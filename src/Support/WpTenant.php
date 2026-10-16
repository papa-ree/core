<?php

namespace Bale\Core\Support;

use Paparee\Rakaca\Models\BaleList;

/**
 * WpTenant
 *
 * Resolves the runtime database connection of a Bale tenant from its
 * `bale_lists` row, and points {@see Cdn::url()} at that tenant.
 *
 * WordPress migration work spans many processes (Livewire request, Artisan
 * command, queue workers), so this is the single place that builds the tenant
 * connection instead of duplicating it in each entry point.
 */
class WpTenant
{
    /** @var array<string, string> Explicit connection overrides (tests / ops). */
    protected array $overrides = [];

    /**
     * Force a specific connection name for a tenant (used by tests and for
     * operational overrides).
     */
    public function override(string $baleId, string $connection): static
    {
        $this->overrides[$baleId] = $connection;

        return $this;
    }

    public function bale(string $baleId): BaleList
    {
        return BaleList::query()->findOrFail($baleId);
    }

    /**
     * Runtime connection name for the given tenant (registers it on demand).
     */
    public function connection(string $baleId): string
    {
        if (isset($this->overrides[$baleId])) {
            return $this->overrides[$baleId];
        }

        return $this->register($this->bale($baleId));
    }

    /**
     * Organization slug used for storage directories and CDN URLs.
     */
    public function slug(string $baleId): string
    {
        if (isset($this->overrides[$baleId])) {
            // Still prefer the real slug when the bale row is available.
            try {
                return (string) $this->bale($baleId)->slug;
            } catch (\Throwable) {
                return '';
            }
        }

        return (string) $this->bale($baleId)->slug;
    }

    /**
     * Point Cdn::url() at the given tenant for the remainder of this process.
     */
    public function cdn(string $slug): void
    {
        Cdn::forgetOrganizationSlug();
        Cdn::resolveOrganizationSlugUsing(fn () => $slug);
    }

    /**
     * Register a Laravel database connection for the tenant.
     */
    protected function register(BaleList $bale): string
    {
        $name = 'wp_tenant_'.str_replace('-', '_', (string) $bale->id);

        config([
            "database.connections.{$name}" => [
                'driver' => 'mysql',
                'host' => $bale->database_host,
                'database' => $bale->database_name,
                'username' => $bale->database_username,
                'password' => $bale->database_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ],
        ]);

        return $name;
    }
}
