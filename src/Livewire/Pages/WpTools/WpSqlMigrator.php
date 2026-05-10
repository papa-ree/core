<?php

namespace Bale\Core\Livewire\Pages\WpTools;

use Bale\Core\Services\PostCleanerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Paparee\Rakaca\Models\BaleList;
use Ramsey\Uuid\Uuid;

#[Layout('core::layouts.app')]
#[Title('WP SQL Migrator')]
class WpSqlMigrator extends Component
{
    use WithFileUploads;

    // -----------------------------------------------------------------------
    // Public State
    // -----------------------------------------------------------------------

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    #[Validate(['sqlFile' => 'nullable|file|mimes:sql,txt|max:102400'])]
    public $sqlFile = null;

    /** The filename of the selected dump from S3 */
    public string $selectedFile = '';

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
    public bool $isDone = false;
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public int $filteredCount = 0;
    public int $totalFoundCount = 0;

    /** @var array<string> Human-readable error messages per row. */
    public array $importErrors = [];

    /** @var string|null  A single critical error that aborts the run. */
    public ?string $fatalError = null;

    // -----------------------------------------------------------------------
    // Computed Helpers
    // -----------------------------------------------------------------------

    /** All active BaleList entries available for selection. */
    public function getBaleListsProperty(): array
    {
        if (!class_exists(BaleList::class)) {
            return [];
        }

        return BaleList::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($bale) => [
                'id' => $bale->id,
                'display_name' => $bale->name . ($bale->database_name ? " ({$bale->database_name}@{$bale->database_host})" : ""),
            ])
            ->toArray();
    }

    // -----------------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------------

    public function mount(): void
    {
        $this->checkAccess(); // inline guard
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
            $bale = BaleList::find($value);
            if (!$bale) {
                return;
            }

            $connectionName = $this->setupTenantConnection($bale);

            // Fetch users from the tenant database
            $this->tenantUsers = DB::connection($connectionName)
                ->table('users')
                ->select('uuid', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->uuid,
                    'name' => $user->name . ($user->email ? " ({$user->email})" : ""),
                ])
                ->toArray();

        } catch (\Throwable $e) {
            $this->dispatch('toast', message: __('Failed to connect to tenant database to fetch users: ') . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Triggered automatically when a file is selected.
     * Directly uploads it to the permanent 'private/wp-sql-dumps' location on S3.
     */
    public function updatedSqlFile(): void
    {
        $this->validateOnly('sqlFile');

        try {
            $originalName = $this->sqlFile->getClientOriginalName();
            $extension = $this->sqlFile->getClientOriginalExtension();
            
            // Cleanup name for storage: slugify but keep extension
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeName = Str::slug($baseName) . '-' . uniqid() . '.' . $extension;
            
            $finalPath = 'private/wp-sql-dumps/' . $safeName;

            // Upload directly to S3 disk as requested
            Storage::disk('s3')->put($finalPath, $this->sqlFile->get());

            $this->sqlFile = null; // Clear temp upload state
            $this->selectedFile = $safeName; // Auto-select the newly uploaded file
            
            $this->dispatch('toast', message: __('File uploaded successfully to S3.'), type: 'success');
        } catch (\Throwable $e) {
            $this->fatalError = __('Upload failed: ') . $e->getMessage();
        }
    }

    /**
     * Delete a file from S3 storage.
     */
    public function deleteFile(string $filename): void
    {
        try {
            Storage::disk('s3')->delete('private/wp-sql-dumps/' . $filename);
            
            if ($this->selectedFile === $filename) {
                $this->selectedFile = '';
            }

            $this->dispatch('toast', message: __('File deleted.'), type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: __('Failed to delete file: ') . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Helper to list all dumps currently stored in S3.
     */
    public function getStoredFiles(): array
    {
        try {
            $files = Storage::disk('s3')->files('private/wp-sql-dumps');
            $data = [];

            foreach ($files as $file) {
                $data[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => $this->formatBytes(Storage::disk('s3')->size($file)),
                    'date' => date('Y-m-d H:i:s', Storage::disk('s3')->lastModified($file)),
                ];
            }

            // Sort by date descending
            usort($data, fn($a, $b) => strcmp($b['date'], $a['date']));

            return $data;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // -----------------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------------

    /**
     * Main import action triggered by the user clicking "Import Now".
     */
    public function import(): void
    {
        $this->checkAccess();
        
        if (empty($this->selectedFile)) {
            $this->dispatch('toast', message: __('Please select a file from the list first.'), type: 'error');
            return;
        }

        $this->reset(['isDone', 'importedCount', 'skippedCount', 'filteredCount', 'totalFoundCount', 'importErrors', 'fatalError']);
        $this->isProcessing = true;

        try {
            // --- Resolve target BaleList & build dynamic DB connection ---
            if (empty($this->selectedBaleId)) {
                $this->fatalError = __('Please select a destination Bale.');
                $this->isProcessing = false;
                return;
            }

            $bale = BaleList::find($this->selectedBaleId);

            if (!$bale) {
                $this->fatalError = __('Selected Bale not found.');
                $this->isProcessing = false;
                return;
            }

            if (empty($this->selectedAuthorId)) {
                $this->fatalError = __('Please select an Author for the posts.');
                $this->isProcessing = false;
                return;
            }

            $connectionName = $this->setupTenantConnection($bale);

            // Verify connection before proceeding.
            DB::connection($connectionName)->getPdo();

            // --- Read & parse the SQL file ---
            $sqlContent = $this->readSqlFile();

            if ($sqlContent === null) {
                $this->isProcessing = false;
                return;
            }

            $posts = $this->parsePosts($sqlContent);

            if (empty($posts)) {
                $this->fatalError = __('No publishable WordPress posts found in the SQL dump. Check the file and table prefix.');
                $this->isProcessing = false;
                return;
            }

            // --- Clean & insert each post ---
            $cleaner = new PostCleanerService();

            foreach ($posts as $post) {
                try {
                    $slug = Str::slug($post['post_title']);
                    $title = $post['post_title'];
                    $content = json_encode($cleaner->clean($post['post_content']), JSON_UNESCAPED_UNICODE);
                    $publishedAt = $post['post_modified'] ?? now();

                    // Check for duplicate slug on the target connection.
                    $exists = DB::connection($connectionName)
                        ->table('posts')
                        ->where('slug', $slug)
                        ->exists();

                    if ($exists) {
                        // Append a short unique suffix to avoid collision.
                        $slug = $slug . '-' . Str::lower(Str::random(5));
                    }

                    DB::connection($connectionName)->table('posts')->insert([
                        'id' => Uuid::uuid4(),
                        'title' => $title,
                        'slug' => $slug,
                        'content' => $content,
                        'author' => $this->selectedAuthorId,
                        'published' => true,
                        'published_at' => $publishedAt,
                        'created_at' => $post['post_date'] ?? now(),
                        'updated_at' => $publishedAt,
                    ]);

                    $this->importedCount++;

                } catch (\Throwable $e) {
                    $this->skippedCount++;
                    $this->importErrors[] = "Row skipped — " . Str::limit($post['post_title'] ?? '(unknown)', 60) . ': ' . $e->getMessage();
                }
            }

            $this->isDone = true;
            $this->dispatch('toast', message: __(':count posts imported successfully!', ['count' => $this->importedCount]), type: 'success');

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
     * Read the SQL file content from S3.
     */
    protected function readSqlFile(): ?string
    {
        $path = 'private/wp-sql-dumps/' . $this->selectedFile;

        if (!Storage::disk('s3')->exists($path)) {
            $this->fatalError = __('The selected SQL file no longer exists in storage.');
            return null;
        }

        $size = Storage::disk('s3')->size($path);

        if ($size > 32 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        $content = Storage::disk('s3')->get($path);

        if ($content === false) {
            $this->fatalError = __('Failed to read the uploaded SQL file. Check storage permissions.');
            return null;
        }

        return $content;
    }

    /**
     * Parse all valid wp_posts INSERT statements from the raw SQL dump.
     *
     * Handles:
     *  - Configurable / auto-detected table prefix.
     *  - Multi-row INSERT VALUES: ('row1_data',...),('row2_data',...).
     *  - WordPress-style SQL escaping (\' within values).
     *
     * @return array<array<string,string>>  Each element is an associative array of column → value.
     */
    protected function parsePosts(string $sql): array
    {
        $posts = [];

        /*
         * Build a regex that matches INSERT INTO <prefix>posts statements.
         * The prefix can be anything ending in `_posts` so we support wp_posts,
         * mysite_posts, etc.  If $tablePrefix is set by the user, we use it exactly.
         */
        $prefixPattern = empty($this->tablePrefix)
            ? '[a-zA-Z0-9_]+'
            : preg_quote($this->tablePrefix, '/');

        // Match the full INSERT statement.
        // We look for INSERT INTO ... VALUES ... ; but we must be careful not to stop at semicolons inside post content.
        // In most SQL dumps, the statement ends with a semicolon at the end of a line.
        $pattern = '/INSERT\s+INTO\s+`?' . $prefixPattern . 'posts`?\s*\(([^)]+)\)\s*VALUES\s*(.*?)(?=;\s*(\r?\n|$)|;\s*(?:\/\*|INSERT|UNLOCK|COMMIT|BEGIN|SET|DROP|CREATE))/is';

        if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $columnsPart = $match[1];
            $valuesPart = $match[2];

            // Parse column names
            $columns = array_map(
                fn($col) => trim(str_replace('`', '', $col)),
                explode(',', $columnsPart)
            );

            // Extract each value tuple: ('...'),('...')
            // We use a robust approach that handles escaped single quotes \'
            $rows = $this->extractValueRows($valuesPart);
            $this->totalFoundCount += count($rows);

            foreach ($rows as $rawRow) {
                $values = $this->parseValueRow($rawRow, count($columns));
                $row = array_combine($columns, $values);

                if (!$row) {
                    continue;
                }

                // Filter: only published posts
                $postType = $row['post_type'] ?? '';
                $postStatus = $row['post_status'] ?? '';

                if ($postType !== 'post' || $postStatus !== 'publish') {
                    $this->filteredCount++;
                    continue;
                }

                // Guard: skip rows with empty title
                if (empty(trim($row['post_title'] ?? ''))) {
                    continue;
                }

                $posts[] = $row;
            }
        }

        return $posts;
    }

    /**
     * Split the VALUES portion of an INSERT statement into individual row strings.
     * Handles single-quoted strings with escaped quotes inside them.
     *
     * @param  string  $valuesPart  e.g. "('a','b'),('c','d')"
     * @return string[]
     */
    protected function extractValueRows(string $valuesPart): array
    {
        $rows = [];
        $depth = 0;
        $inStr = false;
        $escape = false;
        $start = null;
        $len = strlen($valuesPart);

        for ($i = 0; $i < $len; $i++) {
            $char = $valuesPart[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\' && $inStr) {
                $escape = true;
                continue;
            }

            if ($char === "'" && !$escape) {
                $inStr = !$inStr;
            }
            
            if ($inStr) {
                continue;
            }

            if ($char === '(') {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $rows[] = substr($valuesPart, $start, $i - $start);
                    $start = null;
                }
            }
        }

        return $rows;
    }

    /**
     * Parse an individual row string (contents inside the parentheses) into an array
     * of scalar values, respecting single-quoted strings and MySQL NULL.
     *
     * @param  string  $row
     * @param  int     $expectedCount  Number of expected columns.
     * @return string[]
     */
    protected function parseValueRow(string $row, int $expectedCount): array
    {
        $values = [];
        $current = '';
        $inStr = false;
        $escape = false;
        $len = strlen($row);

        for ($i = 0; $i < $len; $i++) {
            $char = $row[$i];

            if ($escape) {
                // Handle common MySQL escape sequences
                $current .= match ($char) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    '\'' => "'",
                    '\\' => '\\',
                    default => $char,
                };
                $escape = false;
                continue;
            }

            if ($char === '\\' && $inStr) {
                $escape = true;
                continue;
            }

            if ($char === "'") {
                if ($inStr) {
                    // Possible end of string — peek ahead to see if it's a doubled quote ''
                    if (isset($row[$i + 1]) && $row[$i + 1] === "'") {
                        $current .= "'";
                        $i++; // skip the second quote
                    } else {
                        $inStr = false;
                    }
                } else {
                    $inStr = true;
                }
                continue;
            }

            if (!$inStr && $char === ',') {
                $values[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        // Last value
        $values[] = $current;

        // Normalise NULL
        $values = array_map(function ($v) {
            $trimmed = trim($v);
            if (strtoupper($trimmed) === 'NULL') {
                return null;
            }
            // Strip wrapping quotes if they exist (though parseValueRow should have handled it)
            if (str_starts_with($trimmed, "'") && str_ends_with($trimmed, "'")) {
                return substr($trimmed, 1, -1);
            }
            return $trimmed;
        }, $values);

        // Pad or trim to expected column count
        while (count($values) < $expectedCount) {
            $values[] = '';
        }

        return array_slice($values, 0, $expectedCount);
    }

    // -----------------------------------------------------------------------
    // Permission Guard
    // -----------------------------------------------------------------------

    public function checkAccess(): void
    {
        if (!auth()->check()) {
            abort(403);
        }
    }

    /**
     * Configure a runtime database connection for a specific BaleList.
     */
    private function setupTenantConnection(BaleList $bale): string
    {
        $connectionName = 'bale_wp_migrator_' . str_replace('-', '_', $bale->id);

        config([
            "database.connections.{$connectionName}" => [
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

        return $connectionName;
    }

    // -----------------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------------

    public function render(): \Illuminate\View\View
    {
        return view('core::livewire.pages.wp-tools.wp-sql-migrator', [
            'baleLists' => $this->getBaleListsProperty(),
            'storedFiles' => $this->getStoredFiles(),
        ]);
    }
}
