<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <h3 class="text-lg font-medium text-slate-800 dark:text-white mb-4">Upload File SQL</h3>
    
    <div class="space-y-4">
        <div 
            x-data="{ isUploading: false, progress: 0 }"
            x-on:livewire-upload-start="isUploading = true"
            x-on:livewire-upload-finish="isUploading = false; progress = 0"
            x-on:livewire-upload-error="isUploading = false"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
            class="relative"
        >
            <label for="sql_file_upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 dark:hover:bg-bray-800 dark:bg-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:hover:border-slate-500 dark:hover:bg-slate-600 transition-colors">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 mb-4 text-slate-500 dark:text-slate-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                    </svg>
                    <p class="mb-2 text-sm text-slate-500 dark:text-slate-400"><span class="font-semibold">Klik untuk upload</span> atau drag and drop</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">File SQL akan diunggah otomatis</p>
                </div>
                <input id="sql_file_upload" type="file" wire:model="sqlFile" class="hidden" accept=".sql,.txt" />
            </label>
            
            <!-- Progress Bar untuk upload Livewire (Temp) -->
            <div x-show="isUploading" class="w-full bg-slate-200 rounded-full h-2.5 dark:bg-slate-700 mt-4 overflow-hidden">
                <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-300" x-bind:style="'width: ' + progress + '%'"></div>
            </div>
            <div x-show="isUploading" class="text-xs text-center text-slate-500 mt-2">
                Mengunggah ke server: <span x-text="progress"></span>%
            </div>
        </div>

        <!-- Indikator saat proses penyimpanan ke S3 berlangsung -->
        <div wire:loading wire:target="sqlFile" class="w-full text-center py-2">
            <span class="text-sm text-slate-500 dark:text-slate-400 flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memindahkan ke S3, harap tunggu...
            </span>
        </div>
    </div>
</div>
