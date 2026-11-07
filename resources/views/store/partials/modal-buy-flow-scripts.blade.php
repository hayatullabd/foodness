{{-- Modal Buy Flow & Checkout Manager --}}
<script>
(() => {
    // Endpoints
    const productModalUrl = @json(route('product.modal.data', ['product' => ':id']));
    const cartStoreModalUrl = @json(route('cart.store.modal'));
    const cartDestroyModalUrl = @json(route('cart.destroy.modal'));
    const cartUpdateModalUrl = @json(route('cart.update.modal'));
    const checkoutModalDataUrl = @json(route('checkout.modal.data'));
    const checkoutStoreModalUrl = @json(route('checkout.modal'));
    const incompleteProgressUrl = @json(route('checkout.incomplete'));
    const buyNowCancelUrl = @json(route('cart.buy-now.cancel'));
    let buyNowCheckout = false;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Instant Client Cache to eliminate redundant network roundtrips
    const productCache = new Map();
    let checkoutDataCache = null;

    // DOM Elements - Variation Modal
    const varModal = document.getElementById('variation-modal');
    const varPanel = varModal?.querySelector('[data-variation-panel]');
    const varBackdrop = varModal?.querySelector('[data-variation-backdrop]');
    const varCloseBtn = varModal?.querySelector('[data-variation-close]');
    const varImg = varModal?.querySelector('[data-var-img]');
    const varImgFallback = varModal?.querySelector('[data-var-img-fallback]');
    const varCategory = varModal?.querySelector('[data-var-category]');
    const varName = varModal?.querySelector('[data-var-name]');
    const varPrice = varModal?.querySelector('[data-var-price]');
    const varComparePrice = varModal?.querySelector('[data-var-compare-price]');
    const varStockBadge = varModal?.querySelector('[data-var-stock-badge]');
    const varDeliveryBadge = varModal?.querySelector('[data-var-delivery-badge]');
    const varAttrsContainer = varModal?.querySelector('[data-var-attributes-container]');
    const varLoading = varModal?.querySelector('[data-var-loading]');
    const varQtyMinus = varModal?.querySelector('[data-var-qty-minus]');
    const varQtyPlus = varModal?.querySelector('[data-var-qty-plus]');
    const varQtyInput = varModal?.querySelector('[data-var-qty-input]');
    const varBtnBuyNow = varModal?.querySelector('[data-var-btn-buy-now]');
    const varBtnAddBag = varModal?.querySelector('[data-var-btn-add-bag]');

    // DOM Elements - Checkout Modal
    const checkModal = document.getElementById('checkout-modal');
    const checkPanel = checkModal?.querySelector('[data-checkout-panel]');
    const checkBackdrop = checkModal?.querySelector('[data-checkout-backdrop]');
    const checkCloseBtn = checkModal?.querySelector('[data-checkout-close]');
    const checkBackBtn = checkModal?.querySelector('[data-checkout-back]');
    const checkViewForm = checkModal?.querySelector('[data-checkout-view-form]');
    const checkViewSuccess = checkModal?.querySelector('[data-checkout-view-success]');
    const checkFooter = checkModal?.querySelector('[data-checkout-footer]');
    const checkItemsCount = checkModal?.querySelector('[data-checkout-items-count]');
    const checkItemsList = checkModal?.querySelector('[data-checkout-items-list]');
    const checkSubtotalHeader = checkModal?.querySelector('[data-checkout-subtotal-header]');
    const checkSubtotalVal = checkModal?.querySelector('[data-checkout-subtotal-val]');
    const checkShippingVal = checkModal?.querySelector('[data-checkout-shipping-val]');
    const checkTotalVal = checkModal?.querySelector('[data-checkout-total-val]');
    const checkShippingList = checkModal?.querySelector('[data-checkout-shipping-list]');
    const selectedShippingInput = () => checkShippingList?.querySelector('input[name="shipping_charge_id"]:checked');
    const checkForm = document.getElementById('modal-checkout-form');
    const checkNameInput = checkModal?.querySelector('[data-checkout-name]');
    const checkPhoneInput = checkModal?.querySelector('[data-checkout-phone]');
    const checkAddressInput = checkModal?.querySelector('[data-checkout-address]');
    const checkNotesInput = checkModal?.querySelector('[data-checkout-notes]');
    const checkSslRow = checkModal?.querySelector('[data-checkout-sslcommerz-row]');
    const checkErrorBox = checkModal?.querySelector('[data-checkout-error-box]');
    const checkErrorMsg = checkModal?.querySelector('[data-checkout-error-message]');
    const checkSubmitBtn = checkModal?.querySelector('[data-checkout-submit-btn]');
    const checkSpinner = checkModal?.querySelector('[data-checkout-spinner]');
    const checkSubmitText = checkModal?.querySelector('[data-checkout-submit-text]');

    // OTP Elements for Modal
    const modalSendOtpBtn = checkModal?.querySelector('[data-modal-send-otp-btn]');
    const modalOtpContainer = checkModal?.querySelector('[data-modal-otp-container]');
    const modalOtpInput = checkModal?.querySelector('[data-modal-otp-input]');
    const modalVerifyOtpBtn = checkModal?.querySelector('[data-modal-verify-otp-btn]');
    const modalOtpMsgBox = checkModal?.querySelector('[data-modal-otp-message-box]');
    const modalOtpBadge = checkModal?.querySelector('[data-modal-otp-verified-badge]');
    const modalTimerLabel = checkModal?.querySelector('[data-modal-otp-timer-label]');

    let modalOtpRequired = false;
    let modalPhoneVerified = false;
    let modalVerifiedPhone = '';

    fetch(@json(route('checkout.otp.status')), { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            if (data.required) {
                modalOtpRequired = true;
            }
        }).catch(() => {});

    checkPhoneInput?.addEventListener('input', () => {
        const current = (checkPhoneInput.value || '').replace(/\D/g, '');
        if (modalOtpRequired) {
            if (current.length === 11 && current.startsWith('01')) {
                if (modalPhoneVerified && current === modalVerifiedPhone) {
                    modalSendOtpBtn?.classList.add('hidden');
                } else {
                    modalPhoneVerified = false;
                    modalSendOtpBtn?.classList.remove('hidden');
                    modalOtpBadge?.classList.add('hidden');
                }
            } else {
                modalSendOtpBtn?.classList.add('hidden');
                modalOtpContainer?.classList.add('hidden');
            }
        }
    });

    let modalOtpInterval = null;
    modalSendOtpBtn?.addEventListener('click', async () => {
        const phone = (checkPhoneInput?.value || '').replace(/\D/g, '');
        if (phone.length !== 11 || !phone.startsWith('01')) {
            window.showToast('সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন।', 'warning');
            return;
        }

        modalSendOtpBtn.disabled = true;
        modalSendOtpBtn.textContent = 'পাঠানো হচ্ছে...';

        try {
            const res = await fetch(@json(route('checkout.otp.send')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ phone }),
            });
            const data = await res.json();
            if (res.ok && data.ok) {
                modalOtpContainer?.classList.remove('hidden');
                if (modalOtpMsgBox) {
                    modalOtpMsgBox.className = 'mt-1.5 text-xs font-semibold text-emerald-700';
                    modalOtpMsgBox.textContent = data.message;
                    modalOtpMsgBox.classList.remove('hidden');
                }
                modalOtpInput?.focus();

                clearInterval(modalOtpInterval);
                let left = 45;
                modalOtpInterval = setInterval(() => {
                    left--;
                    if (left <= 0) {
                        clearInterval(modalOtpInterval);
                        if (modalTimerLabel) modalTimerLabel.textContent = '';
                        modalSendOtpBtn.disabled = false;
                        modalSendOtpBtn.textContent = 'পুনরায় পাঠান';
                    } else {
                        if (modalTimerLabel) modalTimerLabel.textContent = `পুনরায় পাঠান ${left}s`;
                    }
                }, 1000);
            } else {
                modalSendOtpBtn.disabled = false;
                modalSendOtpBtn.textContent = 'ওটিপি পাঠান';
                if (modalOtpMsgBox) {
                    modalOtpMsgBox.className = 'mt-1.5 text-xs font-semibold text-red-600';
                    modalOtpMsgBox.textContent = data.message || 'ওটিপি পাঠানো যায়নি।';
                    modalOtpMsgBox.classList.remove('hidden');
                }
            }
        } catch (e) {
            modalSendOtpBtn.disabled = false;
            modalSendOtpBtn.textContent = 'ওটিপি পাঠান';
        }
    });

    modalVerifyOtpBtn?.addEventListener('click', async () => {
        const phone = (checkPhoneInput?.value || '').replace(/\D/g, '');
        const otp = modalOtpInput?.value?.trim() || '';
        if (otp.length !== 6) {
            window.showToast('৬ সংখ্যার ওটিপি কোডটি লিখুন।', 'warning');
            return;
        }

        modalVerifyOtpBtn.disabled = true;
        modalVerifyOtpBtn.textContent = 'যাচাই হচ্ছে...';

        try {
            const res = await fetch(@json(route('checkout.otp.verify')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ phone, otp }),
            });
            const data = await res.json();
            if (res.ok && data.ok) {
                modalPhoneVerified = true;
                modalVerifiedPhone = phone;
                modalOtpBadge?.classList.remove('hidden');
                modalSendOtpBtn?.classList.add('hidden');
                if (modalOtpMsgBox) {
                    modalOtpMsgBox.className = 'mt-1.5 text-xs font-bold text-emerald-700';
                    modalOtpMsgBox.textContent = '✓ ' + data.message;
                    modalOtpMsgBox.classList.remove('hidden');
                }
                modalVerifyOtpBtn.textContent = 'যাচাই সম্পন্ন';
                modalVerifyOtpBtn.classList.replace('bg-emerald-700', 'bg-emerald-600');
            } else {
                modalVerifyOtpBtn.disabled = false;
                modalVerifyOtpBtn.textContent = 'যাচাই করুন';
                if (modalOtpMsgBox) {
                    modalOtpMsgBox.className = 'mt-1.5 text-xs font-semibold text-red-600';
                    modalOtpMsgBox.textContent = data.message || 'ভুল ওটিপি!';
                    modalOtpMsgBox.classList.remove('hidden');
                }
            }
        } catch (e) {
            modalVerifyOtpBtn.disabled = false;
            modalVerifyOtpBtn.textContent = 'যাচাই করুন';
        }
    });

    // Success View Elements
    const successInvoice = checkModal?.querySelector('[data-success-invoice]');
    const successInvoiceDetail = checkModal?.querySelector('[data-success-invoice-detail]');
    const successCustomer = checkModal?.querySelector('[data-success-customer]');
    const successPhone = checkModal?.querySelector('[data-success-phone]');
    const successTotal = checkModal?.querySelector('[data-success-total]');
    const successCloseBtn = checkModal?.querySelector('[data-checkout-success-close]');

    // Current State
    let currentProduct = null;
    let currentVariants = [];
    let currentSelection = {};
    let currentSubtotal = 0;
    let previousWasVariation = false;

    // Badges update helper
    const updateBadges = (count, subtotal) => {
        const headerBadge = document.querySelector('[data-cart-count-badge]');
        const mobileBadge = document.querySelector('[data-cart-count-badge-mobile]');
        const label = toBengaliNumber(count);
        [headerBadge, mobileBadge].forEach(b => {
            if (!b) return;
            b.textContent = b.classList.contains('cart-count') ? label : count;
            if (b.classList.contains('cart-count')) {
                b.classList.remove('hidden');
                return;
            }
            if (count > 0) {
                b.classList.remove('hidden');
            } else {
                b.classList.add('hidden');
            }
        });
        if (subtotal !== undefined && subtotal !== null) {
            const formatted = Math.round(Number(subtotal) || 0).toLocaleString('en-US');
            document.querySelectorAll('[data-cart-subtotal]').forEach((el) => {
                el.textContent = formatted + '৳';
            });
        }
    };

    // Helper for Bengali Numbers & Prices
    const toBengaliNumber = (num) => {
        const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return String(num).replace(/[0-9]/g, (d) => bnDigits[d]);
    };

    const formatBnPrice = (num) => {
        const formatted = Math.round(Number(num) || 0).toLocaleString('en-US');
        return '৳' + toBengaliNumber(formatted);
    };

    // Customer info storage
    const STORAGE_KEY = 'foodness_saved_customer_info';
    const loadCustomerInfo = () => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            if (checkNameInput && !checkNameInput.value && parsed.name) checkNameInput.value = parsed.name;
            if (checkPhoneInput && !checkPhoneInput.value && parsed.phone) checkPhoneInput.value = parsed.phone;
            if (checkAddressInput && !checkAddressInput.value && parsed.address) checkAddressInput.value = parsed.address;
        } catch (e) {}
    };

    const saveCustomerInfo = () => {
        try {
            const info = {
                name: checkNameInput?.value?.trim() || '',
                phone: checkPhoneInput?.value?.trim() || '',
                address: checkAddressInput?.value?.trim() || '',
            };
            if (info.name || info.phone || info.address) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(info));
            }
        } catch (e) {}
    };

    // Variation Modal logic
    const populateVariationModal = (data) => {
        currentProduct = data;
        currentVariants = data.variants || [];

        if (varLoading) varLoading.classList.add('hidden');

        // Set product info
        if (varName) varName.textContent = data.name;
        if (varCategory) varCategory.textContent = data.category_name || 'Foodness';
        if (varPrice) varPrice.textContent = data.price_formatted;
        if (varComparePrice) {
            if (data.compare_at_price_formatted) {
                varComparePrice.textContent = data.compare_at_price_formatted;
                varComparePrice.classList.remove('hidden');
            } else {
                varComparePrice.classList.add('hidden');
            }
        }

        if (data.image_url && varImg) {
            varImg.src = data.image_url;
            varImg.classList.remove('hidden');
            if (varImgFallback) varImgFallback.classList.add('hidden');
        } else {
            if (varImg) varImg.classList.add('hidden');
            if (varImgFallback) varImgFallback.classList.remove('hidden');
        }

        renderVariationAttributes();
    };

    const openVariationModal = async (productId) => {
        if (!varModal) return;

        // Reset
        currentProduct = null;
        currentVariants = [];
        currentSelection = {};
        if (varQtyInput) varQtyInput.value = 1;
        if (varDeliveryBadge) varDeliveryBadge.classList.add('hidden');
        if (varAttrsContainer) varAttrsContainer.innerHTML = '';

        varModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => {
            varPanel?.classList.remove('scale-95', 'opacity-0');
            varPanel?.classList.add('scale-100', 'opacity-100');
        }, 10);

        // Instant render if cached
        if (productCache.has(productId)) {
            populateVariationModal(productCache.get(productId));
            return;
        }

        if (varLoading) varLoading.classList.remove('hidden');

        try {
            const url = productModalUrl.replace(':id', productId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Product not found');
            const data = await res.json();
            productCache.set(productId, data);
            populateVariationModal(data);
        } catch (err) {
            window.showToast('পণ্যটির অপশন লোড করা যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।', 'error');
            closeVariationModal();
        }
    };

    const renderVariationAttributes = () => {
        if (!varAttrsContainer || !currentProduct) return;
        varAttrsContainer.innerHTML = '';

        const attrs = currentProduct.attributes || {};
        const attrKeys = Object.keys(attrs);

        // Pick initial variant
        const firstAvailable = currentVariants.find(v => v.stock > 0) || currentVariants[0];
        if (firstAvailable && firstAvailable.attributes) {
            Object.assign(currentSelection, firstAvailable.attributes);
        }

        attrKeys.forEach((attrName) => {
            const values = attrs[attrName];
            const groupEl = document.createElement('div');
            groupEl.className = 'space-y-2';

            const headerEl = document.createElement('div');
            headerEl.className = 'flex items-center justify-between text-xs';
            headerEl.innerHTML = `<span class="font-bold text-muted uppercase tracking-wider">${attrName}</span> <span class="font-extrabold text-ink" data-selected-val="${attrName}">${currentSelection[attrName] || ''}</span>`;
            groupEl.appendChild(headerEl);

            const pillsContainer = document.createElement('div');
            pillsContainer.className = 'flex flex-wrap gap-2';

            values.forEach((val) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                const isColor = attrName.toLowerCase() === 'color';
                const colorCode = isColor ? (currentProduct.color_codes?.[val] || null) : null;

                btn.className = `variant-chip relative flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-bold transition cursor-pointer shadow-xs`;
                btn.setAttribute('data-attr', attrName);
                btn.setAttribute('data-val', val);

                let innerHtml = '';
                if (colorCode) {
                    innerHtml += `<span class="h-3.5 w-3.5 rounded-full border border-black/20 shrink-0" style="background-color: ${colorCode}"></span>`;
                }
                innerHtml += `<span>${val}</span>`;
                btn.innerHTML = innerHtml;

                btn.addEventListener('click', () => {
                    currentSelection[attrName] = val;
                    syncVariationState();
                });

                pillsContainer.appendChild(btn);
            });

            groupEl.appendChild(pillsContainer);
            varAttrsContainer.appendChild(groupEl);
        });

        syncVariationState();
    };

    const findSelectedVariant = () => {
        if (!currentVariants.length) return null;
        return currentVariants.find((v) => {
            const vAttrs = v.attributes || {};
            return Object.keys(currentSelection).every((key) => {
                return String(vAttrs[key] || '') === String(currentSelection[key]);
            });
        }) || currentVariants[0];
    };

    const syncVariationState = () => {
        const variant = findSelectedVariant();

        // Update selected values text
        Object.keys(currentSelection).forEach(attrName => {
            const label = varModal?.querySelector(`[data-selected-val="${attrName}"]`);
            if (label) label.textContent = currentSelection[attrName];
        });

        // Update chip active classes
        varModal?.querySelectorAll('.variant-chip').forEach(btn => {
            const attr = btn.getAttribute('data-attr');
            const val = btn.getAttribute('data-val');
            const active = currentSelection[attr] === val;
            btn.classList.toggle('bg-ink', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-ink', active);
            btn.classList.toggle('bg-white', !active);
            btn.classList.toggle('text-ink', !active);
        });

        // Update price & stock
        if (variant) {
            updateVariationPrice();
            if (variant.image_url && varImg) {
                varImg.src = variant.image_url;
                varImg.classList.remove('hidden');
                if (varImgFallback) varImgFallback.classList.add('hidden');
            }

            if (varStockBadge) {
                if (variant.stock > 0) {
                    varStockBadge.textContent = 'স্টকে আছে';
                    varStockBadge.className = 'inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full ring-1 ring-emerald-600/20';
                    if (varBtnBuyNow) varBtnBuyNow.disabled = false;
                    if (varBtnAddBag) varBtnAddBag.disabled = false;
                } else {
                    varStockBadge.textContent = 'স্টক শেষ';
                    varStockBadge.className = 'inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full ring-1 ring-rose-600/20';
                    if (varBtnBuyNow) varBtnBuyNow.disabled = true;
                    if (varBtnAddBag) varBtnAddBag.disabled = true;
                }
            }

            if (varDeliveryBadge) {
                const delType = variant ? (variant.delivery_charge_type || 'default') : 'default';
                if (delType === 'free' || (delType === 'default' && currentProduct?.free_delivery)) {
                    varDeliveryBadge.textContent = 'ফ্রি ডেলিভারি';
                    varDeliveryBadge.className = 'inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full ring-1 ring-emerald-600/20';
                    varDeliveryBadge.style.display = '';
                    varDeliveryBadge.classList.remove('hidden');
                } else if (delType === 'custom') {
                    const ins = variant.custom_delivery_inside;
                    const out = variant.custom_delivery_outside;
                    let text = 'কাস্টম ডেলিভারি';
                    if (ins !== null && ins !== undefined && out !== null && out !== undefined) {
                        text = `ডেলিভারি: ভেতরে ৳${Math.round(ins)} / বাইরে ৳${Math.round(out)}`;
                    } else if (ins !== null && ins !== undefined) {
                        text = `ডেলিভারি: ৳${Math.round(ins)}`;
                    } else if (out !== null && out !== undefined) {
                        text = `ডেলিভারি: ৳${Math.round(out)}`;
                    }
                    varDeliveryBadge.textContent = text;
                    varDeliveryBadge.className = 'inline-flex items-center gap-1 text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full ring-1 ring-blue-600/20';
                    varDeliveryBadge.style.display = '';
                    varDeliveryBadge.classList.remove('hidden');
                } else if (currentProduct?.delivery_charge !== null && currentProduct?.delivery_charge !== undefined) {
                    varDeliveryBadge.textContent = `ডেলিভারি: ${formatBnPrice(currentProduct.delivery_charge)}`;
                    varDeliveryBadge.className = 'inline-flex items-center gap-1 text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full ring-1 ring-slate-300';
                    varDeliveryBadge.style.display = '';
                    varDeliveryBadge.classList.remove('hidden');
                } else {
                    varDeliveryBadge.textContent = '';
                    varDeliveryBadge.className = 'hidden';
                    varDeliveryBadge.style.display = 'none';
                    varDeliveryBadge.classList.add('hidden');
                }
            }
        }
    };

    const closeVariationModal = () => {
        if (!varModal) return;
        varPanel?.classList.remove('scale-100', 'opacity-100');
        varPanel?.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            varModal.classList.add('hidden');
            if (checkModal?.classList.contains('hidden')) {
                document.body.classList.remove('overflow-hidden');
            }
        }, 250);
    };

    const updateVariationPrice = () => {
        const variant = findSelectedVariant();
        const basePrice = variant ? variant.price : (currentProduct ? currentProduct.price : 0);
        const baseCompare = variant ? (variant.compare_at_price || 0) : (currentProduct ? (currentProduct.compare_at_price || 0) : 0);
        const qty = parseInt(varQtyInput?.value || '1', 10) || 1;

        if (varPrice) {
            varPrice.textContent = formatBnPrice(basePrice * qty);
        }
        if (varComparePrice) {
            if (baseCompare > basePrice) {
                varComparePrice.textContent = formatBnPrice(baseCompare * qty);
                varComparePrice.classList.remove('hidden');
            } else {
                varComparePrice.classList.add('hidden');
            }
        }
    };

    // Quantity Stepper
    varQtyMinus?.addEventListener('click', () => {
        let val = parseInt(varQtyInput?.value || '1', 10);
        if (val > 1) {
            varQtyInput.value = val - 1;
            updateVariationPrice();
        }
    });

    varQtyPlus?.addEventListener('click', () => {
        let val = parseInt(varQtyInput?.value || '1', 10);
        const variant = findSelectedVariant();
        const maxStock = variant ? variant.stock : (currentProduct?.total_stock || 99);
        if (val < maxStock) {
            varQtyInput.value = val + 1;
            updateVariationPrice();
        }
    });

    varQtyInput?.addEventListener('input', () => {
        updateVariationPrice();
    });

    // Handle Variation Modal Submit (Buy Now vs Add to Bag)
    // Helper: Client-Side AddToCart Tracking with Deduplication ID
    const triggerAddToCartTracking = (trackedEvent, itemData) => {
        if (!trackedEvent || !trackedEvent.event_id) return;

        const eventId = trackedEvent.event_id;
        const customData = trackedEvent.custom_data || {};
        const dataLayerPayload = trackedEvent.datalayer || {};

        // 1. Google Tag Manager / GA4
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        if (dataLayerPayload.event) {
            window.dataLayer.push(dataLayerPayload);
        } else {
            window.dataLayer.push({
                event: 'add_to_cart',
                event_id: eventId,
                ecommerce: {
                    currency: customData.currency || 'BDT',
                    value: Number(customData.value || 0),
                    items: customData.contents || []
                }
            });
        }

        // 2. Meta Pixel AddToCart with eventID for CAPI deduplication
        if (typeof fbq === 'function') {
            fbq('track', 'AddToCart', {
                content_name: itemData?.name || customData.contents?.[0]?.item_name || 'Product',
                content_category: itemData?.category || customData.contents?.[0]?.item_category || '',
                content_ids: customData.content_ids || [String(itemData?.id || '')],
                content_type: 'product',
                value: Number(customData.value || 0),
                currency: customData.currency || 'BDT',
                num_items: Number(customData.num_items || 1)
            }, {
                eventID: eventId
            });
        }

        // 3. TikTok Pixel AddToCart with event_id
        if (typeof ttq === 'object' && typeof ttq.track === 'function') {
            ttq.track('AddToCart', {
                contents: (customData.contents || []).map(it => ({
                    content_id: String(it.item_id || it.id || itemData?.id || ''),
                    content_name: it.item_name || it.name || itemData?.name || 'Product',
                    content_type: 'product',
                    quantity: Number(it.quantity || it.qty || 1),
                    price: Number(it.price || 0)
                })),
                value: Number(customData.value || 0),
                currency: customData.currency || 'BDT'
            }, {
                event_id: eventId
            });
        }
    };
    window.triggerAddToCartTracking = triggerAddToCartTracking;

    const handleVariationSubmit = async (isBuyNow) => {
        if (!currentProduct) return;
        const variant = findSelectedVariant();
        const qty = parseInt(varQtyInput?.value || '1', 10) || 1;

        const targetBtn = isBuyNow ? varBtnBuyNow : varBtnAddBag;
        if (!targetBtn) return;
        targetBtn.disabled = true;
        const origText = targetBtn.innerHTML;
        targetBtn.innerHTML = '<span>Processing...</span>';

        try {
            const res = await fetch(cartStoreModalUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    product_id: currentProduct.id,
                    variant_id: variant ? variant.id : null,
                    qty: qty,
                    buy_now: isBuyNow ? 1 : 0,
                }),
            });

            const data = await res.json();
            if (data.ok) {
                updateBadges(data.cart_count, data.subtotal);
                if (data.tracked_event) {
                    triggerAddToCartTracking(data.tracked_event, {
                        id: currentProduct?.id,
                        name: currentProduct?.name,
                        category: currentProduct?.category_name,
                    });
                }
                if (isBuyNow) {
                    previousWasVariation = true;
                    closeVariationModal();
                    openCheckoutModal(data.checkout_data || null);
                } else {
                    closeVariationModal();
                    window.showToast(data.message || 'পণ্যটি ব্যাগে যোগ করা হয়েছে!', 'success');
                }
            } else {
                window.showToast(data.message || 'পণ্যটি ব্যাগে যোগ করা সম্ভব হয়নি।', 'error');
            }
        } catch (err) {
            window.showToast('একটি সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।', 'error');
        } finally {
            targetBtn.disabled = false;
            targetBtn.innerHTML = origText;
        }
    };

    varBtnBuyNow?.addEventListener('click', () => handleVariationSubmit(true));
    varBtnAddBag?.addEventListener('click', () => handleVariationSubmit(false));
    varCloseBtn?.addEventListener('click', closeVariationModal);
    varBackdrop?.addEventListener('click', closeVariationModal);

    // ============================================
    // Checkout Modal Logic
    // ============================================
    const renderCheckoutModalData = (data) => {
        if (data.is_empty || !data.lines || data.lines.length === 0) {
            if (checkItemsList) {
                checkItemsList.innerHTML = `
                    <div class="py-6 text-center">
                        <p class="text-xs font-bold text-muted">আপনার ব্যাগে কোনো পণ্য নেই।</p>
                        <button type="button" class="mt-3 inline-block rounded-xl bg-[#013F1E]/10 px-4 py-2 text-xs font-extrabold text-[#013F1E] hover:bg-[#013F1E] hover:text-white transition cursor-pointer" data-checkout-empty-browse>
                            শপিং চালিয়ে যান
                        </button>
                    </div>
                `;
            }
            if (checkItemsCount) checkItemsCount.textContent = '০';
            if (checkSubtotalHeader) checkSubtotalHeader.textContent = '৳০';
            checkModal?.querySelectorAll('[data-checkout-subtotal-val]').forEach(el => { el.textContent = '৳০'; });
            currentSubtotal = 0;
            recalculateCheckoutTotals();
            if (checkSubmitBtn) checkSubmitBtn.disabled = true;
            return;
        }

        if (checkSubmitBtn) checkSubmitBtn.disabled = false;

        // Render Items
        currentSubtotal = data.subtotal || 0;
        if (checkItemsCount) checkItemsCount.textContent = toBengaliNumber(data.lines.length);
        if (checkSubtotalHeader) checkSubtotalHeader.textContent = formatBnPrice(data.subtotal);
        checkModal?.querySelectorAll('[data-checkout-subtotal-val]').forEach(el => { el.textContent = formatBnPrice(data.subtotal); });

        // Trigger Hybrid InitiateCheckout / begin_checkout on modal opening
        if (data.tracked_event) {
            const icEventId = data.tracked_event.event_id;
            const icSubtotal = data.subtotal || 0;
            const icLines = Array.isArray(data.lines) ? data.lines : Object.values(data.lines || {});
            const icContentIds = icLines.map(l => String(l.product_id || l.id));
            const icTotalQty = icLines.reduce((sum, l) => sum + (Number(l.qty) || 1), 0);

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            if (data.tracked_event.datalayer) {
                window.dataLayer.push(data.tracked_event.datalayer);
            }

            if (typeof fbq === 'function') {
                fbq('track', 'InitiateCheckout', {
                    content_ids: icContentIds,
                    content_type: 'product',
                    value: icSubtotal,
                    currency: 'BDT',
                    num_items: icTotalQty
                }, {
                    eventID: icEventId
                });
            }

            if (typeof ttq === 'object' && typeof ttq.track === 'function') {
                ttq.track('InitiateCheckout', {
                    contents: icLines.map(l => ({
                        content_id: String(l.product_id || l.id),
                        content_name: l.name,
                        quantity: Number(l.qty || 1),
                        price: Number(l.unit_price || 0)
                    })),
                    value: icSubtotal,
                    currency: 'BDT'
                }, {
                    event_id: icEventId
                });
            }
        }

        if (checkItemsList) {
            checkItemsList.innerHTML = data.lines.map(line => `
                <div class="fn-co-item">
                    ${line.image_url ? `<img src="${line.image_url}" alt="">` : `<div class="fn-co-ph">A</div>`}
                    <div class="fn-co-item-copy">
                        <h5>${line.name}</h5>
                        <p>${line.variation_name || [line.color, line.size].filter(Boolean).join(' / ') || 'স্ট্যান্ডার্ড'}</p>
                        <strong>${formatBnPrice(line.line_total)}</strong>
                    </div>
                    <div class="fn-co-item-side">
                        <div class="fn-co-qty">
                            <button type="button" data-cart-qty-change="-1" data-product-id="${line.product_id}" data-variant-id="${line.variant_id || ''}" data-qty="${line.qty}" aria-label="পরিমাণ কমান">&minus;</button>
                            <span>${toBengaliNumber(line.qty)}</span>
                            <button type="button" data-cart-qty-change="1" data-product-id="${line.product_id}" data-variant-id="${line.variant_id || ''}" data-qty="${line.qty}" aria-label="পরিমাণ বাড়ান">&plus;</button>
                        </div>
                        <button type="button" class="fn-co-item-remove" data-cart-remove data-product-id="${line.product_id}" data-variant-id="${line.variant_id || ''}" aria-label="বাদ দিন">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Render Shipping Charges
        if (checkShippingList && Array.isArray(data.shipping_charges)) {
            const currentSelected = selectedShippingInput()?.value;
            checkShippingList.innerHTML = data.shipping_charges.map((c) => `
                <label class="fn-co-choice">
                    <input
                        type="radio"
                        name="shipping_charge_id"
                        form="modal-checkout-form"
                        value="${c.id}"
                        data-amount="${c.amount}"
                        ${currentSelected == c.id ? 'checked' : ''}
                    >
                    <span>${c.name}</span>
                    <b>${c.amount_formatted}</b>
                </label>
            `).join('');
            recalculateCheckoutTotals();
        }

        // Payment Gateways
        const paymentListContainer = checkModal?.querySelector('[data-checkout-payment-list]');
        if (paymentListContainer && Array.isArray(data.gateways) && data.gateways.length > 0) {
            paymentListContainer.innerHTML = data.gateways.map((gw, idx) => `
                <label class="fn-co-choice">
                    <input type="radio" name="payment_method" value="${gw.id}" ${idx === 0 ? 'checked' : ''}>
                    <span>${gw.title}</span>
                </label>
            `).join('');
        } else if (data.sslcommerz_enabled && checkSslRow) {
            checkSslRow.classList.remove('hidden');
        } else if (checkSslRow) {
            checkSslRow.classList.add('hidden');
        }

        // Auto-fill logged in user
        if (data.customer) {
            if (checkNameInput && !checkNameInput.value && data.customer.name) checkNameInput.value = data.customer.name;
            if (checkPhoneInput && !checkPhoneInput.value && data.customer.phone) checkPhoneInput.value = data.customer.phone;
            if (checkAddressInput && !checkAddressInput.value && data.customer.address) checkAddressInput.value = data.customer.address;
        }
    };

    const abandonBuyNow = () => {
        if (!buyNowCheckout) return;
        buyNowCheckout = false;
        fetch(buyNowCancelUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: '{}',
            keepalive: true,
        }).catch(() => {});
    };

    const openCheckoutModal = async (initialData = null) => {
        if (!checkModal) return;
        if (initialData?.is_buy_now) {
            buyNowCheckout = true;
        }

        // Reset views
        checkViewForm?.classList.remove('hidden');
        checkViewSuccess?.classList.add('hidden');
        checkFooter?.classList.remove('hidden');
        checkErrorBox?.classList.add('hidden');

        if (previousWasVariation && checkBackBtn) {
            checkBackBtn.classList.remove('hidden');
        } else if (checkBackBtn) {
            checkBackBtn.classList.add('hidden');
        }

        // Show instant skeleton if no items rendered yet
        if (!initialData && checkItemsList && !checkItemsList.children.length) {
            checkItemsList.innerHTML = `
                <div class="space-y-3 py-2 animate-pulse" data-checkout-skeleton>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 flex-1">
                            <div class="h-11 w-11 rounded-xl bg-ink/10 shrink-0"></div>
                            <div class="space-y-1.5 flex-1">
                                <div class="h-3 w-3/4 bg-ink/10 rounded"></div>
                                <div class="h-2.5 w-1/3 bg-ink/10 rounded"></div>
                            </div>
                        </div>
                        <div class="h-6 w-16 bg-ink/10 rounded"></div>
                    </div>
                </div>
            `;
        }

        checkModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => {
            checkPanel?.classList.remove('scale-95', 'opacity-0');
            checkPanel?.classList.add('scale-100', 'opacity-100');
        }, 10);

        loadCustomerInfo();

        if (initialData) {
            renderCheckoutModalData(initialData);
            return;
        }

        try {
            const res = await fetch(checkoutModalDataUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            renderCheckoutModalData(data);
        } catch (err) {
            console.error(err);
        }
    };

    const recalculateCheckoutTotals = () => {
        const opt = selectedShippingInput();
        const shippingAmount = opt ? parseFloat(opt.dataset.amount || '0') : 0;
        const total = currentSubtotal + (opt ? shippingAmount : 0);

        if (checkShippingVal) {
            checkShippingVal.textContent = !opt
                ? 'এলাকা নির্বাচন করুন'
                : (shippingAmount === 0 ? 'ফ্রি' : formatBnPrice(shippingAmount));
            checkShippingVal.classList.toggle('is-pending', !opt);
        }
        if (checkTotalVal) {
            checkTotalVal.textContent = formatBnPrice(total);
        }
        if (checkSubmitText && checkSubmitBtn && !checkSubmitBtn.dataset.busy) {
            checkSubmitText.textContent = `অর্ডার কনফার্ম করুন ${formatBnPrice(total)}`;
        }
    };

    checkShippingList?.addEventListener('change', recalculateCheckoutTotals);

    // Order items list action delegation (Remove & Qty Stepper)
    checkItemsList?.addEventListener('click', async (e) => {
        // Handle Remove
        const removeBtn = e.target.closest('[data-cart-remove]');
        if (removeBtn) {
            e.preventDefault();
            const productId = removeBtn.getAttribute('data-product-id');
            const variantId = removeBtn.getAttribute('data-variant-id') || null;

            removeBtn.disabled = true;
            removeBtn.innerHTML = `
                <svg class="h-4 w-4 animate-spin text-rose-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;

            try {
                const res = await fetch(cartDestroyModalUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        variant_id: variantId,
                    }),
                });

                const data = await res.json();
                if (data.ok) {
                    updateBadges(data.cart_count, data.subtotal);
                    await openCheckoutModal();
                } else {
                    window.showToast(data.message || 'পণ্যটি সরানো যায়নি।', 'error');
                    await openCheckoutModal();
                }
            } catch (err) {
                console.error(err);
                window.showToast('পণ্যটি সরানো যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।', 'error');
                await openCheckoutModal();
            }
            return;
        }

        // Handle Quantity Stepper (+ / -)
        const qtyBtn = e.target.closest('[data-cart-qty-change]');
        if (qtyBtn) {
            e.preventDefault();
            const delta = parseInt(qtyBtn.getAttribute('data-cart-qty-change'), 10);
            const currentQty = parseInt(qtyBtn.getAttribute('data-qty'), 10);
            const newQty = currentQty + delta;
            const productId = qtyBtn.getAttribute('data-product-id');
            const variantId = qtyBtn.getAttribute('data-variant-id') || null;

            if (newQty <= 0) {
                const triggerRemove = qtyBtn.closest('.group')?.querySelector('[data-cart-remove]');
                triggerRemove?.click();
                return;
            }

            qtyBtn.disabled = true;
            try {
                const res = await fetch(cartUpdateModalUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        variant_id: variantId,
                        qty: newQty,
                    }),
                });

                const data = await res.json();
                if (data.ok) {
                    updateBadges(data.cart_count, data.subtotal);
                    await openCheckoutModal();
                } else {
                    window.showToast(data.message || 'পরিমাণ আপডেট করা সম্ভব হয়নি।', 'error');
                    await openCheckoutModal();
                }
            } catch (err) {
                console.error(err);
                window.showToast('পরিমাণ আপডেট করতে সমস্যা হয়েছে।', 'error');
                await openCheckoutModal();
            }
            return;
        }

        // Handle Continue Shopping / Browse button when bag is empty
        const browseBtn = e.target.closest('[data-checkout-empty-browse]');
        if (browseBtn) {
            e.preventDefault();
            closeCheckoutModal();
        }
    });

    const closeCheckoutModal = () => {
        if (!checkModal) return;
        abandonBuyNow();
        checkPanel?.classList.remove('scale-100', 'opacity-100');
        checkPanel?.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            checkModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            previousWasVariation = false;
        }, 250);
    };

    checkCloseBtn?.addEventListener('click', closeCheckoutModal);
    checkBackdrop?.addEventListener('click', closeCheckoutModal);
    successCloseBtn?.addEventListener('click', closeCheckoutModal);

    checkBackBtn?.addEventListener('click', () => {
        closeCheckoutModal();
        if (currentProduct) {
            openVariationModal(currentProduct.id);
        }
    });

    // Incomplete order progress save
    let incompleteTimer = null;
    const saveIncompleteOrder = () => {
        saveCustomerInfo();
        const phone = (checkPhoneInput?.value || '').replace(/\D/g, '');
        if (phone.length !== 11 || !phone.startsWith('01')) return;

        const body = new FormData();
        body.append('customer_name', checkNameInput?.value?.trim() || '');
        body.append('customer_phone', phone);
        body.append('customer_address', checkAddressInput?.value?.trim() || '');

        fetch(incompleteProgressUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body,
        }).catch(() => {});
    };

    [checkNameInput, checkPhoneInput, checkAddressInput].forEach(inp => {
        if (!inp) return;
        inp.addEventListener('input', () => {
            clearTimeout(incompleteTimer);
            incompleteTimer = setTimeout(saveIncompleteOrder, 500);
        });
    });

    // Checkout Submit
    checkSubmitBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        saveCustomerInfo();

        const name = checkNameInput?.value?.trim();
        const phone = (checkPhoneInput?.value || '').replace(/\D/g, '');
        const address = checkAddressInput?.value?.trim();
        const shippingChargeId = selectedShippingInput()?.value;
        const paymentMethod = checkForm?.querySelector('input[name="payment_method"]:checked')?.value || 'cod';
        const notes = checkNotesInput?.value?.trim();

        if (!name) {
            showError('অনুগ্রহ করে আপনার সম্পূর্ণ নাম লিখুন।');
            checkNameInput?.focus();
            return;
        }

        if (!phone || phone.length !== 11 || !phone.startsWith('01')) {
            showError('সঠিক ১১ ডিজিটের মোবাইল নম্বর লিখুন (যেমন: 01XXXXXXXXX)।');
            checkPhoneInput?.focus();
            return;
        }

        if (!address) {
            showError('অনুগ্রহ করে আপনার ডেলিভারির ঠিকানা লিখুন।');
            checkAddressInput?.focus();
            return;
        }

        if (modalOtpRequired && !modalPhoneVerified) {
            showError('অর্ডার নিশ্চিত করতে অনুগ্রহ করে মোবাইল নম্বরে পাঠানো OTP কোড দিয়ে যাচাই করুন।');
            modalOtpContainer?.classList.remove('hidden');
            modalOtpInput?.focus();
            if (modalSendOtpBtn && !modalSendOtpBtn.disabled) {
                modalSendOtpBtn.click();
            }
            return;
        }

        if (!shippingChargeId) {
            showError('অনুগ্রহ করে ডেলিভারি এলাকা নির্বাচন করুন।');
            checkShippingList?.querySelector('input')?.focus();
            return;
        }

        checkErrorBox?.classList.add('hidden');
        checkSubmitBtn.disabled = true;
        checkSubmitBtn.dataset.busy = '1';
        checkSpinner?.classList.remove('hidden');
        checkSubmitText.textContent = 'অর্ডার প্রক্রিয়া হচ্ছে...';

        try {
            const res = await fetch(checkoutStoreModalUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Device-Id': window.foodness_did || '',
                },
                body: JSON.stringify({
                    customer_name: name,
                    customer_phone: phone,
                    customer_address: address,
                    shipping_charge_id: shippingChargeId,
                    payment_method: paymentMethod,
                    notes: notes,
                    device_id: window.foodness_did || '',
                    hp_email: checkForm?.querySelector('input[name="hp_email"]')?.value || '',
                    _submission_time: checkForm?.querySelector('input[name="_submission_time"]')?.value || '',
                }),
            });

            const data = await res.json();

            if (data.ok) {
                // SSLCommerz redirect
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                // Render success view inside modal
                if (successInvoice) successInvoice.textContent = `#${data.invoice_no}`;
                if (successInvoiceDetail) successInvoiceDetail.textContent = `#${data.invoice_no}`;
                if (successCustomer) successCustomer.textContent = data.customer_name;
                if (successPhone) successPhone.textContent = data.customer_phone;
                if (successTotal) successTotal.textContent = data.total_formatted;

                checkViewForm?.classList.add('hidden');
                checkFooter?.classList.add('hidden');
                checkBackBtn?.classList.add('hidden');
                checkViewSuccess?.classList.remove('hidden');

                updateBadges(0, 0);

                // Hybrid Deduplicated Purchase Events (GTM dataLayer, Meta Pixel, TikTok Pixel)
                const purEventId = data.event_id || ('order_' + data.order_id);
                const orderItems = (data.items || []).map(item => ({
                    item_id: String(item.product_id || item.id || ''),
                    item_name: item.name || 'Product',
                    quantity: Number(item.qty || 1),
                    price: Number(item.unit_price || item.price || 0)
                }));

                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                if (data.tracked_event && data.tracked_event.datalayer) {
                    window.dataLayer.push(data.tracked_event.datalayer);
                } else {
                    window.dataLayer.push({
                        event: 'purchase',
                        event_id: purEventId,
                        ecommerce: {
                            transaction_id: data.invoice_no,
                            value: Number(data.total || 0),
                            currency: 'BDT',
                            items: orderItems
                        }
                    });
                }

                if (typeof fbq === 'function') {
                    fbq('track', 'Purchase', {
                        value: Number(data.total || 0),
                        currency: 'BDT',
                        num_items: Number(data.items_count || orderItems.length || 1),
                        order_id: data.invoice_no,
                        content_type: 'product',
                        contents: orderItems.map(it => ({ id: it.item_id, quantity: it.quantity, item_price: it.price }))
                    }, {
                        eventID: purEventId
                    });
                }

                if (typeof ttq === 'object' && typeof ttq.track === 'function') {
                    ttq.track('CompletePayment', {
                        contents: orderItems.map(it => ({
                            content_id: it.item_id,
                            content_name: it.item_name,
                            quantity: it.quantity,
                            price: it.price
                        })),
                        value: Number(data.total || 0),
                        currency: 'BDT',
                        order_id: data.invoice_no
                    }, {
                        event_id: purEventId
                    });
                }
            } else {
                showError(data.message || 'অর্ডার সম্পন্ন করা সম্ভব হয়নি। তথ্যগুলো পুনরায় পরীক্ষা করুন।');
            }
        } catch (err) {
            showError('নেটওয়ার্ক সংযোগ সমস্যা। অনুগ্রহ করে আবার চেষ্টা করুন।');
        } finally {
            checkSubmitBtn.disabled = false;
            delete checkSubmitBtn.dataset.busy;
            checkSpinner?.classList.add('hidden');
            recalculateCheckoutTotals();
        }
    });

    const showError = (msg) => {
        if (checkErrorBox && checkErrorMsg) {
            checkErrorMsg.textContent = msg;
            checkErrorBox.classList.remove('hidden');
            checkErrorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    // Global listener for Buy Now buttons across cards and pages
    // Pre-fetch product modal data on hover / touchstart for instant modal open
    const prefetchProduct = (productId) => {
        if (!productId || productCache.has(productId)) return;
        const url = productModalUrl.replace(':id', productId);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (data) productCache.set(productId, data);
            })
            .catch(() => {});
    };

    document.addEventListener('mouseover', (e) => {
        const btn = e.target.closest('[data-open-buy-modal]');
        if (btn && btn.getAttribute('data-has-variants') === 'true') {
            prefetchProduct(btn.getAttribute('data-product-id'));
        }
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const btn = e.target.closest('[data-open-buy-modal]');
        if (btn && btn.getAttribute('data-has-variants') === 'true') {
            prefetchProduct(btn.getAttribute('data-product-id'));
        }
    }, { passive: true });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-open-buy-modal]');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const productId = btn.getAttribute('data-product-id');
            const hasVariants = btn.getAttribute('data-has-variants') === 'true';

            if (hasVariants) {
                openVariationModal(productId);
            } else {
                // Immediate Visual Feedback on button
                const origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.classList.add('opacity-80', 'pointer-events-none');
                btn.innerHTML = `
                    <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>অর্ডার হচ্ছে...</span>
                `;

                // Single variant product -> directly add to cart and open checkout modal
                addDirectAndCheckout(productId, () => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-80', 'pointer-events-none');
                    btn.innerHTML = origHtml;
                });
            }
            return;
        }

        const cartBtn = e.target.closest('[data-open-cart-modal]');
        if (cartBtn) {
            e.preventDefault();
            e.stopPropagation();
            buyNowCheckout = false;
            fetch(buyNowCancelUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '{}',
            }).finally(() => openCheckoutModal());
            return;
        }
    });

    const addDirectAndCheckout = async (productId, onDone = null) => {
        try {
            const res = await fetch(cartStoreModalUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    product_id: productId,
                    qty: 1,
                    buy_now: 1,
                }),
            });
            const data = await res.json();
            if (data.ok) {
                updateBadges(data.cart_count, data.subtotal);
                if (data.tracked_event) {
                    triggerAddToCartTracking(data.tracked_event, {
                        id: productId,
                    });
                }
                previousWasVariation = false;
                openCheckoutModal(data.checkout_data || null);
            } else {
                window.showToast(data.message || 'পণ্যটি স্টকে নেই।', 'error');
            }
        } catch (e) {
            window.showToast('একটি সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।', 'error');
        } finally {
            if (typeof onDone === 'function') onDone();
        }
    };

    // Expose Global
    window.StoreModal = {
        openVariationModal,
        openCheckoutModal,
        closeVariationModal,
        closeCheckoutModal,
        updateBadges,
    };
})();
</script>
