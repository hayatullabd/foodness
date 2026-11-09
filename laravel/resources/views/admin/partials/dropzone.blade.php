@props([
    'name',
    'id' => null,
    'label' => null,
    'current' => null,
    'hint' => 'Click or drag & drop to upload (PNG, JPG, WEBP · Max 5 MB)',
    'accept' => 'image/*',
    'isVideo' => false,
    'maxHeight' => 'h-36',
    'removeName' => null,
    'nativeFile' => false,
])

@php
    $inputId = $id ?? $name;
    $dropzoneId = 'dropzone_' . str_replace(['[', ']', '.'], '_', $inputId);
    $hasCurrent = !empty($current);
@endphp

<div class="space-y-1.5" id="{{ $dropzoneId }}_wrapper" data-dropzone-root @if ($nativeFile) data-dropzone-native="1" @endif>
    @if ($label)
        <label class="admin-label !mb-0" for="{{ $inputId }}">{{ $label }}</label>
    @endif

    {{-- Hidden File Input --}}
    <input
        type="file"
        id="{{ $inputId }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        class="sr-only"
        data-dropzone-input
    >

    {{-- Hidden Companion Input for Selected Media Path --}}
    <input
        type="hidden"
        name="{{ $name }}_media_path"
        id="{{ $dropzoneId }}_media_path"
        value=""
        data-dropzone-media-input
    >

    @if ($removeName)
        <input type="hidden" name="{{ $removeName }}" id="{{ $dropzoneId }}_remove" value="0" data-dropzone-remove-input>
    @endif

    {{-- Interactive Dropzone Container --}}
    <div
        id="{{ $dropzoneId }}"
        class="group relative flex {{ $maxHeight }} w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-ink/20 bg-mist/20 p-3 text-center transition-all duration-200 hover:border-sage hover:bg-mist/40 focus:outline-none"
        data-dropzone-box
        tabindex="0"
        role="button"
        aria-label="Upload file"
    >
        {{-- Empty Placeholder View --}}
        <div class="flex flex-col items-center justify-center gap-1.5 pointer-events-none {{ $hasCurrent ? 'hidden' : '' }}" data-dropzone-placeholder>
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-xs border border-ink/10 text-muted transition group-hover:scale-105 group-hover:text-sage group-hover:border-sage/40">
                @if ($isVideo)
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z" />
                    </svg>
                @else
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                @endif
            </div>

            <div class="space-y-0.5">
                <p class="text-xs font-semibold text-ink">
                    <span class="text-sage underline underline-offset-2">Click to upload</span> or drag and drop
                </p>
                <p class="text-[11px] text-muted leading-tight">
                    {{ $hint }}
                </p>
            </div>
        </div>

        {{-- Preview View (Active Image or Video) --}}
        <div class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-lg {{ $hasCurrent ? '' : 'hidden' }}" data-dropzone-preview-wrap>
            @if ($isVideo)
                <video
                    src="{{ $hasCurrent ? $current : '' }}"
                    class="h-full w-full object-contain rounded-lg"
                    controls
                    playsinline
                    data-dropzone-video
                ></video>
                <img
                    src=""
                    alt="Preview"
                    class="hidden h-full w-full object-contain rounded-lg"
                    data-dropzone-img
                >
            @else
                <img
                    src="{{ $hasCurrent ? $current : '' }}"
                    alt="Uploaded preview"
                    class="h-full w-full object-contain rounded-lg"
                    data-dropzone-img
                >
            @endif

            {{-- Hover Action Overlay --}}
            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/60 opacity-0 transition-opacity duration-200 group-hover:opacity-100 rounded-lg p-2">
                <span class="inline-flex items-center gap-1 rounded-md bg-white/95 px-3 py-1.5 text-xs font-semibold text-ink shadow-sm pointer-events-none">
                    <svg class="h-3.5 w-3.5 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Click to Change</span>
                </span>

                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition cursor-pointer"
                    title="Remove file"
                    data-dropzone-clear-btn
                    onclick="event.stopPropagation();"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Remove</span>
                </button>
            </div>
        </div>

        {{-- Drag Active State Indicator --}}
        <div class="pointer-events-none absolute inset-0 hidden items-center justify-center rounded-xl bg-sage/10 border-2 border-sage" data-dropzone-drag-overlay>
            <span class="rounded-full bg-sage px-3 py-1 text-xs font-bold text-white shadow-md animate-bounce">
                Drop file here to upload
            </span>
        </div>
    </div>

    {{-- File Meta Info Bar (appears when a new file is chosen) --}}
    <div class="hidden items-center justify-between px-1 text-[11px] text-muted" data-dropzone-info-bar>
        <span class="truncate font-medium text-ink max-w-[80%]" data-dropzone-filename></span>
        <span class="font-mono" data-dropzone-filesize></span>
    </div>
</div>
