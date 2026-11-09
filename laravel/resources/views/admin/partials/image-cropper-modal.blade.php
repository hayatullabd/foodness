{{-- Global Image Cropper & Resizer Modal for Admin Panel --}}
<div
    id="admin-image-cropper-modal"
    style="display: none; position: fixed; inset: 0; z-index: 9999999 !important; background-color: rgba(15, 23, 42, 0.82) !important; backdrop-filter: blur(4px) !important; -webkit-backdrop-filter: blur(4px) !important;"
    class="p-2 sm:p-4 items-center justify-center transition-opacity"
    role="dialog"
    aria-modal="true"
    aria-labelledby="admin-cropper-title"
>
    <div class="relative flex max-h-[94vh] w-full max-w-4xl flex-col rounded-2xl bg-white shadow-2xl overflow-hidden border border-ink/15 my-auto" style="z-index: 10000000 !important;">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between border-b border-ink/10 px-4 py-3 bg-canvas/70">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sage/15 text-sage shrink-0">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 003.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0120.25 6v1.5m0 9V18A2.25 2.25 0 0118 20.25h-1.5m-9 0H6A2.25 2.25 0 013.75 18v-1.5" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="admin-cropper-title" class="text-sm font-semibold text-ink">Crop & Resize Image</h3>
                    <p id="admin-cropper-subtitle" class="text-xs text-muted truncate">Adjust crop boundary, rotation, and output resolution</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span id="cropper-queue-badge" class="hidden rounded-full bg-sage/20 px-2.5 py-0.5 text-xs font-semibold text-sage"></span>
                <button
                    type="button"
                    id="admin-cropper-cancel-btn"
                    class="rounded-md p-1.5 text-muted hover:bg-mist hover:text-ink transition cursor-pointer"
                    title="Close & Cancel"
                    aria-label="Close & Cancel"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Modal Body: Cropper Canvas --}}
        <div class="relative w-full bg-[#111827] overflow-hidden flex items-center justify-center" style="height: 420px; max-height: 54vh; min-height: 280px;">
            <img id="admin-cropper-image" src="" alt="Image to crop" style="display: block; max-width: 100%; max-height: 100%;">
        </div>

        {{-- Modal Controls Toolbar --}}
        <div class="border-t border-ink/10 bg-canvas/40 px-4 py-3 space-y-3">
            {{-- Aspect Ratio Presets & Dimensions Info --}}
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex flex-wrap items-center gap-1.5" id="cropper-aspect-presets">
                    <span class="font-semibold text-ink mr-1">Aspect Ratio:</span>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="NaN">Free</button>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="1">1:1 Square</button>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="0.75">3:4 Portrait</button>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="1.3333">4:3 Standard</button>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="1.7777">16:9 Banner</button>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="1.9047">1.91:1 Meta</button>
                    <button type="button" class="cropper-ratio-btn rounded px-2.5 py-1 text-xs font-medium border border-ink/15 bg-white hover:bg-mist text-ink cursor-pointer transition" data-ratio="3.75">3.75:1 Logo</button>
                </div>
                <div class="flex items-center gap-3 text-muted font-mono text-[11px]" id="cropper-dimension-info">
                    <span>Selection: <strong id="cropper-w-h" class="text-ink">0 × 0 px</strong></span>
                </div>
            </div>

            {{-- Toolbar Tools: Zoom, Rotate, Flip, Max-width Resize --}}
            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-ink/5">
                <div class="flex flex-wrap items-center gap-1.5">
                    {{-- Zoom In / Out --}}
                    <div class="inline-flex rounded-md shadow-xs border border-ink/15 bg-white overflow-hidden">
                        <button type="button" id="cropper-zoom-in" class="px-2.5 py-1 hover:bg-mist text-ink border-r border-ink/10 cursor-pointer transition" title="Zoom In">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </button>
                        <button type="button" id="cropper-zoom-out" class="px-2.5 py-1 hover:bg-mist text-ink cursor-pointer transition" title="Zoom Out">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                        </button>
                    </div>

                    {{-- Rotate Left / Right --}}
                    <div class="inline-flex rounded-md shadow-xs border border-ink/15 bg-white overflow-hidden">
                        <button type="button" id="cropper-rotate-left" class="px-2.5 py-1 hover:bg-mist text-ink border-r border-ink/10 text-xs font-medium flex items-center gap-1 cursor-pointer transition" title="Rotate Left 90°">
                            <span>↺</span> -90°
                        </button>
                        <button type="button" id="cropper-rotate-right" class="px-2.5 py-1 hover:bg-mist text-ink text-xs font-medium flex items-center gap-1 cursor-pointer transition" title="Rotate Right 90°">
                            <span>↻</span> +90°
                        </button>
                    </div>

                    {{-- Flip X / Y --}}
                    <div class="inline-flex rounded-md shadow-xs border border-ink/15 bg-white overflow-hidden">
                        <button type="button" id="cropper-flip-x" class="px-2.5 py-1 hover:bg-mist text-ink border-r border-ink/10 text-xs font-medium cursor-pointer transition" title="Flip Horizontal">
                            ⇄ Flip H
                        </button>
                        <button type="button" id="cropper-flip-y" class="px-2.5 py-1 hover:bg-mist text-ink text-xs font-medium cursor-pointer transition" title="Flip Vertical">
                            ⇅ Flip V
                        </button>
                    </div>

                    <button type="button" id="cropper-reset" class="rounded px-2.5 py-1 border border-ink/15 bg-white hover:bg-mist text-xs font-medium text-ink cursor-pointer transition" title="Reset Crop">
                        Reset
                    </button>
                </div>

                {{-- Resize Max Width Selector --}}
                <div class="flex items-center gap-1.5 text-xs">
                    <label for="cropper-max-size" class="text-muted font-medium">Max Output Size:</label>
                    <select id="cropper-max-size" class="rounded border border-ink/20 bg-white px-2 py-1 text-xs text-ink font-medium">
                        <option value="original">Original Cropped Size</option>
                        <option value="1600">1600 px (Ultra HD)</option>
                        <option value="1200" selected>1200 px (Standard Web / HD)</option>
                        <option value="800">800 px (Optimized / Fast)</option>
                        <option value="600">600 px (Compact)</option>
                        <option value="300">300 px (Thumbnail)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Modal Footer: Action Buttons --}}
        <div class="flex items-center justify-between border-t border-ink/10 bg-canvas px-4 py-3">
            <button
                type="button"
                id="cropper-use-original-btn"
                class="rounded-md border border-ink/20 bg-white px-3.5 py-1.5 text-xs font-semibold text-ink hover:bg-mist shadow-xs cursor-pointer transition"
            >
                Use Original (Skip Crop)
            </button>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="cropper-cancel-action-btn"
                    class="rounded-md px-3 py-1.5 text-xs font-medium text-muted hover:text-ink cursor-pointer transition"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    id="cropper-apply-btn"
                    class="admin-btn-primary !px-4 !py-1.5 text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm cursor-pointer"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Apply & Crop
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    let cropperInstance = null;
    let currentInput = null;
    let fileQueue = [];
    let currentFileIndex = 0;
    let processedFiles = [];
    let scaleX = 1;
    let scaleY = 1;

    const modal = document.getElementById('admin-image-cropper-modal');
    const imageEl = document.getElementById('admin-cropper-image');
    const queueBadge = document.getElementById('cropper-queue-badge');
    const dimInfo = document.getElementById('cropper-w-h');
    const subtitleEl = document.getElementById('admin-cropper-subtitle');
    const ratioBtns = document.querySelectorAll('.cropper-ratio-btn');
    const maxSizeSelect = document.getElementById('cropper-max-size');

    // Helper: open modal with maximum z-index display
    function openModal() {
        if (!modal) return;
        modal.style.setProperty('display', 'flex', 'important');
        document.body.style.overflow = 'hidden';
    }

    // Helper: close modal
    function closeModal() {
        if (!modal) return;
        modal.style.setProperty('display', 'none', 'important');
        document.body.style.overflow = '';
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
        if (imageEl) {
            imageEl.src = '';
        }
        currentInput = null;
        fileQueue = [];
        processedFiles = [];
        currentFileIndex = 0;
    }

    // Initialize Cropper on image
    function initCropper(aspectRatio) {
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }

        scaleX = 1;
        scaleY = 1;

        if (typeof Cropper === 'undefined') {
            console.warn('Cropper.js not loaded.');
            return;
        }

        cropperInstance = new Cropper(imageEl, {
            aspectRatio: isNaN(aspectRatio) ? NaN : aspectRatio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            responsive: true,
            crop: function (event) {
                if (dimInfo) {
                    const w = Math.round(event.detail.width);
                    const h = Math.round(event.detail.height);
                    dimInfo.textContent = `${w} × ${h} px`;
                }
            }
        });
    }

    // Load file from queue
    function loadFileIntoCropper(file) {
        if (!file || !file.type.startsWith('image/')) {
            proceedNextFile();
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            imageEl.src = e.target.result;
            openModal();

            if (fileQueue.length > 1) {
                queueBadge.textContent = `Image ${currentFileIndex + 1} of ${fileQueue.length}`;
                queueBadge.classList.remove('hidden');
            } else {
                queueBadge.classList.add('hidden');
            }

            if (subtitleEl) {
                const kb = Math.round(file.size / 1024);
                subtitleEl.textContent = `${file.name} (${kb} KB) — adjust crop boundary, rotation, and resolution`;
            }

            // Determine default aspect ratio based on input attributes or name
            let defaultRatio = 1; // Default square for products & categories
            const inputName = (currentInput?.name || '').toLowerCase();
            const inputId = (currentInput?.id || '').toLowerCase();
            const customRatioAttr = currentInput?.getAttribute('data-crop-aspect-ratio');

            if (customRatioAttr) {
                defaultRatio = customRatioAttr === 'free' ? NaN : parseFloat(customRatioAttr);
            } else if (inputName.includes('hero') || inputId.includes('hero') || inputName.includes('banner') || inputId.includes('banner')) {
                defaultRatio = 16 / 9;
            } else if (inputName.includes('logo') || inputId.includes('logo')) {
                defaultRatio = 3.75; // 300 / 80 = 3.75:1 store logo standard
            } else if (inputName.includes('meta') || inputId.includes('meta')) {
                defaultRatio = 1.9047; // 1200 / 630 ≈ 1.91:1 Meta image
            } else if (inputName.includes('favicon') || inputId.includes('favicon')) {
                defaultRatio = 1; // Favicon is square
            }

            // Update ratio button UI & init Cropper
            updateRatioButtonsUI(defaultRatio);
            initCropper(defaultRatio);
        };
        reader.readAsDataURL(file);
    }

    function updateRatioButtonsUI(activeRatio) {
        ratioBtns.forEach(btn => {
            const r = parseFloat(btn.dataset.ratio);
            const isMatch = (isNaN(activeRatio) && isNaN(r)) || (Math.abs(r - activeRatio) < 0.05);
            if (isMatch) {
                btn.classList.add('border-sage', 'bg-sage', 'text-white', 'shadow-xs');
                btn.classList.remove('border-ink/15', 'bg-white', 'text-ink');
            } else {
                btn.classList.remove('border-sage', 'bg-sage', 'text-white', 'shadow-xs');
                btn.classList.add('border-ink/15', 'bg-white', 'text-ink');
            }
        });
    }

    // Ratio Preset click
    ratioBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const ratio = parseFloat(this.dataset.ratio);
            updateRatioButtonsUI(ratio);
            if (cropperInstance) {
                cropperInstance.setAspectRatio(isNaN(ratio) ? NaN : ratio);
            }
        });
    });

    // Zoom
    document.getElementById('cropper-zoom-in')?.addEventListener('click', () => cropperInstance?.zoom(0.1));
    document.getElementById('cropper-zoom-out')?.addEventListener('click', () => cropperInstance?.zoom(-0.1));

    // Rotate
    document.getElementById('cropper-rotate-left')?.addEventListener('click', () => cropperInstance?.rotate(-90));
    document.getElementById('cropper-rotate-right')?.addEventListener('click', () => cropperInstance?.rotate(90));

    // Flip
    document.getElementById('cropper-flip-x')?.addEventListener('click', () => {
        scaleX = -scaleX;
        cropperInstance?.scaleX(scaleX);
    });
    document.getElementById('cropper-flip-y')?.addEventListener('click', () => {
        scaleY = -scaleY;
        cropperInstance?.scaleY(scaleY);
    });

    // Reset
    document.getElementById('cropper-reset')?.addEventListener('click', () => {
        cropperInstance?.reset();
        scaleX = 1;
        scaleY = 1;
    });

    // Finish processing all files and assign back to input
    function finishQueue() {
        if (!currentInput) {
            closeModal();
            return;
        }

        try {
            const dt = new DataTransfer();
            processedFiles.forEach(f => dt.items.add(f));

            currentInput._isCroppedEvent = true;
            currentInput.files = dt.files;

            // Dispatch change event to trigger form preview handlers with cropped files
            currentInput.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (err) {
            console.error('Error assigning cropped files:', err);
        }

        closeModal();
    }

    function proceedNextFile() {
        currentFileIndex++;
        if (currentFileIndex < fileQueue.length) {
            loadFileIntoCropper(fileQueue[currentFileIndex]);
        } else {
            finishQueue();
        }
    }

    // Apply & Crop clicked
    document.getElementById('cropper-apply-btn')?.addEventListener('click', function () {
        if (!cropperInstance) {
            if (fileQueue[currentFileIndex]) {
                processedFiles.push(fileQueue[currentFileIndex]);
            }
            proceedNextFile();
            return;
        }

        const originalFile = fileQueue[currentFileIndex];
        const maxSizeVal = maxSizeSelect?.value || '1200';

        let canvasOptions = {
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        };

        if (maxSizeVal !== 'original') {
            const maxDimension = parseInt(maxSizeVal, 10);
            const cropData = cropperInstance.getData();
            if (cropData.width > maxDimension || cropData.height > maxDimension) {
                if (cropData.width >= cropData.height) {
                    canvasOptions.width = maxDimension;
                } else {
                    canvasOptions.height = maxDimension;
                }
            }
        }

        const croppedCanvas = cropperInstance.getCroppedCanvas(canvasOptions);
        if (!croppedCanvas) {
            processedFiles.push(originalFile);
            proceedNextFile();
            return;
        }

        const mimeType = originalFile.type === 'image/png' ? 'image/png' : 'image/jpeg';
        const quality = 0.92;

        croppedCanvas.toBlob(function (blob) {
            if (blob) {
                const croppedFile = new File([blob], originalFile.name, {
                    type: mimeType,
                    lastModified: Date.now(),
                });
                processedFiles.push(croppedFile);
            } else {
                processedFiles.push(originalFile);
            }
            proceedNextFile();
        }, mimeType, quality);
    });

    // Use Original clicked
    document.getElementById('cropper-use-original-btn')?.addEventListener('click', function () {
        const originalFile = fileQueue[currentFileIndex];
        if (originalFile) {
            processedFiles.push(originalFile);
        }
        proceedNextFile();
    });

    // Cancel clicked
    const cancelModal = () => {
        if (currentInput) {
            currentInput.value = ''; // Reset input selection
        }
        closeModal();
    };
    document.getElementById('admin-cropper-cancel-btn')?.addEventListener('click', cancelModal);
    document.getElementById('cropper-cancel-action-btn')?.addEventListener('click', cancelModal);

    // Global listener for file inputs using capture phase so cropper intercepts BEFORE form handlers
    document.addEventListener('change', function (e) {
        const input = e.target;
        if (!input || input.type !== 'file') return;

        // Skip if this event was dispatched programmatically after cropping
        if (input._isCroppedEvent) {
            delete input._isCroppedEvent;
            return;
        }

        // Check if should crop: has image in accept, or has data-crop, or is an image input
        const accept = (input.getAttribute('accept') || '').toLowerCase();
        const name = (input.name || '').toLowerCase();
        const id = (input.id || '').toLowerCase();
        const hasCropAttr = input.hasAttribute('data-crop');
        const noCropAttr = input.hasAttribute('data-no-crop');

        const isImageInput = accept.includes('image') ||
            hasCropAttr ||
            name.includes('image') ||
            name.includes('logo') ||
            name.includes('favicon') ||
            name.includes('avatar') ||
            id.includes('image') ||
            id.includes('logo') ||
            id.includes('favicon');

        if (!isImageInput || noCropAttr) {
            return;
        }

        if (!input.files || input.files.length === 0) {
            return;
        }

        // Filter only image files
        const files = Array.from(input.files).filter(f => f.type.startsWith('image/'));
        if (files.length === 0) return;

        // Stop immediate propagation so form's uncropped preview handlers don't fire yet
        e.stopImmediatePropagation();

        currentInput = input;
        fileQueue = files;
        processedFiles = [];
        currentFileIndex = 0;

        loadFileIntoCropper(fileQueue[0]);
    }, true);
})();
</script>
