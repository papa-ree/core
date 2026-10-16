<?php

namespace Bale\Core\Livewire\Pages\WpTools\SqlMigrator;

use Bale\Core\Jobs\Wp\ImportWpPostJob;
use Bale\Core\Services\WpMigrationRunService;
use Bale\Core\Services\WpSqlParser;
use Bale\Core\Support\WpTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Paparee\Rakaca\Models\BaleList;

#[Layout('core::layouts.app')]
#[Title('WP SQL Migrator')]
class Index extends Component
{
    use WithFileUploads;

    /**
     * Temporary file upload disk.
     *
     * - Local environment  → 'local'  (server filesystem, simplest)
     * - Production (APP_ENV=production) → 's3'
     *
     * Pair this with LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=s3 in production .env
     * so Livewire's chunk-upload endpoints also target the same disk.
     */
    public function temporaryFileUploadDisk(): string
    {
        return app()->isProduction() ? 's3' : 'local';
    }

    // -----------------------------------------------------------------------
    // Public State
    // -----------------------------------------------------------------------

    /** @var TemporaryUploadedFile|null */
    #[Validate(['sqlFile' => 'required|file|mimes:txt|max:102400'])]
    public $sqlFile = null;

    /** Selected BaleList UUID */
    public string $selectedBaleId = '';

    /** Selected Author ID from Tenant DB */
    public string $selectedAuthorId = '';

    /** Users fetched from the selected Tenant DB */
    public array $tenantUsers = [];

    /**
     * Optional table prefix override.
     * If empty, the parser will auto-detect any `INSERT INTO <anything>_posts` statement.
     */
    public string $tablePrefix = '';

    // -----------------------------------------------------------------------
    // Result / UI State
    // -----------------------------------------------------------------------

    public bool $isProcessing = false;

    /** Id of the dispatched migration run (null until an import is started). */
    public ?string $runId = null;

    /** @var string|null A single critical error that aborts the run. */
    public ?string $fatalError = null;

    // -----------------------------------------------------------------------
    // Computed Helpers
    // -----------------------------------------------------------------------

    /** All active BaleList entries available for selection. */
    public function getBaleListsProperty(): array
    {
        if (! class_exists(BaleList::class)) {
            return [];
        }

        return BaleList::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($bale) => [
                'id' => $bale->id,
                'display_name' => $bale->name.($bale->database_name ? " ({$bale->database_name}@{$bale->database_host})" : ''),
            ])
            ->toArray();
    }

    // -----------------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------------

    public function mount(): void
    {
        $this->checkAccess();
    }

    /**
     * Triggered when selectedBaleId changes.
     * Fetches users from the target tenant database for the Author dropdown.
     */
    public function updatedSelectedBaleId($value): void
    {
        $this->selectedAuthorId = '';
        $this->tenantUsers = [];

        if (empty($value)) {
            return;
        }

        try {
            $connection = app(WpTenant::class)->connection($value);

            $this->tenantUsers = DB::connection($connection)
                ->table('users')
                ->select('uuid', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->uuid,
                    'name' => $user->name.($user->email ? " ({$user->email})" : ''),
                ])
                ->toArray();

        } catch (\Throwable $e) {
            $this->dispatch('toast', message: __('Failed to connect to tenant database to fetch users: ').$e->getMessage(), type: 'error');
        }
    }

    // -----------------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------------

    /**
     * Parse the uploaded dump and dispatch one queued job per post.
     *
     * The heavy per-post work (HTML → EditorJS, slug handling, insert) happens
     * in {@see ImportWpPostJob}; this request only parses the
     * file and enqueues the batch so it can never time out.
     */
    public function import(): void
    {
        $this->checkAccess();
        $this->validate();

        $this->reset(['fatalError', 'runId']);
        $this->isProcessing = true;

        try {
            if (empty($this->selectedBaleId)) {
                $this->fatalError = __('Please select a destination Bale.');

                return;
            }

            $bale = BaleList::find($this->selectedBaleId);

            if (! $bale) {
                $this->fatalError = __('Selected Bale not found.');

                return;
            }

            if (empty($this->selectedAuthorId)) {
                $this->fatalError = __('Please select an Author for the posts.');

                return;
            }

            $tenant = app(WpTenant::class);
            $connection = $tenant->connection($bale->id);

            // Verify connection before proceeding.
            DB::connection($connection)->getPdo();

            $sqlContent = $this->readSqlFile();

            if ($sqlContent === null) {
                return;
            }

            $parser = new WpSqlParser;
            $posts = $parser->parse($sqlContent, $this->tablePrefix);

            if (empty($posts)) {
                $this->fatalError = __('No publishable WordPress posts found in the SQL dump. Check the file and table prefix.');

                return;
            }

            $run = app(WpMigrationRunService::class)->startSqlRun(
                baleId: $bale->id,
                authorId: $this->selectedAuthorId,
                posts: $posts,
                meta: [
                    'slug' => (string) $bale->slug,
                    'table_prefix' => $this->tablePrefix,
                    'file' => method_exists($this->sqlFile, 'getClientOriginalName')
                        ? $this->sqlFile->getClientOriginalName()
                        : null,
                    'source' => 'ui',
                ],
            );

            $this->runId = $run->id;

            $this->dispatch(
                'toast',
                message: __(':count posts queued for import. Processing in the background…', ['count' => count($posts)]),
                type: 'success'
            );
        } catch (\Throwable $e) {
            $this->fatalError = $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Reset the form to its initial state.
     */
    public function resetForm(): void
    {
        $this->reset();
    }

    // -----------------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------------

    /**
     * Read the uploaded SQL file content.
     *
     * For local env: reads directly via getRealPath() (temp disk = 'local').
     * For production: reads from S3 via Storage::disk('s3').
     */
    protected function readSqlFile(): ?string
    {
        try {
            $size = $this->sqlFile->getSize();

            if ($size > 32 * 1024 * 1024) {
                ini_set('memory_limit', '512M');
            }

            $content = $this->sqlFile->get();

            if ($content === false || $content === null) {
                $this->fatalError = __('Failed to read the uploaded SQL file. Check storage permissions.');

                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            $this->fatalError = __('Failed to read the uploaded SQL file: ').$e->getMessage();

            return null;
        }
    }

    // -----------------------------------------------------------------------
    // Permission Guard
    // -----------------------------------------------------------------------

    public function checkAccess(): void
    {
        if (! auth()->check()) {
            abort(403);
        }
    }

    // -----------------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------------

    public function render(): View
    {
        return view('core::livewire.pages.wp-tools.sql-migrator.index', [
            'baleLists' => $this->getBaleListsProperty(),
        ]);
    }
}
