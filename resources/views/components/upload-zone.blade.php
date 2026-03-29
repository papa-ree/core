{{--
|--------------------------------------------------------------------------
| Component: x-core::upload-zone
|--------------------------------------------------------------------------
| A drag-and-drop file upload zone built with Alpine.js + Livewire.
| No FilePond dependency — pure Bale design system.
|
| Props:
|   @prop string $accept         Accepted MIME types (e.g. "image/png,image/jpeg")
|   @prop int    $maxSize        Max file size in KB (default: 512)
|   @prop string $label          Main label text inside the drop zone
|   @prop string $hint           Helper text shown below the label
|   @prop bool   $multiple       Allow multiple file selection (default: false)
|   @prop array  $existingFiles  Already-uploaded files to display as previews.
|                                Each entry: { url, name, mime, s3Path }
|                                Pressing remove dispatches 'upload-zone:remove'
|                                with { index, file } so the parent can call
|                                $wire.deleteFile() immediately.
|
| Usage:
|   <x-core::upload-zone
|       wire:model.live="tempUpload"
|       accept="image/*,application/pdf"
|       maxSize="10240"
|       multiple
|       :existingFiles="$uploadedFilesForKey"
|       @upload-zone:remove.window="removeUploadedFile('myKey', $event.detail.index)"
|       label="{{ __('Drop files here') }}"
|       hint="{{ __('Images, PDF up to 10MB') }}"
|   />
|
| Dependencies:
|   - Alpine.js (x-data, x-on, x-show, x-bind)
|   - Livewire (wire:model upload via $wire.upload())
|   - Tailwind CSS (Bale theme classes + dark mode)
|   - Lucide icons (x-lucide-*)
--}}

@props([
    'accept'        => 'image/*',
    'maxSize'       => 512,
    'label'         => null,
    'hint'          => null,
    'multiple'      => false,
    'existingFiles' => [],
])

@php
    $wireModel      = $attributes->whereStartsWith('wire:model')->first();
    $label          = $label ?? __('Drop file here or click to browse');
    $hint           = $hint  ?? __('Max :size KB', ['size' => $maxSize]);
    $containerClass = $attributes->get('class', '');
@endphp

