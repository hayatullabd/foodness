@php
    $story = $story ?? null;
    $mediaType = old('media_type', $story?->media_type ?? 'image');
@endphp

<div class="space-y-5">
    {{-- Story Title & Tag --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label" for="title">Story Title (Bubble Label) <span class="text-rose-500">*</span></label>
            <input
                id="title"
                name="title"
                type="text"
                value="{{ old('title', $story?->title) }}"
                required
                placeholder="e.g. New In, Everyday, Panjabi"
                class="field"
            >
            <p class="mt-1 text-xs text-muted">Displayed below the story card or avatar.</p>
            @error('title') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label" for="tag">Story Sub-Tag (Badge)</label>
            <input
                id="tag"
                name="tag"
                type="text"
                value="{{ old('tag', $story?->tag) }}"
                placeholder="e.g. JUST LANDED, TRENDING NOW"
                class="field"
            >
            <p class="mt-1 text-xs text-muted">Displayed as an uppercase badge on the story card & modal.</p>
            @error('tag') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Media Type Selection --}}
    <div>
        <label class="admin-label mb-2">Media Type <span class="text-rose-500">*</span></label>
        <div class="grid grid-cols-2 gap-3 max-w-md">
            <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition hover:bg-mist/30 {{ $mediaType === 'image' ? 'border-[#0B733E] bg-[#0B733E]/5 ring-1 ring-[#0B733E]' : 'border-ink/15 bg-white' }}" id="label-type-image">
                <input
                    type="radio"
                    name="media_type"
                    value="image"
                    class="sr-only"
                    @checked($mediaType === 'image')
                    onchange="handleMediaTypeChange('image')"
                >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0B733E]/10 text-[#0B733E]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-xs font-bold text-ink">Image Story</span>
                    <span class="block text-[11px] text-muted">JPG, PNG, WebP</span>
                </div>
            </label>

            <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition hover:bg-mist/30 {{ $mediaType === 'video' ? 'border-[#0B733E] bg-[#0B733E]/5 ring-1 ring-[#0B733E]' : 'border-ink/15 bg-white' }}" id="label-type-video">
                <input
                    type="radio"
                    name="media_type"
                    value="video"
                    class="sr-only"
                    @checked($mediaType === 'video')
                    onchange="handleMediaTypeChange('video')"
                >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-xs font-bold text-ink">Video Story</span>
                    <span class="block text-[11px] text-muted">MP4, WebM, MOV</span>
                </div>
            </label>
        </div>
    </div>

    {{-- Media File Upload & Preview --}}
    <div class="rounded-xl border border-ink/10 bg-slate-50/50 p-4 space-y-4">
        <div>
            @include('admin.partials.dropzone', [
                'name' => 'media_file',
                'id' => 'media_file',
                'label' => ($mediaType === 'video' ? 'Upload Video File' : 'Upload Image File') . (!$story ? ' *' : ''),
                'accept' => $mediaType === 'video' ? 'video/mp4,video/webm,video/quicktime' : 'image/jpeg,image/png,image/webp,image/gif',
                'isVideo' => $mediaType === 'video',
                'current' => $story && $story->media_path && !str_starts_with($story->media_path, 'http') ? $story->mediaUrl() : null,
                'hint' => $mediaType === 'video' ? 'Optional cover can be added below · MP4, WebM (Max 50MB)' : 'One image is enough. It is used on the card and inside the story.',
                'maxHeight' => 'h-48',
            ])
            @error('media_file') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>

        {{-- Optional Direct Video / Image URL --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="admin-label !mb-0" for="media_url">Or Direct Video / YouTube Shorts URL (Optional)</label>
                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">
                    Recommended: 1080 × 1920 px (9:16)
                </span>
            </div>
            <input
                id="media_url"
                name="media_url"
                type="url"
                value="{{ old('media_url', str_starts_with($story?->media_path ?? '', 'http') ? $story?->media_path : '') }}"
                placeholder="https://www.youtube.com/shorts/... or direct .mp4 (Recommended: 1080 × 1920 px - 9:16)"
                class="field text-xs"
            >
            <p class="mt-1 text-xs text-muted">Provide a YouTube Shorts or direct MP4 video link. Vertical 9:16 format fits fullscreen view perfectly.</p>
            @error('media_url') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Optional cover, only for video. Image stories reuse the uploaded photo. --}}
    <div class="rounded-xl border border-ink/10 bg-slate-50/50 p-4 {{ $mediaType === 'image' ? 'hidden' : '' }}" id="story-cover-upload">
        <input type="hidden" name="auto_thumbnail_data" id="auto_thumbnail_data" value="">
        <input type="hidden" name="auto_thumbnail_url" id="auto_thumbnail_url" value="">

        @include('admin.partials.dropzone', [
            'name' => 'thumbnail_file',
            'id' => 'thumbnail_file',
            'label' => 'Story Circle Cover / Thumbnail (Optional)',
            'current' => $story && $story->thumbnail_path ? $story->thumbnailUrl() : null,
            'hint' => 'Recommended: 300 × 300 px (1:1 Circle) · JPG, PNG, WEBP',
            'maxHeight' => 'h-28',
        ])
        <div id="auto-thumb-badge" class="hidden mt-2 inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-semibold text-emerald-800">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>✨ Thumbnail auto-captured from video</span>
        </div>
        <p class="mt-1 text-xs text-muted">Circular cover thumbnail for story. If omitted, a frame is automatically captured from the video or YouTube.</p>
        @error('thumbnail_file') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
    </div>

    {{-- Story Action / Link --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="admin-label" for="link_url">Story Action Link (URL)</label>
            <input
                id="link_url"
                name="link_url"
                type="text"
                value="{{ old('link_url', $story?->link_url) }}"
                placeholder="e.g. /shop or product link"
                class="field"
            >
            <p class="mt-1 text-xs text-muted">When customer taps the button in the story, they will be redirected to this URL.</p>
            @error('link_url') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label" for="link_text">Button Label (CTA Text)</label>
            <input
                id="link_text"
                name="link_text"
                type="text"
                value="{{ old('link_text', $story?->link_text ?? 'SHOP NOW') }}"
                placeholder="e.g. SHOP NOW, VIEW PRODUCTS"
                class="field"
            >
            <p class="mt-1 text-xs text-muted">Button label displayed on the story card & modal.</p>
            @error('link_text') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Story Options: Duration, Sort Order, Active Status --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
        <div>
            <label class="admin-label" for="duration">Duration (Seconds)</label>
            <input
                id="duration"
                name="duration"
                type="number"
                min="1"
                max="120"
                value="{{ old('duration', $story?->duration ?? ($mediaType === 'video' ? 15 : 5)) }}"
                class="field"
            >
            <p class="mt-1 text-xs text-muted">5-7 seconds recommended for images. Videos will play for their full duration.</p>
            @error('duration') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label" for="sort_order">Sort Order</label>
            <input
                id="sort_order"
                name="sort_order"
                type="number"
                min="0"
                value="{{ old('sort_order', $story?->sort_order ?? 0) }}"
                class="field"
            >
            <p class="mt-1 text-xs text-muted">Lower numbers appear first (0, 1, 2...).</p>
            @error('sort_order') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col justify-center">
            <span class="admin-label mb-1">Status</span>
            <label class="flex items-center gap-2.5 text-xs font-semibold text-ink cursor-pointer mt-1">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="h-4 w-4 rounded border-ink/20 text-[#0B733E] focus:ring-[#0B733E]"
                    @checked(old('is_active', $story?->is_active ?? true))
                >
                <span>Active (Display on storefront)</span>
            </label>
        </div>
    </div>
</div>

<script>
function handleMediaTypeChange(type) {
    const imgLabel = document.getElementById('label-type-image');
    const vidLabel = document.getElementById('label-type-video');
    const fileLabel = document.getElementById('media-file-label');
    const fileInput = document.getElementById('media_file');
    const fileHint = document.getElementById('media-file-hint');
    const durationInput = document.getElementById('duration');

    if (type === 'video') {
        imgLabel.classList.remove('border-[#0B733E]', 'bg-[#0B733E]/5', 'ring-1', 'ring-[#0B733E]');
        imgLabel.classList.add('border-ink/15', 'bg-white');
        vidLabel.classList.add('border-[#0B733E]', 'bg-[#0B733E]/5', 'ring-1', 'ring-[#0B733E]');
        vidLabel.classList.remove('border-ink/15', 'bg-white');

        if (fileLabel) fileLabel.innerHTML = 'Upload Video File <span class="text-rose-500">*</span>';
        if (fileInput) fileInput.accept = 'video/mp4,video/webm,video/quicktime';
        if (fileHint) fileHint.textContent = 'Optional cover can be added below · MP4, WebM (Max 50MB)';
        document.getElementById('story-cover-upload')?.classList.remove('hidden');
        if (!durationInput.value || durationInput.value === '5') {
            durationInput.value = '15';
        }
    } else {
        vidLabel.classList.remove('border-[#0B733E]', 'bg-[#0B733E]/5', 'ring-1', 'ring-[#0B733E]');
        vidLabel.classList.add('border-ink/15', 'bg-white');
        imgLabel.classList.add('border-[#0B733E]', 'bg-[#0B733E]/5', 'ring-1', 'ring-[#0B733E]');
        imgLabel.classList.remove('border-ink/15', 'bg-white');

        if (fileLabel) fileLabel.innerHTML = 'Upload Image File <span class="text-rose-500">*</span>';
        if (fileInput) fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
        if (fileHint) fileHint.textContent = 'One image is enough. It is used on the card and inside the story.';
        document.getElementById('story-cover-upload')?.classList.add('hidden');
        if (!durationInput.value || durationInput.value === '15') {
            durationInput.value = '5';
        }
    }
}

// Automatic Video Frame / YouTube Thumbnail Extractor for Story Circle Cover
document.addEventListener('DOMContentLoaded', function () {
    const mediaFileInput = document.getElementById('media_file');
    const mediaUrlInput = document.getElementById('media_url');
    const thumbFileInput = document.getElementById('thumbnail_file');
    const autoThumbDataInput = document.getElementById('auto_thumbnail_data');
    const autoThumbUrlInput = document.getElementById('auto_thumbnail_url');
    const autoBadge = document.getElementById('auto-thumb-badge');

    function showAutoThumbnailPreview(srcUrl, isYoutube = false) {
        if (!thumbFileInput || (thumbFileInput.files && thumbFileInput.files.length > 0)) {
            return;
        }

        const thumbDropzone = document.getElementById('dropzone_thumbnail_file');
        if (thumbDropzone) {
            const placeholder = thumbDropzone.querySelector('[data-dropzone-placeholder]');
            const previewWrap = thumbDropzone.querySelector('[data-dropzone-preview-wrap]');
            const img = thumbDropzone.querySelector('[data-dropzone-img]') || previewWrap?.querySelector('img');

            if (placeholder) placeholder.classList.add('hidden');
            if (previewWrap) previewWrap.classList.remove('hidden');
            if (img) {
                img.src = srcUrl;
                img.classList.remove('hidden');
            }
        }

        if (autoBadge) {
            autoBadge.innerHTML = isYoutube
                ? '<span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span><span>✨ Thumbnail auto-captured from YouTube</span>'
                : '<span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span><span>✨ Thumbnail auto-captured from video frame</span>';
            autoBadge.classList.remove('hidden');
        }
    }

    function extractVideoFrame(file) {
        if (!file || !file.type.startsWith('video/')) {
            return;
        }

        const video = document.createElement('video');
        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        const objectUrl = URL.createObjectURL(file);
        video.src = objectUrl;

        video.onloadedmetadata = function () {
            let seekTime = 1.0;
            if (video.duration && video.duration > 0) {
                seekTime = Math.min(1.0, video.duration / 3);
            }
            video.currentTime = seekTime;
        };

        video.onseeked = function () {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = 480;
                canvas.height = 480;
                const ctx = canvas.getContext('2d');

                const vWidth = video.videoWidth || 480;
                const vHeight = video.videoHeight || 480;
                const size = Math.min(vWidth, vHeight);
                const startX = (vWidth - size) / 2;
                const startY = (vHeight - size) / 2;

                ctx.drawImage(video, startX, startY, size, size, 0, 0, canvas.width, canvas.height);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                if (autoThumbDataInput) {
                    autoThumbDataInput.value = dataUrl;
                }

                showAutoThumbnailPreview(dataUrl, false);
            } catch (err) {
                console.warn('Canvas frame capture error:', err);
            } finally {
                URL.revokeObjectURL(objectUrl);
            }
        };

        video.onerror = function () {
            URL.revokeObjectURL(objectUrl);
        };
    }

    function checkYoutubeUrl(url) {
        if (!url) return;
        const ytMatch = url.match(/(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
        if (ytMatch && ytMatch[1]) {
            const ytId = ytMatch[1];
            const thumbUrl = `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`;
            if (autoThumbUrlInput) {
                autoThumbUrlInput.value = thumbUrl;
            }
            showAutoThumbnailPreview(thumbUrl, true);
        }
    }

    mediaFileInput?.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            extractVideoFrame(this.files[0]);
        }
    });

    mediaUrlInput?.addEventListener('input', function () {
        checkYoutubeUrl(this.value.trim());
    });

    // Check existing media URL on load if thumbnail empty
    if (mediaUrlInput && mediaUrlInput.value && (!thumbFileInput || !thumbFileInput.value)) {
        checkYoutubeUrl(mediaUrlInput.value.trim());
    }

    // Hide auto badge if user manually selects a custom thumbnail
    thumbFileInput?.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            if (autoBadge) autoBadge.classList.add('hidden');
            if (autoThumbDataInput) autoThumbDataInput.value = '';
            if (autoThumbUrlInput) autoThumbUrlInput.value = '';
        }
    });
});
</script>
