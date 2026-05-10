<?php

namespace Bale\Core\Livewire\Pages\WpTools\SqlMigrator;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class FileList extends Component
{
    public array $files = [];

    public bool $hasConnectionError = false;
    public string $connectionErrorMessage = '';

    public function mount()
    {
        $this->loadFiles();
    }

    #[On('file-uploaded')]
    public function loadFiles()
    {
        $this->hasConnectionError = false;
        $this->connectionErrorMessage = '';

        try {
            $disk = Storage::disk('s3');
            // Ensure directory exists or ignore errors if empty
            $allFiles = $disk->files('private/sql-migrator');
            
            $filesData = [];
            foreach ($allFiles as $file) {
                // Kita bisa mengambil size dan lastModified jika butuh,
                // tapi karena ini S3, memanggil size() per file bisa memperlambat loading
                // untuk iterasi pertama, kita tampilkan namanya saja
                $filesData[] = [
                    'path' => $file,
                    'name' => basename($file),
                ];
            }

            // Urutkan kasar (yang terbaru di atas karena s3 list biasanya alphabetic / asc)
            $this->files = array_reverse($filesData);
        } catch (\Exception $e) {
            $this->hasConnectionError = true;
            $this->connectionErrorMessage = $e->getMessage();
            $this->files = [];
        }
    }

    public function deleteFile($path)
    {
        try {
            Storage::disk('s3')->delete($path);
            $this->loadFiles();
            $this->dispatch('toast', message: 'File berhasil dihapus dari S3', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menghapus file: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('core::livewire.pages.wp-tools.sql-migrator.file-list');
    }
}
