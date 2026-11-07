<script>
    (function () {
        function formatBytes(bytes, decimals = 1) {
            if (!+bytes) return '0 B';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        }

        function initDropzone(root) {
            if (root._dropzoneInitialized) return;
            root._dropzoneInitialized = true;

            const box = root.querySelector('[data-dropzone-box]');
            const input = root.querySelector('[data-dropzone-input]');
            const placeholder = root.querySelector('[data-dropzone-placeholder]');
            const previewWrap = root.querySelector('[data-dropzone-preview-wrap]');
            const previewImg = root.querySelector('[data-dropzone-img]');
            const previewVideo = root.querySelector('[data-dropzone-video]');
            const dragOverlay = root.querySelector('[data-dropzone-drag-overlay]');
            const infoBar = root.querySelector('[data-dropzone-info-bar]');
            const filenameEl = root.querySelector('[data-dropzone-filename]');
            const filesizeEl = root.querySelector('[data-dropzone-filesize]');
            const clearBtn = root.querySelector('[data-dropzone-clear-btn]');
            const removeInput = root.querySelector('[data-dropzone-remove-input]');
            const mediaInput = root.querySelector('[data-dropzone-media-input]');
            const mediaBtns = root.querySelectorAll('[data-dropzone-media-btn]');

            if (!box || !input) return;

            function applyMediaSelection(item) {
                if (!item) return;

                const isVideoFile = item.is_video;
                if (isVideoFile && previewVideo) {
                    previewVideo.src = item.url;
                    previewVideo.classList.remove('hidden');
                    if (previewImg) previewImg.classList.add('hidden');
                } else if (previewImg) {
                    previewImg.src = item.url;
                    previewImg.classList.remove('hidden');
                    if (previewVideo) previewVideo.classList.add('hidden');
                }

                if (previewWrap) previewWrap.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');

                // Clear local file input so form submits the media path
                input.value = '';
                if (mediaInput) mediaInput.value = item.path;

                if (infoBar) {
                    infoBar.classList.remove('hidden');
                    infoBar.classList.add('flex');
                }
                if (filenameEl) filenameEl.textContent = item.name + ' (Media)';
                if (filesizeEl) filesizeEl.textContent = item.size_formatted || '';

                if (removeInput) removeInput.value = '0';
            }

            // Trigger Media Picker when "Select from Media" button is clicked
            mediaBtns.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (typeof window.openMediaPicker === 'function') {
                        window.openMediaPicker({
                            multiple: false,
                            initialTab: 'library',
                            filter: previewVideo ? 'all' : 'image',
                            title: 'Select Image',
                            buttonText: 'Set Image',
                            onSelect: applyMediaSelection
                        });
                    }
                });
            });

            // Trigger Media Modal on box click (skip for native file uploads e.g. ZIP packages)
            const useNativeFile = root.getAttribute('data-dropzone-native') === '1';
            box.addEventListener('click', function (e) {
                if (e.target.closest('[data-dropzone-clear-btn]') || e.target.closest('[data-dropzone-media-btn]')) return;

                if (! useNativeFile && typeof window.openMediaPicker === 'function') {
                    window.openMediaPicker({
                        multiple: false,
                        initialTab: 'library',
                        filter: previewVideo ? 'all' : 'image',
                        title: 'Select Image',
                        buttonText: 'Set Image',
                        onSelect: applyMediaSelection
                    });
                } else {
                    input.click();
                }
            });

            // Keyboard accessibility
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    input.click();
                }
            });

            // Drag and Drop Events
            let dragCounter = 0;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                box.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            box.addEventListener('dragenter', function () {
                dragCounter++;
                if (dragOverlay) dragOverlay.classList.remove('hidden');
                box.classList.add('border-sage', 'bg-sage/5');
            });

            box.addEventListener('dragleave', function () {
                dragCounter--;
                if (dragCounter <= 0) {
                    dragCounter = 0;
                    if (dragOverlay) dragOverlay.classList.add('hidden');
                    box.classList.remove('border-sage', 'bg-sage/5');
                }
            });

            box.addEventListener('drop', function (e) {
                dragCounter = 0;
                if (dragOverlay) dragOverlay.classList.add('hidden');
                box.classList.remove('border-sage', 'bg-sage/5');

                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            function optimizeImageFile(origFile, onReady) {
                if (!origFile || !origFile.type.startsWith('image/') || origFile.type === 'image/svg+xml' || origFile.size <= 1.2 * 1024 * 1024) {
                    onReady(origFile, false);
                    return;
                }

                const img = new Image();
                const tempUrl = URL.createObjectURL(origFile);
                img.onload = function () {
                    URL.revokeObjectURL(tempUrl);
                    try {
                        const canvas = document.createElement('canvas');
                        let width = img.naturalWidth || img.width;
                        let height = img.naturalHeight || img.height;

                        const maxDim = 1200;
                        if (width > maxDim || height > maxDim) {
                            if (width > height) {
                                height = Math.round((height * maxDim) / width);
                                width = maxDim;
                            } else {
                                width = Math.round((width * maxDim) / height);
                                height = maxDim;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, width, height);
                        ctx.drawImage(img, 0, 0, width, height);

                        const isPng = origFile.type === 'image/png';
                        const mimeType = isPng ? 'image/png' : 'image/jpeg';
                        const quality = isPng ? undefined : 0.88;

                        canvas.toBlob(function (blob) {
                            if (blob && blob.size < origFile.size) {
                                const optimizedFile = new File([blob], origFile.name, {
                                    type: blob.type,
                                    lastModified: Date.now()
                                });
                                onReady(optimizedFile, true);
                            } else {
                                onReady(origFile, false);
                            }
                        }, mimeType, quality);
                    } catch (e) {
                        onReady(origFile, false);
                    }
                };
                img.onerror = function () {
                    URL.revokeObjectURL(tempUrl);
                    onReady(origFile, false);
                };
                img.src = tempUrl;
            }

            function renderFileUI(file, originalSize) {
                const isVideoFile = file.type.startsWith('video/');
                const isImageFile = file.type.startsWith('image/');

                if (isVideoFile && previewVideo) {
                    const objectUrl = URL.createObjectURL(file);
                    previewVideo.src = objectUrl;
                    previewVideo.classList.remove('hidden');
                    if (previewImg) previewImg.classList.add('hidden');
                    if (previewWrap) previewWrap.classList.remove('hidden');
                    if (placeholder) placeholder.classList.add('hidden');
                } else if (isImageFile && previewImg) {
                    const objectUrl = URL.createObjectURL(file);
                    previewImg.src = objectUrl;
                    previewImg.classList.remove('hidden');
                    if (previewVideo) previewVideo.classList.add('hidden');
                    if (previewWrap) previewWrap.classList.remove('hidden');
                    if (placeholder) placeholder.classList.add('hidden');
                } else {
                    // Non-media files (ZIP, PDF, etc.): keep placeholder, show filename bar only
                    if (previewWrap) previewWrap.classList.add('hidden');
                    if (placeholder) placeholder.classList.remove('hidden');
                }

                if (infoBar) {
                    infoBar.classList.remove('hidden');
                    infoBar.classList.add('flex');
                }
                if (filenameEl) filenameEl.textContent = file.name;
                if (filesizeEl) {
                    if (originalSize && originalSize > file.size) {
                        filesizeEl.textContent = formatBytes(file.size) + ' (Optimized from ' + formatBytes(originalSize) + ')';
                    } else {
                        filesizeEl.textContent = formatBytes(file.size);
                    }
                }

                if (mediaInput) mediaInput.value = '';
                if (removeInput) removeInput.value = '0';
            }

            // Handle file selection and live preview
            input.addEventListener('change', function () {
                if (input._isProcessingOptimization) return;

                const file = input.files && input.files[0];
                if (!file) return;

                const isImageFile = file.type.startsWith('image/');

                // If image exceeds 1.2 MB, auto-optimize in browser to prevent webserver max_upload_size limits
                if (isImageFile && file.size > 1.2 * 1024 * 1024) {
                    if (infoBar) {
                        infoBar.classList.remove('hidden');
                        infoBar.classList.add('flex');
                    }
                    if (filenameEl) filenameEl.textContent = file.name;
                    if (filesizeEl) filesizeEl.textContent = 'Optimizing ' + formatBytes(file.size) + '...';

                    optimizeImageFile(file, function (processedFile, wasOptimized) {
                        if (wasOptimized) {
                            try {
                                const dt = new DataTransfer();
                                dt.items.add(processedFile);
                                input._isProcessingOptimization = true;
                                input.files = dt.files;
                                input._isProcessingOptimization = false;
                            } catch (e) {}
                        }
                        renderFileUI(processedFile, wasOptimized ? file.size : null);
                    });
                    return;
                }

                renderFileUI(file, null);
            });

            // Handle remove / clear
            if (clearBtn) {
                clearBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    input.value = '';
                    if (mediaInput) mediaInput.value = '';
                    if (previewImg) previewImg.src = '';
                    if (previewVideo) previewVideo.src = '';
                    if (previewWrap) previewWrap.classList.add('hidden');
                    if (placeholder) placeholder.classList.remove('hidden');

                    if (infoBar) {
                        infoBar.classList.add('hidden');
                        infoBar.classList.remove('flex');
                    }
                    if (filenameEl) filenameEl.textContent = '';
                    if (filesizeEl) filesizeEl.textContent = '';

                    if (removeInput) removeInput.value = '1';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-dropzone-root]').forEach(initDropzone);
        });

        // Expose function for dynamically loaded modals or rows
        window.initAdminDropzones = function (context) {
            const scope = context || document;
            scope.querySelectorAll('[data-dropzone-root]').forEach(initDropzone);
        };
    })();
</script>
