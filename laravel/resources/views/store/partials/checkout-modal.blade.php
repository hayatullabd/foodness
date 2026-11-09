{{-- Checkout & Order Confirmation Modal --}}
<div
    id="checkout-modal"
    class="fn-co-modal fixed inset-0 z-50 hidden"
    data-checkout-modal
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
>
    <div class="fn-co-backdrop" data-checkout-backdrop></div>

    <div class="fn-co-stage">
        <div
            class="fn-co-panel scale-95 opacity-0 transition-all duration-300"
            data-checkout-panel
        >
            <div class="fn-co-head">
                <div class="fn-co-head-main">
                    <button
                        type="button"
                        class="fn-icon-btn hidden"
                        data-checkout-back
                        title="পূর্বের অপশনে ফিরুন"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <h3>অর্ডার করুন</h3>
                </div>
                <button
                    type="button"
                    class="fn-icon-btn"
                    data-checkout-close
                    aria-label="বন্ধ করুন"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="fn-co-scroll" data-checkout-view-form>
                <div class="hidden rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700 font-medium" data-checkout-error-box>
                    <span data-checkout-error-message></span>
                </div>
                <div class="fn-co-split">
                    <div class="fn-co-col">
                        <section class="fn-co-card">
                            <h4>
                                <span>আপনার পণ্য</span>
                                <span class="fn-count" data-checkout-items-count>০</span>
                            </h4>
                            <div data-checkout-items-list>
                                <div class="space-y-3 py-2 animate-pulse" data-checkout-skeleton>
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-xl bg-ink/10 shrink-0"></div>
                                        <div class="space-y-1.5 flex-1">
                                            <div class="h-3 w-3/4 bg-ink/10 rounded"></div>
                                            <div class="h-2.5 w-1/3 bg-ink/10 rounded"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="fn-co-subline">
                                <span>পণ্যের সাবটোটাল</span>
                                <strong data-checkout-subtotal-val>৳০</strong>
                            </div>
                        </section>
                        <section class="fn-co-card">
                            <h4>ডেলিভারি</h4>
                            <div class="fn-co-choices" data-checkout-shipping-list role="radiogroup" aria-label="ডেলিভারি এলাকা"></div>
                        </section>
                    </div>

                    <div class="fn-co-col">
                        <section class="fn-co-card fn-co-fields">
                            <h4>অর্ডার করতে নিচের তথ্যগুলি দিন</h4>
                        <form id="modal-checkout-form">
                            @csrf
                            <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                                <input type="text" name="hp_email" tabindex="-1" autocomplete="off" value="">
                                <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
                            </div>
                            <input type="hidden" name="_submission_time" value="{{ encrypt(time()) }}">

                            <div class="fn-field">
                                <label class="fn-label">নাম</label>
                                <input
                                    type="text"
                                    name="customer_name"
                                    required
                                    autocomplete="name"
                                    placeholder="আপনার নাম"
                                    data-bn-voice="name"
                                    class="fn-control"
                                    data-checkout-name
                                >
                            </div>

                            <div class="fn-field">
                                <label class="fn-label">মোবাইল নাম্বার</label>
                                <div class="fn-phone relative">
                                    <input
                                        type="tel"
                                        name="customer_phone"
                                        required
                                        inputmode="numeric"
                                        autocomplete="tel"
                                        maxlength="11"
                                        placeholder="১১ ডিজিট মোবাইল নাম্বার"
                                        data-bn-voice="phone"
                                        class="fn-control"
                                        data-checkout-phone
                                    >
                                    <button
                                        type="button"
                                        class="hidden absolute rounded-lg bg-[#013F1E] px-3 py-1.5 text-xs font-bold text-white"
                                        data-modal-send-otp-btn
                                    >
                                        ওটিপি পাঠান
                                    </button>
                                </div>
                                <div class="hidden mt-2.5 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3" data-modal-otp-container>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                            <span>ওটিপি কোড লিখুন</span>
                                            <span class="hidden rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] text-white font-bold" data-modal-otp-verified-badge>Verified ✓</span>
                                        </span>
                                        <span class="text-[11px] font-mono text-emerald-700" data-modal-otp-timer-label></span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <input
                                            type="text"
                                            maxlength="6"
                                            inputmode="numeric"
                                            placeholder="XXXXXX"
                                            class="w-32 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-center text-sm font-bold tracking-widest text-ink focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                                            data-modal-otp-input
                                        >
                                        <button
                                            type="button"
                                            class="rounded-lg bg-emerald-700 px-3.5 py-1.5 text-xs font-bold text-white"
                                            data-modal-verify-otp-btn
                                        >
                                            যাচাই করুন
                                        </button>
                                    </div>
                                    <div class="hidden mt-1.5 text-xs font-medium" data-modal-otp-message-box></div>
                                </div>
                            </div>

                            <div class="fn-field">
                                <label class="fn-label">ঠিকানা</label>
                                <textarea
                                    name="customer_address"
                                    rows="2"
                                    required
                                    autocomplete="street-address"
                                    placeholder="বাসা নম্বর, গ্রাম/মহল্লা, উপজেলা, জেলা"
                                    data-bn-voice="address"
                                    class="fn-control"
                                    data-checkout-address
                                ></textarea>
                            </div>

                            <div class="fn-field">
                                <label class="fn-label">অর্ডার নোট</label>
                                <input
                                    type="text"
                                    name="notes"
                                    placeholder="স্পেশাল কিছু বলতে চাইলে লিখুন (অপশনাল)"
                                    class="fn-control"
                                    data-checkout-notes
                                >
                            </div>
                        </form>
                        </section>

                        <section class="fn-co-card">
                            <h4>মূল্য বিবরণ</h4>
                            <div class="fn-totals">
                                <div>
                                    <span>মোট</span>
                                    <span data-checkout-subtotal-val>৳০</span>
                                </div>
                                <div>
                                    <span>ডেলিভারি চার্জ</span>
                                    <span data-checkout-shipping-val>এলাকা নির্বাচন করুন</span>
                                </div>
                                <div class="fn-due">
                                    <span>সর্বমোট</span>
                                    <span data-checkout-total-val>৳০</span>
                                </div>
                            </div>
                            <div class="fn-field" data-checkout-payment-methods-container>
                                <label class="fn-label">পেমেন্ট মাধ্যম</label>
                                <div class="fn-co-choices" data-checkout-payment-list>
                                    <label class="fn-co-choice">
                                        <input type="radio" name="payment_method" value="cod" checked>
                                        <span>ক্যাশ অন ডেলিভারি</span>
                                    </label>
                                    <div class="hidden" data-checkout-sslcommerz-row>
                                        <label class="fn-co-choice">
                                            <input type="radio" name="payment_method" value="sslcommerz">
                                            <span>অনলাইন পেমেন্ট</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </section>

                        <div class="fn-co-confirm" data-checkout-footer>
                            @if ((string) ($store['checkout_terms_enabled'] ?? '1') === '1')
                            <label class="fn-co-terms">
                                <input type="checkbox" data-checkout-terms>
                                <span>
                                    আমি
                                    <a href="{{ route('page.terms') }}" target="_blank" rel="noopener">শর্তাবলি ও ব্যবহারবিধি</a>,
                                    <a href="{{ route('page.privacy') }}" target="_blank" rel="noopener">গোপনীয়তা নীতিমালা</a>
                                    ও
                                    <a href="{{ route('page.refund-return') }}" target="_blank" rel="noopener">পণ্য বিনিময় ও ফেরত নীতিমালা</a>
                                    পড়েছি এবং সম্মত আছি।
                                </span>
                            </label>
                            @endif
                            <button type="button" class="fn-submit" data-checkout-submit-btn>
                                <svg class="h-5 w-5 animate-spin hidden" data-checkout-spinner fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span data-checkout-submit-text>অর্ডার কনফার্ম করুন</span>
                            </button>
                            <p>আমাদের একজন কাস্টমার প্রতিনিধি আপনাকে কল করে আবার কনফার্ম হবে</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fn-co-success hidden" data-checkout-view-success>
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="fn-co-kicker">অর্ডার সম্পন্ন</p>
                <h2>অর্ডারের জন্য ধন্যবাদ!</h2>
                <p class="fn-lead">
                    আপনার অর্ডার <span data-success-invoice>#০০০০</span> কনফার্ম হয়েছে। প্রতিনিধি খুব দ্রুত যোগাযোগ করবেন।
                </p>
                <div class="fn-success-card">
                    <div><span>ইনভয়েস</span><strong data-success-invoice-detail>-</strong></div>
                    <div><span>নাম</span><strong data-success-customer>-</strong></div>
                    <div><span>মোবাইল</span><strong data-success-phone>-</strong></div>
                    <div><span>সর্বমোট</span><strong data-success-total>৳০</strong></div>
                </div>
                <div style="max-width:420px;margin:18px auto 0;">
                    <button type="button" class="fn-submit" data-checkout-success-close>আরও কেনাকাটা করুন</button>
                </div>
            </div>

        </div>
    </div>
</div>
