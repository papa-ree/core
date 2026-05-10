<?php

namespace Bale\Core\Livewire\Pages\WpTools\SqlMigrator;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Upload extends Component
{
    use WithFileUploads;

    public $sqlFile;

    public function updatedSqlFile()
    {
        $this->validate([
            'sqlFile' => 'required|file',
        ]);

        try {
            $fileName = $this->sqlFile->getClientOriginalName();
            $finalPath = 'private/sql-migrator/' . Str::random(20) . '-' . time() . '-' . $fileName;

            // Upload directly to S3
            Storage::disk('s3')->put($finalPath, $this->sqlFile->get());

            // Clear the file selection after successful upload
            $this->sqlFile = null;

            $this->dispatch('toast', message: 'File ' . $fileName . ' berhasil diupload ke S3', type: 'success');
            $this->dispatch('file-uploaded');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal upload file: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('core::livewire.pages.wp-tools.sql-migrator.upload');
    }
}
