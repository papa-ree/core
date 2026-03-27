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
    'accept'  => 'image/*',
    'maxSize' => 512,
    'label'   => null,
    'hint'    => null,
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
        preview: null,
        fileName: null,
        fileSize: null,
        error: null,
        uploading: false,
        uploadProgress: 0,

        accept: '{{ $accept }}',
        maxSizeKb: {{ $maxSize }},

        handleFile(file) {
            if (!file) return;

            // File type check
            const accepted = this.accept.split(',').map(a => a.trim());
            const typeOk = accepted.some(type => {
                if (type === '*' || type === '*/*') return true;
                if (type.endsWith('/*')) return file.type.startsWith(type.replace('/*', '/'));
                return file.type === type;
            });

            if (!typeOk) {
                this.error = '{{ __('Invalid file type. Accepted:') }} ' + this.accept;
                return;
            }

            // File size check (maxSizeKb → bytes)
            if (file.size > this.maxSizeKb * 1024) {
                this.error = '{{ __('File is too large. Max size:') }} ' + this.maxSizeKb + ' KB';
                return;
            }

            this.error = null;
            this.fileName = file.name;
            this.fileSize = (file.size / 1024).toFixed(1) + ' KB';

            // Show image preview if it's an image
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => { this.preview = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.preview = null;
            }

            // Upload via Livewire
            this.uploading = true;
            $wire.upload(
                '{{ $wireModel }}',
                file,
                () => { this.uploading = false; this.uploadProgress = 0; },
                () => { this.uploading = false; this.error = '{{ __('Upload failed. Please try again.') }}'; },
                (progress) => { this.uploadProgress = progress; }
            );
        },

        clear() {
            this.preview = null;
            this.fileName = null;
            this.fileSize = null;
            this.error = null;
            this.uploading = false;
            this.uploadProgress = 0;
            $wire.set('{{ $wireModel }}', null);
        },

        onDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) this.handleFile(file);
        },

        onInputChange(event) {
            const file = event.target.files[0];
            if (file) this.handleFile(file);
        },
    }"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop($event)"
    class="{{ $containerClass }}"
>
    {{-- ===== Drop Zone (no file selected) ===== --}}
    <div
        x-show="!fileName"
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
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
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
            class="sr-only"
        />
    </div>

    {{-- ===== Preview card (file selected) ===== --}}
    <div
        x-show="fileName"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative w-full rounded-2xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
    >
        {{-- Image thumbnail preview --}}
        <div x-show="preview" class="relative group">
            <img
                :src="preview"
                alt="Preview"
                class="w-full max-h-48 object-cover object-center"
            />
            {{-- Hover overlay --}}
            <div class="absolute inset-0 bg-linear-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        {{-- File icon preview (non-image) --}}
        <div x-show="!preview && fileName" class="flex items-center gap-3 px-4 py-4">
            <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                <x-lucide-file class="w-6 h-6" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="fileName"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="fileSize"></p>
            </div>
        </div>

        {{-- File info bar (shown under image preview) --}}
        <div x-show="preview" class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate" x-text="fileName"></p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500" x-text="fileSize"></p>
            </div>
        </div>

        {{-- Upload progress bar --}}
        <div x-show="uploading" class="absolute bottom-0 inset-x-0 h-1 bg-gray-200 dark:bg-gray-700">
            <div
                class="h-full bg-linear-to-r from-indigo-500 to-purple-600 transition-all duration-300"
                :style="'width:' + uploadProgress + '%'"
            ></div>
        </div>

        {{-- Uploading badge --}}
        <div
            x-show="uploading"
            class="absolute top-2 left-2 flex items-center gap-1.5 px-2 py-1 rounded-full bg-white/90 dark:bg-gray-800/90 border border-gray-200 dark:border-gray-600 shadow text-xs font-medium text-gray-700 dark:text-gray-300"
        >
            <div class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></div>
            {{ __('Uploading...') }} <span x-text="uploadProgress + '%'"></span>
        </div>

        {{-- Done badge --}}
        <div
            x-show="!uploading && fileName"
            x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute top-2 left-2 flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-600/90 text-white text-xs font-medium shadow"
        >
            <x-lucide-check class="w-3 h-3" />
            {{ __('Ready') }}
        </div>

        {{-- Remove button --}}
        <button
            type="button"
            @click.stop="clear()"
            class="absolute top-2 right-2 p-1.5 rounded-lg bg-red-500/90 hover:bg-red-600 text-white shadow-md transition-all duration-200 hover:scale-105"
            title="{{ __('Remove') }}"
        >
            <x-lucide-x class="w-3.5 h-3.5" />
        </button>
    </div>

    {{-- ===== Error message ===== --}}
    <div
        x-show="error"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
    >
        <x-lucide-alert-circle class="w-4 h-4 shrink-0 text-red-500" />
        <p class="text-xs font-medium text-red-700 dark:text-red-400" x-text="error"></p>
    </div>
</div>
