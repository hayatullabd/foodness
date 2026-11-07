@if(app(\App\Services\GeminiService::class)->isContentGenerationEnabled())
{{-- Gemini AI Copywriter Studio Modal --}}
<div
    id="gemini-ai-modal"
    class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/70 backdrop-blur-xs p-3 sm:p-6 overflow-y-auto"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="gemini-modal-title"
>
    <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[92vh] my-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-indigo-700 via-indigo-800 to-purple-800 px-5 py-3.5 text-white">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-yellow-300 ring-1 ring-white/30">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </span>
                <div>
                    <h3 id="gemini-modal-title" class="text-sm font-bold leading-tight">Gemini AI Copywriter Studio</h3>
                    <p class="text-[11px] text-indigo-200 font-medium">Custom prompt • Live preview • Apply to product in one click</p>
                </div>
            </div>
            <button
                type="button"
                onclick="window.closeGeminiAiModal()"
                class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 hover:text-white transition cursor-pointer"
                aria-label="Close modal"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body: 2 Columns on Desktop --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 flex-1 overflow-y-auto divide-y lg:divide-y-0 lg:divide-x divide-slate-200">
            {{-- Left Column: Prompt Controls (5 cols) --}}
            <div class="lg:col-span-5 p-4 sm:p-5 space-y-3.5 bg-slate-50/70 overflow-y-auto">
                {{-- Product Name Field --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1" for="modal-ai-product-name">
                        Product Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="modal-ai-product-name"
                        type="text"
                        class="field !text-xs !py-2 w-full bg-white font-medium"
                        placeholder="e.g. Men's Premium Cotton Panjabi"
                    />
                </div>

                {{-- Category Context --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1" for="modal-ai-category">
                        Category Context
                    </label>
                    <input
                        id="modal-ai-category"
                        type="text"
                        class="field !text-xs !py-2 w-full bg-white"
                        placeholder="e.g. Panjabi / Mens Fashion"
                    />
                </div>

                {{-- Custom Prompt / Instructions --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1" for="modal-ai-custom-prompt">
                        Custom Instructions & Prompt (Optional)
                    </label>
                    <textarea
                        id="modal-ai-custom-prompt"
                        rows="3"
                        class="field !text-xs !py-2 !px-3 w-full bg-white shadow-xs placeholder:text-slate-400 focus:bg-white"
                        placeholder="e.g. 100% pure organic cotton, delicate embroidery, comfortable fit, festive season, 20% discount & wash care instructions..."
                    ></textarea>

                    {{-- Quick Prompt Chips --}}
                    <div class="mt-2 flex flex-wrap gap-1">
                        <button type="button" onclick="window.appendModalPromptChip('100% pure cotton fabric, maximum breathable comfort')" class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/80 hover:bg-indigo-50 transition cursor-pointer">+ 100% Cotton</button>
                        <button type="button" onclick="window.appendModalPromptChip('Luxury and premium finish, royal aesthetic')" class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/80 hover:bg-indigo-50 transition cursor-pointer">+ Premium Look</button>
                        <button type="button" onclick="window.appendModalPromptChip('Perfect for Eid, festivals and special events')" class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/80 hover:bg-indigo-50 transition cursor-pointer">+ Festive Collection</button>
                        <button type="button" onclick="window.appendModalPromptChip('Include detailed wash care and fabric care instructions')" class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/80 hover:bg-indigo-50 transition cursor-pointer">+ Wash Care</button>
                        <button type="button" onclick="window.appendModalPromptChip('Write persuasive, high-converting product descriptions')" class="rounded-md bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/80 hover:bg-indigo-50 transition cursor-pointer">+ Persuasive Copy</button>
                    </div>
                </div>

                {{-- Action Generate Button --}}
                <div class="pt-1">
                    <button
                        type="button"
                        id="btn-modal-generate-ai"
                        onclick="window.executeGeminiModalGenerate()"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 px-4 text-xs font-bold text-white shadow-md transition active:scale-98 cursor-pointer hover:opacity-95 disabled:opacity-50"
                        style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff !important;"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span id="btn-modal-generate-text">✨ Generate Content with AI</span>
                    </button>
                </div>
            </div>

            {{-- Right Column: Preview Studio (7 cols) --}}
            <div class="lg:col-span-7 flex flex-col bg-white overflow-hidden">
                {{-- Preview Tabs --}}
                <div class="flex items-center border-b border-slate-200 bg-slate-50/80 px-4 pt-2 gap-1 shrink-0 text-xs font-bold">
                    <button
                        type="button"
                        onclick="window.switchAiStudioTab('full')"
                        id="tab-btn-full"
                        class="px-3.5 py-2 border-b-2 border-indigo-600 text-indigo-700 transition cursor-pointer"
                    >
                        📝 Full Description
                    </button>
                    <button
                        type="button"
                        onclick="window.switchAiStudioTab('short')"
                        id="tab-btn-short"
                        class="px-3.5 py-2 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition cursor-pointer"
                    >
                        📌 Short Description
                    </button>
                    <button
                        type="button"
                        onclick="window.switchAiStudioTab('seo')"
                        id="tab-btn-seo"
                        class="px-3.5 py-2 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition cursor-pointer"
                    >
                        🔍 SEO Metadata
                    </button>
                </div>

                {{-- Tab Content Area --}}
                <div class="p-4 sm:p-5 flex-1 overflow-y-auto min-h-[300px]">
                    {{-- Empty State (Before Generation) --}}
                    <div id="ai-studio-empty" class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-3 ring-8 ring-indigo-50/50">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700 mb-1">Waiting for Preview</h4>
                        <p class="text-[11px] text-slate-500 max-w-xs leading-relaxed">
                            Enter product details and prompt on the left, then click <strong>"Generate Content"</strong> to preview AI-crafted copy here.
                        </p>
                    </div>

                    {{-- Tab: Full Description --}}
                    <div id="tab-content-full" class="hidden space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 border-b pb-1.5 mb-2">
                            <span class="font-bold text-slate-700">WYSIWYG Rich HTML Description:</span>
                            <span class="text-[10px] bg-slate-100 px-2 py-0.5 rounded text-slate-600">Editable before apply</span>
                        </div>
                        <div id="ai-preview-full-html" class="prose prose-sm max-w-none text-slate-800 text-xs sm:text-sm leading-relaxed p-3 rounded-lg bg-slate-50 border border-slate-200 min-h-[220px] focus:outline-none focus:bg-white" contenteditable="true">
                        </div>
                    </div>

                    {{-- Tab: Short Description --}}
                    <div id="tab-content-short" class="hidden space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 border-b pb-1.5 mb-2">
                            <span class="font-bold text-slate-700">Product Intro Hook:</span>
                        </div>
                        <textarea id="ai-preview-short-text" rows="5" class="field !text-xs !py-2.5 !px-3 w-full bg-slate-50 leading-relaxed"></textarea>
                    </div>

                    {{-- Tab: SEO & Google Snippet --}}
                    <div id="tab-content-seo" class="hidden space-y-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 space-y-1">
                            <p class="text-[10px] font-semibold text-slate-400">GOOGLE SEARCH PREVIEW</p>
                            <h4 id="ai-preview-seo-title" class="text-sm font-semibold text-blue-700 hover:underline cursor-pointer truncate">Product Title Preview</h4>
                            <p id="ai-preview-seo-desc" class="text-xs text-slate-600 line-clamp-2 leading-relaxed">Meta description snippet preview will appear here.</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1" for="ai-preview-meta-title-input">Meta Title</label>
                            <input id="ai-preview-meta-title-input" type="text" class="field !text-xs !py-1.5 w-full bg-white" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1" for="ai-preview-meta-desc-input">Meta Description</label>
                            <textarea id="ai-preview-meta-desc-input" rows="3" class="field !text-xs !py-1.5 w-full bg-white"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50/90 px-4 py-3 shrink-0">
                    <button
                        type="button"
                        onclick="window.closeGeminiAiModal()"
                        class="rounded-lg px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="btn-modal-apply-ai"
                            onclick="window.executeGeminiModalApply()"
                            disabled
                            class="flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold text-white shadow-sm transition active:scale-95 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                            style="background: #059669; color: #ffffff !important;"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Apply to Product</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    window.geminiStudioCurrentData = null;

    window.openGeminiAiModal = function() {
        var modal = document.getElementById('gemini-ai-modal');
        if (!modal) {
            alert('AI Modal could not be found.');
            return;
        }

        // Teleport to document.body to escape any transformed or overflow containers
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        var nameInput = document.getElementById('name');
        var modalName = document.getElementById('modal-ai-product-name');
        if (modalName && nameInput) {
            modalName.value = nameInput.value.trim();
        }

        var catSelect = document.getElementById('category_id');
        var modalCat = document.getElementById('modal-ai-category');
        if (modalCat && catSelect && catSelect.selectedIndex > 0) {
            modalCat.value = catSelect.options[catSelect.selectedIndex].text;
        }

        modal.style.display = 'flex';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        var promptArea = document.getElementById('modal-ai-custom-prompt');
        if (nameInput && !nameInput.value.trim() && modalName) {
            modalName.focus();
        } else if (promptArea) {
            promptArea.focus();
        }
    };

    window.closeGeminiAiModal = function() {
        var modal = document.getElementById('gemini-ai-modal');
        if (!modal) return;
        modal.style.display = 'none';
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.appendModalPromptChip = function(text) {
        var promptArea = document.getElementById('modal-ai-custom-prompt');
        if (promptArea && text) {
            var cur = promptArea.value.trim();
            promptArea.value = cur ? cur + ', ' + text : text;
            promptArea.focus();
        }
    };

    window.switchAiStudioTab = function(tabName) {
        var tabs = ['full', 'short', 'seo'];
        tabs.forEach(function(t) {
            var btn = document.getElementById('tab-btn-' + t);
            var content = document.getElementById('tab-content-' + t);
            if (!btn || !content) return;
            if (t === tabName) {
                btn.className = 'px-3.5 py-2 border-b-2 border-indigo-600 text-indigo-700 font-bold transition cursor-pointer';
                content.classList.remove('hidden');
            } else {
                btn.className = 'px-3.5 py-2 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition cursor-pointer';
                content.classList.add('hidden');
            }
        });
    };

    window.executeGeminiModalGenerate = async function() {
        var modalName = document.getElementById('modal-ai-product-name');
        var nameInput = document.getElementById('name');
        var prodName = (modalName && modalName.value.trim()) || (nameInput && nameInput.value.trim()) || '';

        if (!prodName) {
            alert('Please enter a Product Name first so Gemini AI knows what to write.');
            if (modalName) modalName.focus();
            return;
        }

        if (nameInput && !nameInput.value.trim()) {
            nameInput.value = prodName;
            nameInput.dispatchEvent(new Event('input'));
        }

        var modalCat = document.getElementById('modal-ai-category');
        var catText = (modalCat && modalCat.value.trim()) || '';

        var modalPrompt = document.getElementById('modal-ai-custom-prompt');
        var promptVal = (modalPrompt && modalPrompt.value.trim()) || '';

        var btnGen = document.getElementById('btn-modal-generate-ai');
        var btnGenText = document.getElementById('btn-modal-generate-text');

        if (btnGen) btnGen.disabled = true;
        if (btnGenText) btnGenText.textContent = 'Generating with Gemini AI...';

        try {
            var response = await fetch('{{ route("admin.products.generate-ai") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: prodName,
                    category: catText,
                    prompt: promptVal,
                    notes: promptVal
                })
            });

            var res = await response.json();

            if (!response.ok || !res.ok) {
                alert(res.message || 'Unable to generate content. Please check your Gemini settings.');
                return;
            }

            var data = res.data;
            window.geminiStudioCurrentData = data;

            var emptyState = document.getElementById('ai-studio-empty');
            if (emptyState) emptyState.classList.add('hidden');

            var previewFull = document.getElementById('ai-preview-full-html');
            if (previewFull) previewFull.innerHTML = data.description || '';

            var previewShort = document.getElementById('ai-preview-short-text');
            if (previewShort) previewShort.value = data.short_description || '';

            var seoTitleInput = document.getElementById('ai-preview-meta-title-input');
            var seoTitlePreview = document.getElementById('ai-preview-seo-title');
            if (seoTitleInput) seoTitleInput.value = data.meta_title || '';
            if (seoTitlePreview) seoTitlePreview.textContent = data.meta_title || 'Product Title Preview';

            var seoDescInput = document.getElementById('ai-preview-meta-desc-input');
            var seoDescPreview = document.getElementById('ai-preview-seo-desc');
            if (seoDescInput) seoDescInput.value = data.meta_description || '';
            if (seoDescPreview) seoDescPreview.textContent = data.meta_description || 'Meta description snippet preview will appear here.';

            var btnApply = document.getElementById('btn-modal-apply-ai');
            if (btnApply) {
                btnApply.disabled = false;
                btnApply.classList.remove('opacity-40', 'cursor-not-allowed');
            }

            window.switchAiStudioTab('full');
            if (btnGenText) btnGenText.textContent = '🔄 Regenerate Content';

        } catch (err) {
            console.error('Gemini AI modal error:', err);
            alert('Network or server error while generating content.');
        } finally {
            if (btnGen) btnGen.disabled = false;
            if (btnGenText && btnGenText.textContent.includes('Generating')) {
                btnGenText.textContent = '✨ Generate Content with AI';
            }
        }
    };

    window.executeGeminiModalApply = function() {
        var previewFull = document.getElementById('ai-preview-full-html');
        var previewShort = document.getElementById('ai-preview-short-text');
        var seoTitleInput = document.getElementById('ai-preview-meta-title-input');
        var seoDescInput = document.getElementById('ai-preview-meta-desc-input');

        var data = window.geminiStudioCurrentData || {};
        var finalFull = (previewFull && previewFull.innerHTML) || data.description || '';
        var finalShort = (previewShort && previewShort.value) || data.short_description || '';
        var finalMetaTitle = (seoTitleInput && seoTitleInput.value) || data.meta_title || '';
        var finalMetaDesc = (seoDescInput && seoDescInput.value) || data.meta_description || '';

        // 1. Short Description
        var shortDesc = document.getElementById('short_description');
        if (shortDesc) {
            shortDesc.value = finalShort;
            shortDesc.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // 2. Full Description (both textarea and Summernote)
        var descArea = document.getElementById('description');
        if (descArea) {
            descArea.value = finalFull;
            if (window.jQuery && typeof window.jQuery('#description').summernote === 'function') {
                window.jQuery('#description').summernote('code', finalFull);
            }
            descArea.dispatchEvent(new Event('input', { bubbles: true }));
            descArea.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // 3. Meta Title
        var metaTitle = document.getElementById('meta_title');
        if (metaTitle) {
            metaTitle.value = finalMetaTitle;
            metaTitle.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // 4. Meta Description
        var metaDesc = document.getElementById('meta_description');
        if (metaDesc) {
            metaDesc.value = finalMetaDesc;
            metaDesc.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Close modal
        window.closeGeminiAiModal();

        // Smooth scroll to Description
        if (descArea) {
            descArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    function initAiModal() {
        var modal = document.getElementById('gemini-ai-modal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) window.closeGeminiAiModal();
            });
        }

        var seoTitleInput = document.getElementById('ai-preview-meta-title-input');
        var seoDescInput = document.getElementById('ai-preview-meta-desc-input');
        var seoTitlePreview = document.getElementById('ai-preview-seo-title');
        var seoDescPreview = document.getElementById('ai-preview-seo-desc');

        if (seoTitleInput && seoTitlePreview) {
            seoTitleInput.addEventListener('input', function() {
                seoTitlePreview.textContent = seoTitleInput.value.trim() || 'Product Title Preview';
            });
        }
        if (seoDescInput && seoDescPreview) {
            seoDescInput.addEventListener('input', function() {
                seoDescPreview.textContent = seoDescInput.value.trim() || 'Meta description snippet preview will appear here.';
            });
        }

        var modalNameInput = document.getElementById('modal-ai-product-name');
        if (modalNameInput) {
            modalNameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var promptInput = document.getElementById('modal-ai-custom-prompt');
                    if (promptInput) promptInput.focus();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAiModal);
    } else {
        initAiModal();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal = document.getElementById('gemini-ai-modal');
            if (modal && modal.style.display !== 'none' && !modal.classList.contains('hidden')) {
                window.closeGeminiAiModal();
            }
        }
    });
})();
</script>
@endif
