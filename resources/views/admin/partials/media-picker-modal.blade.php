{{-- WordPress / WooCommerce Style Universal Media Picker Modal for Admin Panel --}}
<div
    id="admin-media-picker-modal"
    style="display: none; position: fixed; inset: 0; z-index: 9999990 !important; background-color: rgba(15, 23, 42, 0.72) !important; backdrop-filter: blur(4px) !important; -webkit-backdrop-filter: blur(4px) !important;"
    class="p-2 sm:p-4 md:p-6 items-center justify-center transition-opacity"
    role="dialog"
    aria-modal="true"
    aria-labelledby="media-modal-title"
>
    <div
        class="relative flex h-[92vh] max-h-[850px] w-full max-w-6xl flex-col rounded-lg bg-white shadow-2xl overflow-hidden border border-gray-300 font-sans my-auto"
        style="z-index: 9999995 !important;"
    >
        {{-- Modal Top Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 bg-white shrink-0">
            <h2 id="media-modal-title" class="text-base sm:text-lg font-semibold text-gray-800">Product image</h2>
            <button
                type="button"
                id="admin-media-picker-close-btn"
                class="text-gray-400 hover:text-gray-700 text-2xl font-light leading-none p-1 rounded transition cursor-pointer"
                title="Close dialog"
                aria-label="Close dialog"
            >
                &times;
            </button>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex items-center gap-6 px-4 bg-white border-b border-gray-200 shrink-0 text-xs sm:text-sm select-none">
            <button
                type="button"
                id="media-tab-btn-upload"
                class="py-2.5 font-medium border-b-2 transition cursor-pointer text-gray-500 border-transparent hover:text-gray-800"
            >
                Upload files
            </button>
            <button
                type="button"
                id="media-tab-btn-library"
                class="py-2.5 font-semibold border-b-2 transition cursor-pointer text-[#2271b1] border-[#2271b1]"
            >
                Media Library
            </button>
        </div>

        {{-- TAB VIEW 1: UPLOAD FILES --}}
        <div id="media-view-upload" class="hidden flex-1 flex flex-col items-center justify-center p-6 sm:p-12 bg-white overflow-y-auto">
            <div
                id="media-upload-dropzone"
                class="w-full max-w-2xl border-2 border-dashed border-gray-300 rounded-lg p-10 sm:p-14 text-center transition-colors hover:border-[#2271b1] hover:bg-blue-50/10 flex flex-col items-center justify-center cursor-pointer"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3 pointer-events-none">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                </div>
                <h3 class="text-xl sm:text-2xl font-normal text-gray-700 mb-1.5 pointer-events-none">Drop files to upload</h3>
                <p class="text-sm text-gray-400 mb-4 pointer-events-none">or</p>
                <label class="inline-flex items-center justify-center px-5 py-2 text-xs sm:text-sm font-medium text-[#2271b1] bg-white border border-[#2271b1] hover:bg-blue-50 rounded cursor-pointer transition shadow-2xs">
                    <span>Select Files</span>
                    <input type="file" id="media-tab-upload-input" multiple class="sr-only" accept="image/*,video/*">
                </label>
                <p class="text-xs text-gray-400 mt-6 pointer-events-none">Maximum upload file size: 50 MB.</p>
            </div>

            {{-- Upload Progress --}}
            <div id="media-tab-upload-progress" class="hidden mt-4 w-full max-w-2xl p-3 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-[#2271b1]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="media-tab-upload-text">Uploading files...</span>
                </span>
            </div>
        </div>

        {{-- TAB VIEW 2: MEDIA LIBRARY --}}
        <div id="media-view-library" class="flex-1 flex flex-col min-h-0 bg-white overflow-hidden">
            {{-- Toolbar: Filter & Search --}}
            <div class="border-b border-gray-200 bg-white px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs shrink-0">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Filter by Type --}}
                    <select id="media-filter-type" class="border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-700 bg-white focus:border-[#2271b1] focus:outline-none">
                        <option value="all">All media items</option>
                        <option value="image" selected>Images</option>
                        <option value="video">Video</option>
                        <option value="document">Documents</option>
                        <option value="other">Other</option>
                    </select>

                    {{-- Filter by Date / Folder --}}
                    <select id="media-filter-folder" class="border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-700 bg-white focus:border-[#2271b1] focus:outline-none">
                        <option value="all">All dates</option>
                    </select>
                </div>

                {{-- Search Media --}}
                <div class="relative w-52 sm:w-64">
                    <input
                        type="search"
                        id="media-search-input"
                        placeholder="Search media"
                        class="w-full border border-gray-300 rounded pl-2.5 pr-8 py-1.5 text-xs text-gray-700 focus:border-[#2271b1] focus:outline-none"
                    >
                    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>
            </div>

            {{-- 2-Column Area: Media Grid on Left, Attachment Details on Right --}}
            <div class="flex-1 flex overflow-hidden min-h-0">
                {{-- Column 1: Media Grid --}}
                <div class="flex-1 overflow-y-auto p-4 bg-gray-50/40 min-h-0" id="media-grid-scroll-container">
                    <div id="media-picker-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8 gap-2">
                        {{-- Rendered dynamically --}}
                    </div>

                    {{-- Empty State --}}
                    <div id="media-picker-empty" class="hidden flex-col items-center justify-center py-20 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-2">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-gray-700">No media found</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Try a different search term or upload a new file.</p>
                    </div>

                    {{-- Pagination / Load More --}}
                    <div id="media-picker-pagination" class="hidden mt-4 pt-3 flex items-center justify-center">
                        <button
                            type="button"
                            id="media-picker-load-more"
                            class="px-4 py-1.5 text-xs font-medium border border-gray-300 rounded bg-white hover:bg-gray-50 text-gray-700 shadow-2xs cursor-pointer transition"
                        >
                            Load More Media
                        </button>
                    </div>
                </div>

                {{-- Column 2: ATTACHMENT DETAILS Sidebar --}}
                <div class="w-72 sm:w-80 shrink-0 border-l border-gray-200 bg-gray-50/90 p-4 overflow-y-auto min-h-0 text-xs flex flex-col space-y-3.5 select-none" id="media-sidebar-wrapper">
                    <h4 class="font-bold uppercase tracking-wider text-[11px] text-gray-500">Attachment Details</h4>

                    {{-- Empty State (No item selected) --}}
                    <div id="media-sidebar-empty" class="flex flex-col items-center justify-center py-16 text-center text-gray-400">
                        <svg class="h-8 w-8 mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-xs">No media item selected</p>
                    </div>

                    {{-- Item Details Content --}}
                    <div id="media-sidebar-content" class="hidden space-y-3.5 select-text">
                        {{-- Top Thumbnail & Metadata --}}
                        <div class="flex gap-3 items-start">
                            <div class="w-20 h-20 shrink-0 bg-white border border-gray-200 rounded overflow-hidden flex items-center justify-center p-0.5 shadow-2xs">
                                <img id="wp-detail-thumb" src="" alt="" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="min-w-0 flex-1 leading-normal">
                                <p id="wp-detail-filename" class="font-bold text-gray-900 break-all text-xs leading-tight"></p>
                                <p id="wp-detail-date" class="text-gray-500 text-[11px] mt-1"></p>
                                <p id="wp-detail-size" class="text-gray-500 text-[11px]"></p>
                                <p id="wp-detail-dimensions" class="text-gray-500 text-[11px]"></p>
                                <button
                                    type="button"
                                    id="wp-detail-delete-btn"
                                    class="text-red-600 hover:text-red-700 hover:underline text-[11px] font-medium mt-1.5 inline-block text-left cursor-pointer"
                                >
                                    Delete permanently
                                </button>
                            </div>
                        </div>

                        <hr class="border-gray-200 my-1">

                        {{-- Editable Details Fields (WordPress style) --}}
                        <div>
                            <label class="block font-semibold text-gray-700 text-[11px] mb-1" for="wp-detail-alt">Alt Text</label>
                            <textarea
                                id="wp-detail-alt"
                                rows="2"
                                class="w-full text-xs rounded border border-gray-300 p-1.5 bg-white focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] resize-y"
                                placeholder="Alternative text"
                            ></textarea>
                            <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">Learn how to describe the purpose of the image. Leave empty if the image is purely decorative.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 text-[11px] mb-1" for="wp-detail-title">Title</label>
                            <input
                                type="text"
                                id="wp-detail-title"
                                class="w-full text-xs rounded border border-gray-300 p-1.5 bg-white focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]"
                            >
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 text-[11px] mb-1" for="wp-detail-caption">Image Caption</label>
                            <textarea
                                id="wp-detail-caption"
                                rows="2"
                                class="w-full text-xs rounded border border-gray-300 p-1.5 bg-white focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] resize-y"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 text-[11px] mb-1" for="wp-detail-desc">Description</label>
                            <textarea
                                id="wp-detail-desc"
                                rows="2"
                                class="w-full text-xs rounded border border-gray-300 p-1.5 bg-white focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] resize-y"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 text-[11px] mb-1" for="wp-detail-url">File URL:</label>
                            <input
                                type="text"
                                id="wp-detail-url"
                                readonly
                                class="w-full text-xs rounded border border-gray-300 bg-gray-100 p-1.5 text-gray-600 select-all font-mono"
                            >
                            <button
                                type="button"
                                id="wp-detail-copy-btn"
                                class="mt-1.5 inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium border border-gray-300 rounded bg-white hover:bg-gray-100 text-gray-700 shadow-2xs transition cursor-pointer"
                            >
                                <svg class="h-3 w-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                </svg>
                                <span id="wp-detail-copy-text">Copy URL to clipboard</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 text-xs">
                <span id="media-footer-summary" class="text-gray-500">No media selected</span>
                <button
                    type="button"
                    id="media-footer-clear-btn"
                    class="hidden text-red-600 hover:underline text-[11px] cursor-pointer"
                >
                    Clear selection
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="admin-media-picker-confirm-btn"
                    disabled
                    style="background-color: #2271b1 !important; color: #ffffff !important; border: 1px solid #135e96 !important;"
                    class="inline-flex items-center justify-center gap-1.5 px-6 py-2.5 text-xs font-semibold rounded shadow transition cursor-pointer text-white disabled:opacity-45 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span id="admin-media-picker-confirm-text">Set product image</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    // DOM Elements
    const modal = document.getElementById('admin-media-picker-modal');
    const modalTitle = document.getElementById('media-modal-title');
    const closeBtn = document.getElementById('admin-media-picker-close-btn');
    const confirmBtn = document.getElementById('admin-media-picker-confirm-btn');

    // Tabs
    const tabBtnUpload = document.getElementById('media-tab-btn-upload');
    const tabBtnLibrary = document.getElementById('media-tab-btn-library');
    const viewUpload = document.getElementById('media-view-upload');
    const viewLibrary = document.getElementById('media-view-library');

    // Upload elements
    const uploadDropzone = document.getElementById('media-upload-dropzone');
    const uploadFileInput = document.getElementById('media-tab-upload-input');
    const uploadProgress = document.getElementById('media-tab-upload-progress');
    const uploadProgressText = document.getElementById('media-tab-upload-text');

    // Library elements
    const typeSelect = document.getElementById('media-filter-type');
    const folderSelect = document.getElementById('media-filter-folder');
    const searchInput = document.getElementById('media-search-input');
    const grid = document.getElementById('media-picker-grid');
    const emptyState = document.getElementById('media-picker-empty');
    const paginationWrap = document.getElementById('media-picker-pagination');
    const loadMoreBtn = document.getElementById('media-picker-load-more');

    // Sidebar elements
    const sidebarEmpty = document.getElementById('media-sidebar-empty');
    const sidebarContent = document.getElementById('media-sidebar-content');
    const detailThumb = document.getElementById('wp-detail-thumb');
    const detailFilename = document.getElementById('wp-detail-filename');
    const detailDate = document.getElementById('wp-detail-date');
    const detailSize = document.getElementById('wp-detail-size');
    const detailDimensions = document.getElementById('wp-detail-dimensions');
    const detailDeleteBtn = document.getElementById('wp-detail-delete-btn');
    const detailAlt = document.getElementById('wp-detail-alt');
    const detailTitle = document.getElementById('wp-detail-title');
    const detailCaption = document.getElementById('wp-detail-caption');
    const detailDesc = document.getElementById('wp-detail-desc');
    const detailUrl = document.getElementById('wp-detail-url');
    const detailCopyBtn = document.getElementById('wp-detail-copy-btn');
    const detailCopyText = document.getElementById('wp-detail-copy-text');

    // Footer elements
    const footerSummary = document.getElementById('media-footer-summary');
    const footerClearBtn = document.getElementById('media-footer-clear-btn');

    // State
    let currentOptions = {
        multiple: false,
        filter: 'image',
        title: 'Product image',
        buttonText: 'Set product image',
        initialTab: 'library',
        onSelect: null,
    };

    let activeTab = 'library';
    let selectedItems = []; // Array of media file objects
    let loadedFiles = [];
    let activeDetailItem = null;
    let currentPage = 1;
    let lastPage = 1;
    let totalFiles = 0;
    let isLoading = false;
    let searchDebounce = null;

    const mediaApiUrl = '{{ route("admin.media.index") }}';
    const mediaStoreUrl = '{{ route("admin.media.store") }}';
    const mediaDestroyUrl = '{{ route("admin.media.destroy") }}';
    const csrfToken = '{{ csrf_token() }}';

    // Switch between "Upload files" and "Media Library" tabs
    function switchTab(tab) {
        activeTab = tab;
        if (tab === 'upload') {
            tabBtnUpload.className = 'py-2.5 font-semibold border-b-2 transition cursor-pointer text-[#2271b1] border-[#2271b1]';
            tabBtnLibrary.className = 'py-2.5 font-medium border-b-2 transition cursor-pointer text-gray-500 border-transparent hover:text-gray-800';
            viewUpload.classList.remove('hidden');
            viewLibrary.classList.add('hidden');
        } else {
            tabBtnLibrary.className = 'py-2.5 font-semibold border-b-2 transition cursor-pointer text-[#2271b1] border-[#2271b1]';
            tabBtnUpload.className = 'py-2.5 font-medium border-b-2 transition cursor-pointer text-gray-500 border-transparent hover:text-gray-800';
            viewLibrary.classList.remove('hidden');
            viewUpload.classList.add('hidden');
        }
    }

    tabBtnUpload?.addEventListener('click', () => switchTab('upload'));
    tabBtnLibrary?.addEventListener('click', () => switchTab('library'));

    // Open Modal API
    function openMediaPicker(options = {}) {
        currentOptions = {
            multiple: Boolean(options.multiple),
            filter: options.filter || 'image',
            title: options.title || (options.multiple ? 'Add Images to Gallery' : 'Product image'),
            buttonText: options.buttonText || (options.multiple ? 'Insert into gallery' : 'Set product image'),
            initialTab: options.initialTab || 'library',
            onSelect: options.onSelect || null,
        };

        if (modalTitle) {
            modalTitle.textContent = currentOptions.title;
        }

        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = '0.45';
            confirmBtn.style.cursor = 'not-allowed';
            const confirmTextEl = document.getElementById('admin-media-picker-confirm-text');
            if (confirmTextEl) {
                confirmTextEl.textContent = currentOptions.buttonText;
            } else {
                confirmBtn.textContent = currentOptions.buttonText;
            }
        }

        if (typeSelect) {
            typeSelect.value = currentOptions.filter === 'image' ? 'image' : (currentOptions.filter || 'all');
        }

        if (searchInput) searchInput.value = '';

        selectedItems = [];
        activeDetailItem = null;
        updateSidebar(null);
        updateFooterSummary();

        switchTab(currentOptions.initialTab);

        modal.style.display = 'flex';
        document.body.classList.add('overflow-hidden');

        fetchMedia(1, true);
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.classList.remove('overflow-hidden');
    }

    // Fetch media from server
    async function fetchMedia(page = 1, reset = false) {
        if (isLoading) return;
        isLoading = true;

        if (reset) {
            grid.innerHTML = `
                <div class="col-span-full py-16 flex items-center justify-center gap-2 text-gray-400 text-xs">
                    <svg class="animate-spin h-5 w-5 text-[#2271b1]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Loading media library...</span>
                </div>
            `;
            emptyState.classList.add('hidden');
            paginationWrap.classList.add('hidden');
        }

        const params = new URLSearchParams({
            page: page,
            q: searchInput ? searchInput.value.trim() : '',
            folder: folderSelect ? folderSelect.value : 'all',
            type: typeSelect ? typeSelect.value : 'image',
        });

        try {
            const res = await fetch(`${mediaApiUrl}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!res.ok) throw new Error('Failed to load media');

            const json = await res.json();
            currentPage = json.current_page || 1;
            lastPage = json.last_page || 1;
            totalFiles = json.total || 0;

            if (reset) {
                loadedFiles = json.data || [];
                grid.innerHTML = '';
                populateFolders(json.folders || []);
            } else {
                loadedFiles = loadedFiles.concat(json.data || []);
            }

            renderMediaCards(json.data || [], reset);

            if (currentPage < lastPage) {
                paginationWrap.classList.remove('hidden');
            } else {
                paginationWrap.classList.add('hidden');
            }

            if (loadedFiles.length === 0) {
                emptyState.classList.remove('hidden');
                grid.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                grid.classList.remove('hidden');
            }
        } catch (err) {
            console.error('Error fetching media:', err);
            grid.innerHTML = `<div class="col-span-full py-12 text-center text-xs text-red-600">Failed to load media items. Please try again.</div>`;
        } finally {
            isLoading = false;
        }
    }

    function populateFolders(folders) {
        if (!folderSelect || folderSelect.options.length > 1) return;
        folders.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = `Folder: ${f}`;
            folderSelect.appendChild(opt);
        });
    }

    // Render square cards matching the WordPress screenshot
    function renderMediaCards(items, reset) {
        if (reset) {
            grid.innerHTML = '';
        }

        items.forEach(item => {
            const isSelected = selectedItems.some(s => s.path === item.path);
            const card = document.createElement('div');

            // WordPress style thumbnail card
            card.className = `group relative aspect-square bg-gray-100 border border-gray-200 rounded cursor-pointer overflow-hidden select-none transition-all ${
                isSelected ? 'ring-4 ring-[#2271b1] ring-inset border-[#2271b1]' : 'hover:border-gray-400'
            }`;
            card.setAttribute('data-media-path', item.path);
            card.title = `${item.name}\n${item.size_formatted || ''} ${item.dimensions ? '· ' + item.dimensions : ''}`;

            const isImg = item.is_image;

            card.innerHTML = `
                <div class="h-full w-full flex items-center justify-center bg-gray-50 overflow-hidden">
                    ${isImg
                        ? `<img src="${item.url}" alt="${item.name}" loading="lazy" class="h-full w-full object-cover pointer-events-none">`
                        : `<div class="flex flex-col items-center justify-center p-2 text-center text-gray-500">
                             <svg class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                             </svg>
                             <span class="text-[9px] uppercase font-mono mt-1">${item.extension || 'FILE'}</span>
                           </div>`
                    }
                </div>

                {{-- WordPress Top-Right Checkmark Badge --}}
                <div class="wp-badge absolute top-0 right-0 w-6 h-6 bg-[#2271b1] text-white flex items-center justify-center shadow-xs transition ${isSelected ? '' : 'hidden'}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
            `;

            // Card Click Event
            card.addEventListener('click', () => {
                handleCardClick(item, card);
            });

            // Double Click for Single Selection immediate confirm
            if (!currentOptions.multiple) {
                card.addEventListener('dblclick', () => {
                    selectedItems = [item];
                    confirmSelection();
                });
            }

            grid.appendChild(card);
        });
    }

    // Card selection logic
    function handleCardClick(item, cardEl) {
        if (!currentOptions.multiple) {
            // Single select: only one item active
            selectedItems = [item];
            activeDetailItem = item;

            // Reset all cards' visual active state
            grid.querySelectorAll('[data-media-path]').forEach(el => {
                el.classList.remove('ring-4', 'ring-[#2271b1]', 'ring-inset', 'border-[#2271b1]');
                el.classList.add('hover:border-gray-400');
                const badge = el.querySelector('.wp-badge');
                if (badge) badge.classList.add('hidden');
            });

            // Set current card active
            cardEl.classList.add('ring-4', 'ring-[#2271b1]', 'ring-inset', 'border-[#2271b1]');
            cardEl.classList.remove('hover:border-gray-400');
            const badge = cardEl.querySelector('.wp-badge');
            if (badge) badge.classList.remove('hidden');

            updateSidebar(item);
        } else {
            // Multi select: toggle clicked item
            const existingIndex = selectedItems.findIndex(s => s.path === item.path);
            const badge = cardEl.querySelector('.wp-badge');

            if (existingIndex > -1) {
                selectedItems.splice(existingIndex, 1);
                cardEl.classList.remove('ring-4', 'ring-[#2271b1]', 'ring-inset', 'border-[#2271b1]');
                cardEl.classList.add('hover:border-gray-400');
                if (badge) badge.classList.add('hidden');

                if (selectedItems.length > 0) {
                    activeDetailItem = selectedItems[selectedItems.length - 1];
                    updateSidebar(activeDetailItem);
                } else {
                    activeDetailItem = null;
                    updateSidebar(null);
                }
            } else {
                selectedItems.push(item);
                activeDetailItem = item;
                cardEl.classList.add('ring-4', 'ring-[#2271b1]', 'ring-inset', 'border-[#2271b1]');
                cardEl.classList.remove('hover:border-gray-400');
                if (badge) badge.classList.remove('hidden');

                updateSidebar(item);
            }
        }

        updateFooterSummary();
    }

    // Update ATTACHMENT DETAILS right sidebar
    function updateSidebar(item) {
        if (!item) {
            sidebarEmpty.classList.remove('hidden');
            sidebarContent.classList.add('hidden');
            return;
        }

        sidebarEmpty.classList.add('hidden');
        sidebarContent.classList.remove('hidden');

        if (detailThumb) {
            detailThumb.src = item.url;
            detailThumb.alt = item.name;
        }

        if (detailFilename) {
            detailFilename.textContent = item.name;
        }

        if (detailDate) {
            detailDate.textContent = item.last_modified_formatted || 'Uploaded recently';
        }

        if (detailSize) {
            detailSize.textContent = item.size_formatted || '';
        }

        if (detailDimensions) {
            detailDimensions.textContent = item.dimensions || (item.is_image ? 'Image file' : item.extension || 'File');
        }

        // Fill editable metadata fields
        const titleWithoutExt = item.name.replace(/\.[^/.]+$/, '');
        if (detailTitle) detailTitle.value = titleWithoutExt;
        if (detailAlt) detailAlt.value = titleWithoutExt.replace(/[-_]/g, ' ');
        if (detailCaption) detailCaption.value = '';
        if (detailDesc) detailDesc.value = '';
        if (detailUrl) detailUrl.value = item.url;

        // Reset copy button
        if (detailCopyText) detailCopyText.textContent = 'Copy URL to clipboard';
    }

    // Copy URL to clipboard
    detailCopyBtn?.addEventListener('click', async () => {
        if (!detailUrl || !detailUrl.value) return;
        try {
            await navigator.clipboard.writeText(detailUrl.value);
            if (detailCopyText) detailCopyText.textContent = 'Copied to clipboard!';
            setTimeout(() => {
                if (detailCopyText) detailCopyText.textContent = 'Copy URL to clipboard';
            }, 2000);
        } catch (e) {
            detailUrl.select();
            document.execCommand('copy');
            if (detailCopyText) detailCopyText.textContent = 'Copied!';
            setTimeout(() => {
                if (detailCopyText) detailCopyText.textContent = 'Copy URL to clipboard';
            }, 2000);
        }
    });

    // Delete permanently from server
    detailDeleteBtn?.addEventListener('click', async () => {
        if (!activeDetailItem) return;

        const confirmMsg = `Are you sure you want to permanently delete "${activeDetailItem.name}" from storage? This cannot be undone.`;
        if (!confirm(confirmMsg)) return;

        const deletePath = activeDetailItem.path;
        detailDeleteBtn.disabled = true;
        detailDeleteBtn.textContent = 'Deleting...';

        try {
            const res = await fetch(mediaDestroyUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ path: deletePath })
            });

            const data = await res.json();
            if (data.ok) {
                // Remove card from DOM
                const card = grid.querySelector(`[data-media-path="${deletePath}"]`);
                if (card) card.remove();

                // Remove from selectedItems & loadedFiles
                selectedItems = selectedItems.filter(s => s.path !== deletePath);
                loadedFiles = loadedFiles.filter(s => s.path !== deletePath);

                activeDetailItem = null;
                updateSidebar(null);
                updateFooterSummary();

                if (window.showAdminToast) {
                    window.showAdminToast('File permanently deleted.', 'success');
                }
            } else {
                alert(data.message || 'Failed to delete file.');
            }
        } catch (err) {
            console.error('Delete error:', err);
            alert('Failed to delete file. Please try again.');
        } finally {
            detailDeleteBtn.disabled = false;
            detailDeleteBtn.textContent = 'Delete permanently';
        }
    });

    // Update bottom footer summary
    function updateFooterSummary() {
        const count = selectedItems.length;

        if (count === 0) {
            footerSummary.textContent = 'No media selected';
            footerClearBtn.classList.add('hidden');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = '0.45';
                confirmBtn.style.cursor = 'not-allowed';
            }
        } else if (count === 1) {
            footerSummary.innerHTML = `Selected: <strong class="text-gray-900 truncate font-semibold">${selectedItems[0].name}</strong>`;
            footerClearBtn.classList.remove('hidden');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor = 'pointer';
            }
        } else {
            footerSummary.innerHTML = `Selected: <strong class="text-[#2271b1] font-semibold">${count} items</strong>`;
            footerClearBtn.classList.remove('hidden');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor = 'pointer';
            }
        }
    }

    // Clear selection
    footerClearBtn?.addEventListener('click', () => {
        selectedItems = [];
        activeDetailItem = null;
        grid.querySelectorAll('[data-media-path]').forEach(el => {
            el.classList.remove('ring-4', 'ring-[#2271b1]', 'ring-inset', 'border-[#2271b1]');
            el.classList.add('hover:border-gray-400');
            const badge = el.querySelector('.wp-badge');
            if (badge) badge.classList.add('hidden');
        });
        updateSidebar(null);
        updateFooterSummary();
    });

    // Confirm selection and pass to caller callback
    function confirmSelection() {
        if (selectedItems.length === 0) return;

        if (typeof currentOptions.onSelect === 'function') {
            if (currentOptions.multiple) {
                currentOptions.onSelect(selectedItems);
            } else {
                currentOptions.onSelect(selectedItems[0]);
            }
        }

        closeModal();
    }

    confirmBtn?.addEventListener('click', confirmSelection);

    // Upload Files Handling (Tab 1)
    async function uploadFiles(files) {
        if (!files || files.length === 0) return;

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        formData.append('folder', folderSelect && folderSelect.value !== 'all' ? folderSelect.value : 'media');
        formData.append('_token', csrfToken);

        uploadProgress.classList.remove('hidden');
        uploadProgressText.textContent = `Uploading ${files.length} file(s)...`;

        try {
            const res = await fetch(mediaStoreUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await res.json();
            if (data.ok && data.files) {
                if (window.showAdminToast) {
                    window.showAdminToast(data.message || 'Files uploaded successfully.', 'success');
                }

                // Switch to "Media Library" tab
                switchTab('library');

                // Reload media list
                await fetchMedia(1, true);

                // Auto-select newly uploaded files
                if (data.files.length > 0) {
                    if (!currentOptions.multiple) {
                        const first = data.files[0];
                        const card = grid.querySelector(`[data-media-path="${first.path}"]`);
                        if (card) {
                            handleCardClick(first, card);
                        } else {
                            selectedItems = [first];
                            activeDetailItem = first;
                            updateSidebar(first);
                            updateFooterSummary();
                        }
                    } else {
                        data.files.forEach(f => {
                            const card = grid.querySelector(`[data-media-path="${f.path}"]`);
                            if (card) {
                                handleCardClick(f, card);
                            } else {
                                selectedItems.push(f);
                            }
                        });
                        activeDetailItem = data.files[data.files.length - 1];
                        updateSidebar(activeDetailItem);
                        updateFooterSummary();
                    }
                }
            } else {
                alert(data.message || 'Failed to upload files.');
            }
        } catch (err) {
            console.error('Upload error:', err);
            alert('Upload error. Please check file size and try again.');
        } finally {
            uploadProgress.classList.add('hidden');
            if (uploadFileInput) uploadFileInput.value = '';
        }
    }

    // Input change event
    uploadFileInput?.addEventListener('change', function () {
        uploadFiles(this.files);
    });

    // Drag and drop onto upload tab
    if (uploadDropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadDropzone.classList.add('border-[#2271b1]', 'bg-blue-50/20');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadDropzone.classList.remove('border-[#2271b1]', 'bg-blue-50/20');
            });
        });

        uploadDropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                uploadFiles(files);
            }
        });
    }

    // Drag and drop anywhere onto modal window
    modal.addEventListener('dragover', (e) => {
        e.preventDefault();
    });

    modal.addEventListener('drop', (e) => {
        // If dropped outside dropzone, still accept upload if on upload tab
        if (activeTab === 'upload' && !e.target.closest('#media-upload-dropzone')) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                uploadFiles(files);
            }
        }
    });

    // Toolbar filters and pagination
    closeBtn?.addEventListener('click', closeModal);

    loadMoreBtn?.addEventListener('click', () => {
        if (currentPage < lastPage) {
            fetchMedia(currentPage + 1, false);
        }
    });

    searchInput?.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            fetchMedia(1, true);
        }, 300);
    });

    folderSelect?.addEventListener('change', () => fetchMedia(1, true));
    typeSelect?.addEventListener('change', () => fetchMedia(1, true));

    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });

    // Expose global API
    window.openMediaPicker = openMediaPicker;
})();
</script>