<div
    x-data="{
        isDragging: false,
        files: [],    {{-- Newly selected / uploading files --}}
        uploading: false,
        uploadProgress: 0,
        overallError: null,

        accept: '{{ $accept }}',
        maxSizeKb: {{ $maxSize }},
        isMultiple: {{ $multiple ? 'true' : 'false' }},

        {{-- Existing uploaded files passed from parent --}}
        existingFiles: @js($existingFiles),

        handleFiles(fileList) {
            if (!fileList || fileList.length === 0) return;
            const newFiles = Array.from(fileList);
            if (!this.isMultiple) {
                this.files = [];
                this.uploadFiles([newFiles[0]]);
            } else {
                this.uploadFiles(newFiles);
            }
        },

        uploadFiles(fileArray) {
            this.overallError = null;
            const acceptedTypes = this.accept.split(',').map(a => a.trim());

            const validFiles = fileArray.filter(file => {
                const typeOk = acceptedTypes.some(type => {
                    if (type === '*' || type === '*/*') return true;
                    if (type.endsWith('/*')) return file.type.startsWith(type.replace('/*', '/'));
                    return file.type === type;
                });
                if (!typeOk) {
                    this.overallError = '{{ __('Some files have invalid types. Accepted:') }} ' + this.accept;
                    return false;
                }
                if (file.size > this.maxSizeKb * 1024) {
                    this.overallError = '{{ __('Some files are too large. Max size:') }} ' + this.maxSizeKb + ' KB';
                    return false;
                }
                return true;
            });

            if (validFiles.length === 0) return;

            validFiles.forEach(file => {
                const fileEntry = {
                    name: file.name,
                    size: (file.size / 1024).toFixed(1) + ' KB',
                    preview: null,
                    progress: 0,
                    uploading: true,
                    error: null,
                };
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => { fileEntry.preview = e.target.result; };
                    reader.readAsDataURL(file);
                }
                this.files.push(fileEntry);
            });

            this.uploading = true;

            $wire.upload(
                '{{ $wireModel }}',
                this.isMultiple ? validFiles : validFiles[0],
                () => {
                    this.uploading = false;
                    this.files.forEach(f => { f.uploading = false; f.progress = 100; });
                    {{-- Clear new-file list after a short delay so user sees 100% --}}
                    setTimeout(() => { this.files = []; }, 1200);
                },
                () => {
                    this.uploading = false;
                    this.overallError = '{{ __('Upload failed. Please try again.') }}';
                },
                (progress) => {
                    this.uploadProgress = progress;
                    this.files.forEach(f => { if (f.uploading) f.progress = progress; });
                }
            );
        },

        {{-- Remove a newly-selected (not yet saved) file from UI --}}
        removeNewFile(index) {
            this.files.splice(index, 1);
            if (this.files.length === 0) this.clear();
        },

        {{-- Remove an already-uploaded (existing) file — dispatches event for parent to delete from server --}}
        removeExistingFile(index) {
            if (!confirm('{{ __('Remove this file? This cannot be undone.') }}')) return;
            const file = this.existingFiles[index];
            this.existingFiles.splice(index, 1);
            {{-- Parent listens to 'upload-zone:remove' to call $wire.deleteFile() --}}
            this.$dispatch('upload-zone:remove', { index, file });
        },

        clear() {
            this.files = [];
            this.overallError = null;
            this.uploading = false;
            this.uploadProgress = 0;
            $wire.set('{{ $wireModel }}', this.isMultiple ? [] : null);
        },

        onDrop(event) {
            this.isDragging = false;
            this.handleFiles(event.dataTransfer.files);
        },

        onInputChange(event) {
            this.handleFiles(event.target.files);
        },

        {{-- MIME helpers --}}
        detectMime(name) {
            if (!name) return '';
            const ext = name.split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return 'image/' + ext;
            if (ext === 'pdf') return 'application/pdf';
            if (['xlsx','xls','csv'].includes(ext)) return 'application/vnd.ms-excel';
            if (['docx','doc'].includes(ext)) return 'application/msword';
            return '';
        },
        isImage(file) {
            const mime = file.mime || file.preview ? (file.preview ? 'image/x' : '') : this.detectMime(file.name);
            if (file.url) {
                const ext = (file.name || file.url).split('.').pop().toLowerCase();
                return ['jpg','jpeg','png','gif','webp','svg'].includes(ext);
            }
            return mime && mime.startsWith('image/');
        },
        fileTypeIcon(file) {
            const name = file.name || file.url || '';
            const ext  = name.split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return null;
            if (ext === 'pdf')  return 'pdf';
            if (['xlsx','xls','csv'].includes(ext)) return 'xlsx';
            if (['docx','doc'].includes(ext)) return 'docx';
            return 'file';
        },
        fileEmoji(file) {
            const t = this.fileTypeIcon(file);
            if (t === 'pdf')  return '📄';
            if (t === 'xlsx') return '📊';
            if (t === 'docx') return '📝';
            return '📎';
        },
    }"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop($event)"
    class="{{ $containerClass }}"
