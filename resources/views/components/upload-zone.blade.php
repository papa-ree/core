{{--
|--------------------------------------------------------------------------
| Component: x-core::upload-zone
|--------------------------------------------------------------------------
| A drag-and-drop file upload zone built with Alpine.js + Livewire.
| No FilePond dependency — pure Bale design system.
|
| Props:
|   @prop string $accept    Accepted MIME types (e.g. "image/png,image/jpeg")
|   @prop int    $maxSize   Max file size in KB (default: 512)
|   @prop string $label     Main label text inside the drop zone
|   @prop string $hint      Helper text shown below the label
|
| Usage:
|   <x-core::upload-zone
|       wire:model.live="thumbnail_new"
|       accept="image/png,image/jpeg,image/jpg"
|       maxSize="512"
|       :label="__('Drop image here or click to browse')"
|       :hint="__('PNG, JPG, JPEG up to 512KB')"
|   />
|
| Dependencies:
|   - Alpine.js (x-data, x-on, x-show, x-bind)
|   - Livewire (wire:model upload via $wire.upload())
|   - Tailwind CSS (Bale theme classes + dark mode)
|   - Lucide icons (x-lucide-*)
--}}

@props([
    'accept'   => 'image/*',
    'maxSize'  => 512,
    'label'    => null,
    'hint'     => null,
    'multiple' => false,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $label     = $label ?? __('Drop file here or click to browse');
    $hint      = $hint  ?? __('Max :size KB', ['size' => $maxSize]);

    // Merge class but exclude wire:model so we can pass it to the hidden input
    $containerClass = $attributes->get('class', '');
@endphp

<div
    x-data="{
        isDragging: false,
        files: [], {{-- Array of { name, size, preview, progress, uploading, error } --}}
        uploading: false,
        uploadProgress: 0,
        overallError: null,

        accept: '{{ $accept }}',
        maxSizeKb: {{ $maxSize }},
        isMultiple: {{ $multiple ? 'true' : 'false' }},

        handleFiles(fileList) {
            if (!fileList || fileList.length === 0) return;

            const newFiles = Array.from(fileList);
            
            if (!this.isMultiple) {
                this.files = []; {{-- Replace if single --}}
                this.uploadFiles([newFiles[0]]);
            } else {
                this.uploadFiles(newFiles);
            }
        },

        uploadFiles(fileArray) {
            this.overallError = null;
            const acceptedTypes = this.accept.split(',').map(a => a.trim());

            {{-- Filter and validate files --}}
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

            {{-- Create local entries for UI --}}
            validFiles.forEach(file => {
                const fileEntry = {
                    name: file.name,
                    size: (file.size / 1024).toFixed(1) + ' KB',
                    preview: null,
                    progress: 0,
                    uploading: true,
                    error: null
                };

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => { fileEntry.preview = e.target.result; };
                    reader.readAsDataURL(file);
                }

                this.files.push(fileEntry);
            });

            this.uploading = true;
            
            {{-- Upload via Livewire --}}
            $wire.upload(
                '{{ $wireModel }}',
                this.isMultiple ? validFiles : validFiles[0],
                () => { 
                    this.uploading = false;
                    this.files.forEach(f => { f.uploading = false; f.progress = 100; });
                },
                () => { 
                    this.uploading = false; 
                    this.overallError = '{{ __('Upload failed. Please try again.') }}'; 
                },
                (progress) => { 
                    this.uploadProgress = progress;
                    {{-- For simplicity, apply same progress to all currently uploading --}}
                    this.files.forEach(f => { if(f.uploading) f.progress = progress; });
                }
            );
        },

        removeFile(index) {
            this.files.splice(index, 1);
            if (this.files.length === 0) {
                this.clear();
            }
            {{-- Note: This only clears UI, backend clearing depends on updatedModel logic in parent --}}
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
    }"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop($event)"
    class="{{ $containerClass }}"
>
    {{-- ===== Drop Zone ===== --}}
    <div
        x-show="!isMultiple || files.length === 0"
        x-on:click="$refs.fileInput.click()"
        :class="isDragging
            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 scale-[1.01] shadow-lg shadow-purple-500/10'
            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-purple-400 dark:hover:border-purple-600 hover:bg-purple-50/50 dark:hover:bg-purple-900/10'"
        class="relative flex flex-col items-center justify-center gap-3 w-full min-h-[160px] rounded-2xl border-2 border-dashed
               cursor-pointer transition-all duration-300 group"
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

        {{-- Drag overlay indicator --}}
        <div
            x-show="isDragging"
            class="absolute inset-0 flex items-center justify-center rounded-2xl pointer-events-none"
        >
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

    {{-- ===== Multiple Preview List ===== --}}
    <div x-show="files.length > 0" class="mt-4 space-y-3">
        <template x-for="(file, index) in files" :key="index">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative flex items-center gap-4 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm transition-all"
            >
                {{-- Preview Thumbnail --}}
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

                {{-- File Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="file.name"></p>
                        <span class="text-[10px] font-medium text-gray-500" x-text="file.size"></span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-linear-to-r from-indigo-500 to-purple-600 transition-all duration-300"
                            :style="'width:' + file.progress + '%'"
                        ></div>
                    </div>
                </div>

                {{-- Status / Actions --}}
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

                    <button
                        type="button"
                        @click="removeFile(index)"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </template>

        {{-- Add more files button (only for multiple) --}}
        <template x-if="isMultiple && files.length > 0">
            <button
                type="button"
                @click="$refs.fileInput.click()"
                class="w-full py-2 border border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-xs font-semibold text-gray-500 hover:border-purple-400 hover:text-purple-500 transition-all bg-gray-50/30 dark:bg-gray-800/30"
            >
                + {{ __('Add more files') }}
            </button>
        </template>
    </div>

    {{-- ===== Overall Error message ===== --}}
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

</div>
