{{-- Variation / Quick Select Modal --}}
<div
    id="variation-modal"
    class="fixed inset-0 z-50 hidden transition-opacity duration-300"
    data-variation-modal
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" data-variation-backdrop></div>

    {{-- Modal Dialog (Centered) --}}
    <div class="fixed inset-0 z-10 flex items-center justify-center p-3 sm:p-6 pointer-events-none">
        <div
            class="pointer-events-auto relative w-full max-h-[92vh] sm:max-h-[85vh] sm:max-w-lg rounded-2xl sm:rounded-3xl bg-white shadow-2xl flex flex-col overflow-hidden transition-all duration-300 transform scale-95 opacity-0"
            data-variation-panel
        >
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-[#013F1E]/10 px-5 py-4">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#013F1E]/10 text-[#013F1E]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </span>
                    <h3 class="font-serif text-base font-extrabold text-[#013F1E]">অপশন বাছাই করুন</h3>
                </div>
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-muted hover:bg-mist hover:text-ink transition cursor-pointer"
                    data-variation-close
                    aria-label="বন্ধ করুন"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5 no-scrollbar">
                {{-- Product Mini Header --}}
                <div class="flex items-center gap-3.5 rounded-2xl bg-[#FAFBF9] p-3 border border-[#013F1E]/10">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-white border border-ink/10 flex items-center justify-center">
                        <img src="" alt="" data-var-img class="h-full w-full object-cover hidden">
                        <div data-var-img-fallback class="font-serif text-xl font-black text-ink/20">A</div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p data-var-category class="text-[10px] font-bold uppercase tracking-wider text-[#013F1E]">Foodness</p>
                        <h4 data-var-name class="font-serif text-sm font-bold text-ink truncate leading-snug">Product Name</h4>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span data-var-price class="font-sans text-base font-black text-[#013F1E]">৳০</span>
                            <span data-var-compare-price class="text-xs text-muted line-through hidden">৳০</span>
                            <span data-var-stock-badge class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full ring-1 ring-emerald-600/20">
                                স্টকে আছে
                            </span>
                            <span data-var-delivery-badge class="hidden text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full ring-1 ring-emerald-600/20"></span>
                        </div>
                    </div>
                </div>

                {{-- Loading Skeleton --}}
                <div data-var-loading class="py-8 text-center hidden">
                    <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-[#013F1E] border-t-transparent"></div>
                    <p class="mt-2 text-xs font-bold text-muted">অপশন লোড হচ্ছে...</p>
                </div>

                {{-- Attributes Container (Color, Size, etc.) --}}
                <div data-var-attributes-container class="space-y-4">
                    {{-- Dynamically injected attributes --}}
                </div>

                {{-- Quantity Stepper --}}
                <div class="flex items-center justify-between border-t border-ink/8 pt-4">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-muted">পরিমাণ</span>
                        <span class="text-[11px] text-muted">পিসের সংখ্যা নির্বাচন করুন</span>
                    </div>
                    <div class="flex items-center rounded-xl border border-ink/15 bg-white overflow-hidden">
                        <button
                            type="button"
                            class="px-3.5 py-2 text-base font-bold text-ink hover:bg-mist transition cursor-pointer select-none"
                            data-var-qty-minus
                        >&minus;</button>
                        <input
                            type="number"
                            value="1"
                            min="1"
                            max="99"
                            class="w-12 bg-transparent text-center font-sans text-sm font-bold text-ink focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            data-var-qty-input
                        >
                        <button
                            type="button"
                            class="px-3.5 py-2 text-base font-bold text-ink hover:bg-mist transition cursor-pointer select-none"
                            data-var-qty-plus
                        >&plus;</button>
                    </div>
                </div>
            </div>

            {{-- Modal Footer CTA --}}
            <div class="border-t border-ink/8 bg-[#FAFBF9] p-4 sm:px-5">
                <div class="grid grid-cols-2 gap-2.5">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#013F1E] px-4 py-3 text-sm font-extrabold text-white shadow-md hover:bg-[#012E16] transition cursor-pointer disabled:opacity-50"
                        data-var-btn-buy-now
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        <span>অর্ডার করুন</span>
                    </button>

                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-[#013F1E] bg-[#013F1E]/5 px-3 py-3 text-xs sm:text-sm font-extrabold text-[#013F1E] hover:bg-[#013F1E] hover:text-white transition cursor-pointer disabled:opacity-50"
                        data-var-btn-add-bag
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="truncate">ব্যাগে যোগ করুন</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
