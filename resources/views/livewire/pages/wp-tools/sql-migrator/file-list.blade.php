<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <h3 class="text-lg font-medium text-slate-800 dark:text-white mb-4">Daftar File SQL</h3>

    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
        @if($hasConnectionError)
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-3">
                    <svg class="w-6 h-6 text-red-500 dark:text-red-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-800 dark:text-slate-200 mb-1">Koneksi S3 Terputus</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto mb-4">{{ $connectionErrorMessage }}</p>
                <button wire:click="loadFiles" class="px-4 py-2 text-xs font-medium text-white bg-slate-800 rounded-lg hover:bg-slate-900 focus:ring-4 focus:ring-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 dark:focus:ring-slate-800 transition-colors">
                    Coba Lagi
                </button>
            </div>
        @else
            @forelse($files as $file)
                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="flex-shrink-0 text-slate-400">
                            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3v4a1 1 0 0 1-1 1H5m4 6h6m-6 4h6m4-8v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l5 5h1a2 2 0 0 1 2 2Z"/>
                            </svg>
                        </div>
                        <div class="truncate">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate" title="{{ $file['name'] }}">
                                {{ $file['name'] }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center flex-shrink-0 ml-4 space-x-2">
                        <button wire:click="deleteFile('{{ $file['path'] }}')" wire:confirm="Yakin ingin menghapus file ini dari S3?" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 transition-colors" title="Hapus File">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 mb-3">
                        <svg class="w-6 h-6 text-slate-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2M12 4v12m0-12 4 4m-4-4-4 4"/>
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada file yang diunggah di <br/> <code class="text-xs bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">private/sql-migrator/</code></p>
                </div>
            @endforelse
        @endif
    </div>
</div>
