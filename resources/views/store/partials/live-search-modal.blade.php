{{-- Minimalist Spotlight Live Search Modal --}}
<div
    id="live-search-modal"
    aria-hidden="true"
    class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 opacity-0 transition-opacity duration-200"
>
    {{-- Blur Backdrop --}}
    <div
        id="live-search-backdrop"
        class="fixed inset-0 bg-black/40 backdrop-blur-md transition-opacity"
    ></div>

    {{-- Modal Card Container --}}
    <div
        id="live-search-container"
        class="relative w-full max-w-lg sm:max-w-xl bg-white rounded-3xl shadow-2xl p-4 sm:p-6 transition-all duration-200 scale-95 border border-slate-100 flex flex-col max-h-[85vh] z-10"
    >
        {{-- Search Input Bar --}}
        <div class="relative flex items-center rounded-2xl border border-slate-200/90 bg-white px-3.5 py-2.5 sm:py-3 transition focus-within:border-slate-400 focus-within:ring-2 focus-within:ring-slate-100">
            <svg class="h-5 w-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>

            <input
                type="text"
                id="live-search-input"
                placeholder="পণ্য বা ক্যাটাগরি খুঁজুন..."
                autocomplete="off"
                class="w-full bg-transparent px-3 text-sm sm:text-base font-normal text-slate-800 placeholder:text-slate-400 focus:outline-none"
            >

            {{-- Spinner (hidden by default) --}}
            <div id="live-search-spinner" class="hidden shrink-0 animate-spin mr-1.5">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>

            {{-- Close / Clear Button --}}
            <button
                type="button"
                id="live-search-close"
                class="p-1 text-slate-400 hover:text-slate-700 transition shrink-0"
                aria-label="অনুসন্ধান বন্ধ করুন"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Section Subheading --}}
        <div class="pt-4 pb-1.5 px-1">
            <span id="live-search-header-title" class="text-[10px] sm:text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                জনপ্রিয় পণ্যসমূহ
            </span>
        </div>

        {{-- Products List Container --}}
        <div id="live-search-items-container" class="space-y-1 max-h-[58vh] overflow-y-auto no-scrollbar -mx-1 px-1">
            {{-- Dynamically populated --}}
        </div>

        {{-- Empty State --}}
        <div id="live-search-empty-state" class="hidden py-8 text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-700">কোনো পণ্য পাওয়া যায়নি</p>
            <p class="text-xs text-slate-400 mt-1">ভিন্ন কোনো শব্দ বা নামে অনুসন্ধান করুন</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('live-search-modal');
        const container = document.getElementById('live-search-container');
        const backdrop = document.getElementById('live-search-backdrop');
        const input = document.getElementById('live-search-input');
        const spinner = document.getElementById('live-search-spinner');
        const closeBtn = document.getElementById('live-search-close');
        const headerTitle = document.getElementById('live-search-header-title');
        const itemsContainer = document.getElementById('live-search-items-container');
        const emptyState = document.getElementById('live-search-empty-state');

        let debounceTimer = null;
        let cachedPopularProducts = null;

        function renderProducts(products) {
            if (!products || products.length === 0) {
                itemsContainer.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }

            emptyState.classList.add('hidden');
            itemsContainer.innerHTML = products.map(p => `
                <a href="${p.url}" class="group flex items-center justify-between py-2 px-2.5 rounded-2xl hover:bg-slate-50 transition duration-150">
                    <div class="flex items-center gap-3 sm:gap-3.5 min-w-0">
                        <div class="h-11 w-11 sm:h-12 sm:w-12 shrink-0 overflow-hidden rounded-xl bg-slate-100 border border-slate-100/80">
                            ${p.image_url
                                ? `<img src="${p.image_url}" alt="${p.name}" class="h-full w-full object-cover rounded-xl transition duration-200 group-hover:scale-105">`
                                : `<div class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-400">AS</div>`
                            }
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-medium text-slate-800 group-hover:text-black transition truncate">
                                ${p.name}
                            </h4>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs font-bold text-primary">${p.price_formatted}</span>
                                ${p.compare_at_price_formatted ? `<span class="text-[11px] text-slate-400 line-through">${p.compare_at_price_formatted}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="pl-2 shrink-0">
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-slate-800 transition stroke-[1.75]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                        </svg>
                    </div>
                </a>
            `).join('');
        }

        function toBengaliNumber(num) {
            const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
            return String(num).replace(/[0-9]/g, d => bnDigits[d]);
        }

        function loadPopularSearches() {
            if (cachedPopularProducts) {
                headerTitle.textContent = 'জনপ্রিয় পণ্যসমূহ';
                renderProducts(cachedPopularProducts);
                return;
            }

            fetch(`{{ route('shop.search.live') }}`)
                .then(res => res.json())
                .then(data => {
                    cachedPopularProducts = data.products || [];
                    headerTitle.textContent = 'জনপ্রিয় পণ্যসমূহ';
                    renderProducts(cachedPopularProducts);
                })
                .catch(() => {});
        }

        function openSearchModal(initialQuery = '') {
            modal.classList.remove('pointer-events-none');
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
            document.body.style.overflow = 'hidden';

            if (initialQuery) {
                input.value = initialQuery;
                handleSearchInput();
            } else {
                input.value = '';
                loadPopularSearches();
            }

            setTimeout(() => {
                input.focus();
                if (initialQuery) {
                    input.select();
                }
            }, 80);
        }

        function closeSearchModal() {
            modal.classList.add('opacity-0');
            modal.classList.remove('opacity-100');
            container.classList.add('scale-95');
            container.classList.remove('scale-100');
            setTimeout(() => {
                modal.classList.add('pointer-events-none');
                document.body.style.overflow = '';
            }, 200);
        }

        const headerInput = document.getElementById('header-search-input');
        const headerPanel = document.getElementById('sb-search-panel');
        const headerTitleInline = document.getElementById('sb-search-title');
        const headerResults = document.getElementById('sb-search-results');
        const headerEmpty = document.getElementById('sb-search-empty');
        let inlineTimer = null;

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[char]));

        function renderInline(products) {
            if (!headerResults || !headerEmpty) return;
            if (!products || products.length === 0) {
                headerResults.innerHTML = '';
                headerEmpty.hidden = false;
                return;
            }
            headerEmpty.hidden = true;
            headerResults.innerHTML = products.map((product) => `
                <a class="sb-search__item" href="${escapeHtml(product.url)}">
                    ${product.image_url ? `<img src="${escapeHtml(product.image_url)}" alt="">` : '<span class="sb-search__thumb"></span>'}
                    <span>
                        <strong>${escapeHtml(product.name)}</strong>
                        <small>${escapeHtml(product.price_formatted || '')}</small>
                        ${product.compare_at_price_formatted ? `<del>${escapeHtml(product.compare_at_price_formatted)}</del>` : ''}
                    </span>
                </a>
            `).join('');
        }

        function openInline(initialQuery = '') {
            if (!headerPanel || !headerInput) return;
            headerPanel.hidden = false;
            if (initialQuery) {
                headerInput.value = initialQuery;
                searchInline();
                return;
            }
            if (headerInput.value.trim() === '') {
                if (headerTitleInline) headerTitleInline.textContent = 'জনপ্রিয় পণ্যসমূহ';
                if (cachedPopularProducts) {
                    renderInline(cachedPopularProducts);
                    return;
                }
                fetch(`{{ route('shop.search.live') }}`)
                    .then((res) => res.json())
                    .then((data) => {
                        cachedPopularProducts = data.products || [];
                        renderInline(cachedPopularProducts);
                    })
                    .catch(() => {});
            }
        }

        function closeInline() {
            if (headerPanel) headerPanel.hidden = true;
        }

        function inlineSearchVisible() {
            return !!headerInput && headerInput.offsetParent !== null;
        }

        function searchInline() {
            if (!headerInput) return;
            const query = headerInput.value.trim();
            clearTimeout(inlineTimer);
            if (query.length === 0) {
                if (headerTitleInline) headerTitleInline.textContent = 'জনপ্রিয় পণ্যসমূহ';
                renderInline(cachedPopularProducts || []);
                return;
            }
            inlineTimer = setTimeout(() => {
                fetch(`{{ route('shop.search.live') }}?q=${encodeURIComponent(query)}`)
                    .then((res) => res.json())
                    .then((data) => {
                        if (headerTitleInline) {
                            headerTitleInline.textContent = data.total_count > 0
                                ? `অনুসন্ধানের ফলাফল (${toBengaliNumber(data.total_count)})`
                                : 'অনুসন্ধানের ফলাফল';
                        }
                        renderInline(data.products || []);
                    })
                    .catch(() => {});
            }, 200);
        }

        headerInput?.addEventListener('focus', () => {
            if (headerInput.value.trim()) {
                headerPanel.hidden = false;
                searchInline();
                return;
            }
            openInline();
        });
        headerInput?.addEventListener('input', () => {
            openInline();
            searchInline();
        });
        headerInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeInline();
        });
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.sb-search-wrap')) closeInline();
        });

        // Attach to all elements with data-search-open
        document.querySelectorAll('[data-search-open]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const q = this.getAttribute('data-search-query') || this.dataset.searchQuery || '';
                if (inlineSearchVisible()) {
                    headerInput.focus();
                    openInline(q);
                    return;
                }
                openSearchModal(q);
            });
        });

        // Global keyboard shortcut: '/' or 'Ctrl+K'
        document.addEventListener('keydown', function (e) {
            if ((e.key === '/' || (e.key === 'k' && (e.metaKey || e.ctrlKey))) &&
                !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                if (inlineSearchVisible()) {
                    headerInput.focus();
                    openInline();
                    return;
                }
                openSearchModal();
            }
            if (e.key === 'Escape' && !modal.classList.contains('pointer-events-none')) {
                closeSearchModal();
            }
        });

        backdrop.addEventListener('click', closeSearchModal);

        closeBtn.addEventListener('click', function () {
            if (input.value.trim().length > 0) {
                input.value = '';
                input.focus();
                loadPopularSearches();
            } else {
                closeSearchModal();
            }
        });

        input.addEventListener('input', handleSearchInput);

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const term = input.value.trim();
                if (term) {
                    window.location.href = `{{ route('shop.index') }}?q=${encodeURIComponent(term)}`;
                }
            }
        });

        function handleSearchInput() {
            const query = input.value.trim();
            clearTimeout(debounceTimer);

            if (query.length === 0) {
                spinner.classList.add('hidden');
                emptyState.classList.add('hidden');
                headerTitle.textContent = 'জনপ্রিয় পণ্যসমূহ';
                renderProducts(cachedPopularProducts || []);
                return;
            }

            spinner.classList.remove('hidden');

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('shop.search.live') }}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        spinner.classList.add('hidden');
                        headerTitle.textContent = data.total_count > 0 ? `অনুসন্ধানের ফলাফল (${toBengaliNumber(data.total_count)})` : 'অনুসন্ধানের ফলাফল';
                        renderProducts(data.products || []);
                    })
                    .catch(() => {
                        spinner.classList.add('hidden');
                    });
            }, 200);
        }
    });
</script>