>
    {{-- ===== Drop Zone (hidden once existingFiles present in single mode) ===== --}}
    <div
        x-show="isMultiple || (existingFiles.length === 0 && files.length === 0)"
        x-on:click="$refs.fileInput.click()"
        :class="isDragging
            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 scale-[1.01] shadow-lg shadow-purple-500/10'
            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-purple-400 dark:hover:border-purple-600 hover:bg-purple-50/50 dark:hover:bg-purple-900/10'"
        class="relative flex flex-col items-center justify-center gap-3 w-full min-h-[140px] rounded-2xl border-2 border-dashed cursor-pointer transition-all duration-300 group"
    >
        {{-- Animated upload icon --}}
        <div
            :class="isDragging ? 'scale-110 text-purple-600' : 'text-gray-400 dark:text-gray-500 group-hover:text-purple-500 dark:group-hover:text-purple-400'"
            class="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/60 transition-all duration-300 group-hover:bg-purple-50 dark:group-hover:bg-purple-900/20"
        >
            <x-lucide-upload-cloud class="w-8 h-8 transition-transform duration-300 group-hover:-translate-y-0.5" />
        </div>

        {{-- Label --}}
        <div class="text-center px-4">
            <p
                :class="isDragging ? 'text-purple-700 dark:text-purple-300' : 'text-gray-600 dark:text-gray-300'"
                class="text-sm font-semibold transition-colors duration-200">
                {{ $label }}
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
        </div>

        {{-- Drag overlay --}}
        <div x-show="isDragging" class="absolute inset-0 flex items-center justify-center rounded-2xl pointer-events-none">
            <span class="px-4 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-full shadow-lg">
                {{ __('Drop it!') }}
            </span>
        </div>

        {{-- Hidden file input --}}
        <input
            type="file"
            x-ref="fileInput"
            @change="onInputChange($event)"
            accept="{{ $accept }}"
            {{ $multiple ? 'multiple' : '' }}
            class="sr-only"
        />
    </div>

    {{-- ===== Existing Uploaded Files Grid ===== --}}
    <div x-show="existingFiles.length > 0" class="mt-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-1.5">
                <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-500" />
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                    {{ __('Uploaded') }} (<span x-text="existingFiles.length"></span>)
                </span>
            </div>
            <template x-if="isMultiple">
                <button type="button" @click="$refs.fileInput.click()"
                    class="text-xs font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400 hover:underline">
                    + {{ __('Add more') }}
                </button>
            </template>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            <template x-for="(file, index) in existingFiles" :key="index">
                <div class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-all">

                    {{-- Image preview --}}
                    <template x-if="isImage(file)">
                        <img :src="file.url" :alt="file.name" class="w-full h-24 object-cover" />
                    </template>

                    {{-- Document icon --}}
                    <template x-if="!isImage(file)">
                        <div class="w-full h-24 flex flex-col items-center justify-center gap-1"
                            :class="{
                                'bg-red-50 dark:bg-red-900/20':   fileTypeIcon(file) === 'pdf',
                                'bg-green-50 dark:bg-green-900/20': fileTypeIcon(file) === 'xlsx',
                                'bg-blue-50 dark:bg-blue-900/20':  fileTypeIcon(file) === 'docx',
                                'bg-gray-50 dark:bg-gray-900/20':  fileTypeIcon(file) === 'file'
                            }">
                            <span class="text-3xl" x-text="fileEmoji(file)"></span>
                            <span class="text-xs font-bold uppercase text-gray-500"
                                x-text="(file.name || file.url || 'file').split('.').pop()"></span>
                        </div>
                    </template>

                    {{-- File name --}}
                    <div class="p-2">
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate"
                            :title="file.name" x-text="file.name || file.url"></p>
                    </div>

                    {{-- Remove button (visible on hover) — immediate server delete via parent --}}
                    <button
                        type="button"
                        @click.stop="removeExistingFile(index)"
                        class="absolute top-1 right-1 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md"
                        title="{{ __('Remove file') }}"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- ===== In-progress Upload List ===== --}}
    <div x-show="files.length > 0" class="mt-4 space-y-2">
        <template x-for="(file, index) in files" :key="index">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative flex items-center gap-4 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
            >
                {{-- Thumbnail --}}
                <div class="shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                    <template x-if="file.preview">
                        <img :src="file.preview" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!file.preview">
                        <div class="flex items-center justify-center h-full text-gray-400">
                            <x-lucide-file class="w-5 h-5" />
                        </div>
                    </template>
                </div>

                {{-- File info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="file.name"></p>
                        <span class="text-[10px] font-medium text-gray-500" x-text="file.size"></span>
                    </div>
                    <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-linear-to-r from-indigo-500 to-purple-600 transition-all duration-300"
                            :style="'width:' + file.progress + '%'"></div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="shrink-0 flex items-center gap-2">
                    <template x-if="file.uploading">
                        <div class="flex items-center gap-1 text-[10px] font-bold text-purple-600 animate-pulse">
                            <span x-text="file.progress + '%'"></span>
                        </div>
                    </template>
                    <template x-if="!file.uploading">
                        <div class="text-emerald-500">
                            <x-lucide-check-circle class="w-5 h-5" />
                        </div>
                    </template>
                    <button type="button" @click="removeNewFile(index)"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- ===== Overall Error ===== --}}
    <div
        x-show="overallError"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
    >
        <x-lucide-alert-circle class="w-4 h-4 shrink-0 text-red-500" />
        <p class="text-xs font-medium text-red-700 dark:text-red-400" x-text="overallError"></p>
    </div>
</div>
