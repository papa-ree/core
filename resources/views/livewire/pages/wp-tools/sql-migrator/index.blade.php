<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">WP SQL Migrator</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Upload database dump untuk memulai proses migrasi data dari WordPress.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="col-span-1">
            @livewire(\Bale\Core\Livewire\Pages\WpTools\SqlMigrator\Upload::class)
        </div>
        
        <div class="col-span-1">
            @livewire(\Bale\Core\Livewire\Pages\WpTools\SqlMigrator\FileList::class)
        </div>
    </div>
</div>
