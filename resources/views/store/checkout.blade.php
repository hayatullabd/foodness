@extends('layouts.store')

@php
    use App\Support\Money;
@endphp

@section('title', 'Checkout — '.($store['store_name'] ?? config('app.name', 'Store')))

@section('content')
    <section class="fn-co-page">
        <div class="fn-co-wrap">
        <div class="fn-co-top">
            <div>
                <p class="fn-co-kicker">নিরাপদ অর্ডার</p>
                <h1>চেকআউট</h1>
                <p class="fn-co-lead">
                    @if ($sslCommerzEnabled)
                        ক্যাশ অন ডেলিভারি অথবা অনলাইনে পেমেন্ট করে অর্ডার সম্পন্ন করুন।
                    @else
                        পণ্য হাতে পেয়ে টাকা দিন। কোনো অগ্রিম পেমেন্ট লাগবে না।
                    @endif
                </p>
            </div>
            <ol class="fn-co-steps" aria-label="অর্ডার ধাপ">
                <li class="is-done"><span>১</span> ব্যাগ</li>
                <li class="is-on"><span>২</span> বিবরণ</li>
                <li><span>৩</span> নিশ্চিত</li>
            </ol>
        </div>

        <div class="fn-co-layout">
            <div class="fn-co-card">
                <form method="POST" action="{{ route('checkout.store') }}" autocomplete="on" id="checkout-form">
                    @csrf

                    {{-- Anti-Bot Honeypot (Invisible to real users, catches bots) --}}
                    <div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                        <input type="text" name="hp_email" tabindex="-1" autocomplete="off" value="">
                        <input type="text" name="website_url" tabindex="-1" autocomplete="off" value="">
                    </div>

                    {{-- Form Submission Timer Token (Rejects < 2s submissions) --}}
                    <input type="hidden" name="_submission_time" value="{{ encrypt(time()) }}">

                    <div class="fn-co-block">
                        <h2><span class="fn-co-num">১</span> গ্রাহকের তথ্য</h2>
                        <div>
                            <div class="fn-field">
                                <label for="customer_name">পূর্ণ নাম *</label>
                                <input
                                    id="customer_name"
                                    name="customer_name"
                                    type="text"
                                    value="{{ old('customer_name', $customer['name'] ?? '') }}"
                                    required
                                    autocomplete="name"
                                    placeholder="আপনার সম্পূর্ণ নাম"
                                    data-bn-voice="name"
                                >
                                @error('customer_name') <p class="fn-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="fn-field">
                                <label for="customer_phone">মোবাইল নম্বর *</label>
                                <div class="fn-phone">
                                    <input
                                        id="customer_phone"
                                        name="customer_phone"
                                        type="tel"
                                        inputmode="numeric"
                                        autocomplete="tel"
                                        maxlength="11"
                                        value="{{ old('customer_phone', $customer['phone'] ?? '') }}"
                                        required
                                        placeholder="01XXXXXXXXX"
                                        data-bn-voice="phone"
                                    >
                                    <button
                                        type="button"
                                        id="send-otp-btn"
                                        class="hidden absolute right-1.5 top-1.5 rounded-lg bg-[#013F1E] px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-[#012E16] transition disabled:opacity-50"
                                    >
                                        Send OTP
                                    </button>
                                </div>
                                <p class="fn-hint">অর্ডার কনফার্মেশন ও কুরিয়ার আপডেট এই নম্বরে যাবে।</p>
                                @error('customer_phone') <p class="fn-error">{{ $message }}</p> @enderror

                                {{-- Inline OTP Verification Box (shown if OTP is enabled) --}}
                                <div id="checkout-otp-container" class="hidden mt-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                            <span>🔒 ওটিপি কোড লিখুন</span>
                                            <span id="otp-verified-badge" class="hidden rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] text-white font-bold">Verified ✓</span>
                                        </span>
                                        <span id="otp-timer-label" class="text-[11px] font-mono text-emerald-700"></span>
                                    </div>
                                    <p class="text-[11px] text-emerald-800 mb-2">আপনার মোবাইল নম্বরে পাঠানো ৬ সংখ্যার কোডটি বসিয়ে যাচাই করুন।</p>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="text"
                                            id="order_otp_input"
                                            maxlength="6"
                                            inputmode="numeric"
                                            placeholder="XXXXXX"
                                            class="w-36 rounded-lg border border-emerald-300 bg-white px-3 py-2 text-center text-sm font-bold tracking-widest text-ink focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                                        >
                                        <button
                                            type="button"
                                            id="verify-otp-btn"
                                            class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition"
                                        >
                                            যাচাই করুন
                                        </button>
                                    </div>
                                    <div id="otp-message-box" class="hidden mt-2 text-xs font-medium"></div>
                                </div>
                            </div>

                            <div class="fn-field">
                                <label for="customer_address">ডেলিভারির ঠিকানা *</label>
                                <textarea
                                    id="customer_address"
                                    name="customer_address"
                                    rows="3"
                                    required
                                    autocomplete="street-address"
                                    placeholder="বাসা, রোড, এলাকা, থানা ও জেলা"
                                    data-bn-voice="address"
                                >{{ old('customer_address', $customer['address'] ?? '') }}</textarea>
                                @error('customer_address') <p class="fn-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="fn-co-block">
                        <h2><span class="fn-co-num">২</span> ডেলিভারি এলাকা</h2>

                        <div class="fn-field">
                            <label for="shipping_charge_id">এলাকা *</label>
                            <select
                                id="shipping_charge_id"
                                name="shipping_charge_id"
                                required
                            >
                                @foreach ($shippingCharges as $charge)
                                    @php
                                        $areaOverride = isset($cart) ? $cart->deliveryChargeForArea($charge->id) : null;
                                        $effectiveAmount = $areaOverride !== null ? $areaOverride : ($customDeliveryCharge !== null ? $customDeliveryCharge : $charge->amount);
                                    @endphp
                                    <option
                                        value="{{ $charge->id }}"
                                        data-amount="{{ $effectiveAmount }}"
                                        @selected(old('shipping_charge_id') == $charge->id)
                                    >
                                        {{ $charge->name }} — {{ (float) $effectiveAmount == 0 ? 'ফ্রি ডেলিভারি' : Money::formatBn($effectiveAmount) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="fn-hint">Steadfast বা Pathao কুরিয়ারে পৌঁছে দেওয়া হবে।</p>
                        </div>
                    </div>

                    <div class="fn-co-block">
                        <h2><span class="fn-co-num">৩</span> পেমেন্ট</h2>

                        <div>
                            @php
                                $selectedMethod = old('payment_method', $availableGateways[0]['id'] ?? 'cod');
                            @endphp

                            @foreach ($availableGateways as $gw)
                                <label class="fn-pay">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $gw['id'] }}"
                                        @checked($selectedMethod === $gw['id'])
                                    >
                                    <span>
                                        <strong>{{ $gw['title'] }}</strong>
                                        @if (! empty($gw['subtitle']))
                                            <small>{{ $gw['subtitle'] }}</small>
                                        @endif
                                    </span>
                                    @if ($gw['id'] === 'cod')
                                        <span class="fn-badge">জনপ্রিয়</span>
                                    @elseif ($gw['id'] === 'sslcommerz')
                                        <span class="fn-badge">অনলাইন</span>
                                    @elseif ($gw['id'] === 'manual')
                                        <span class="fn-badge">ম্যানুয়াল</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method') <p class="fn-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="fn-co-block">
                        <div class="fn-field">
                            <label for="notes">বিশেষ নির্দেশনা <span style="font-weight:600;color:#66746c;">(ঐচ্ছিক)</span></label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="2"
                                placeholder="ডেলিভারির সময় বা অন্য কোনো কথা"
                            >{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="fn-submit">
                            <span>অর্ডার কনফার্ম করুন</span>
                        </button>
                        <p class="fn-hint" style="text-align:center;">
                            অর্ডার দিলে {{ $store['store_name'] ?? config('app.name') }} থেকে একটি কনফার্মেশন কল আসতে পারে।
                        </p>
                    </div>
                </form>
            </div>

            <aside class="fn-aside">
                <div class="fn-co-card fn-summary">
                <h3>অর্ডার সারাংশ</h3>

                <ul class="fn-lines">
                    @foreach ($lines as $line)
                        @php
                            $imagePath = $line['variant']?->primaryImagePath() ?: $line['product']->primaryImagePath();
                            $imageUrl = $imagePath ? asset('storage/'.$imagePath) : null;
                            $optionLabel = $line['variation_name']
                                ?? implode(' / ', array_filter([$line['color'] ?? null, $line['size'] ?? null]));
                        @endphp
                        <li class="fn-line">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="">
                            @else
                                <span class="fn-ph">{{ mb_substr($line['product']->name, 0, 1) }}</span>
                            @endif
                            <div>
                                <strong>{{ $line['product']->name }}</strong>
                                <em>{{ $optionLabel !== '' ? $optionLabel.' · ' : '' }}পরিমাণ {{ $line['qty'] }}</em>
                            </div>
                            <b>{{ Money::format($line['line_total']) }}</b>
                        </li>
                    @endforeach
                </ul>

                @php
                    $firstCharge = $shippingCharges->first();
                    $firstAreaOverride = $firstCharge && isset($cart) ? $cart->deliveryChargeForArea($firstCharge->id) : null;
                    $initialShipping = $firstAreaOverride !== null ? $firstAreaOverride : ($customDeliveryCharge !== null ? $customDeliveryCharge : ($firstCharge?->amount ?? '0.00'));
                    $initialTotal = bcadd($subtotal, (string) $initialShipping, 2);
                @endphp

                <div class="fn-totals">
                    <div>
                        <span>সাবটোটাল</span>
                        <span>{{ Money::format($subtotal) }}</span>
                    </div>
                    <div>
                        <span>ডেলিভারি চার্জ</span>
                        <span id="checkout-shipping-val">{{ (float) $initialShipping == 0 ? 'ফ্রি' : Money::format($initialShipping) }}</span>
                    </div>
                    <div class="fn-due">
                        <span>সর্বমোট</span>
                        <span id="checkout-total-val">{{ Money::format($initialTotal) }}</span>
                    </div>
                </div>

                <div class="fn-trust">
                    <strong>পণ্য দেখে টাকা দিন</strong>
                    <span>পার্সেল হাতে পেয়ে যাচাই করে মূল্য পরিশোধ। অগ্রিম পেমেন্ট নেই।</span>
                </div>
                </div>
            </aside>
        </div>
        </div>
    </section>

    <script>
    (() => {
        const shippingSelect = document.getElementById('shipping_charge_id');
        const shippingEl = document.getElementById('checkout-shipping-val');
        const totalEl = document.getElementById('checkout-total-val');
        const subtotal = Number(@json($subtotal));

        const recalculateShipping = () => {
            const opt = shippingSelect?.selectedOptions?.[0];
            const amount = opt ? Number(opt.dataset.amount || 0) : 0;
            if (shippingEl) {
                shippingEl.textContent = amount === 0 ? 'ফ্রি' : ('৳' + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
            }
            if (totalEl) {
                totalEl.textContent = '৳' + (subtotal + amount).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        };

        shippingSelect?.addEventListener('change', recalculateShipping);
        recalculateShipping();

        const progressUrl = @json(route('checkout.incomplete'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const checkoutForm = document.getElementById('checkout-form');
        const nameInput = document.getElementById('customer_name');
        const phoneInput = document.getElementById('customer_phone');
        const addressInput = document.getElementById('customer_address');
        const fields = [nameInput, phoneInput, addressInput].filter(Boolean);

        const STORAGE_KEY = 'foodness_saved_customer_info';

        // 1. Auto-collect saved address from browser storage
        const loadBrowserSavedInfo = () => {
            try {
                const savedRaw = localStorage.getItem(STORAGE_KEY);
                if (!savedRaw) return;
                const saved = JSON.parse(savedRaw);
                if (saved && typeof saved === 'object') {
                    if (nameInput && !nameInput.value.trim() && saved.name) {
                        nameInput.value = saved.name;
                    }
                    if (phoneInput && !phoneInput.value.trim() && saved.phone) {
                        phoneInput.value = saved.phone;
                    }
                    if (addressInput && !addressInput.value.trim() && saved.address) {
                        addressInput.value = saved.address;
                    }
                }
            } catch (e) {}
        };

        const saveToBrowserStorage = () => {
            try {
                const info = {
                    name: nameInput?.value?.trim() || '',
                    phone: phoneInput?.value?.trim() || '',
                    address: addressInput?.value?.trim() || '',
                };
                if (info.name || info.phone || info.address) {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(info));
                }
            } catch (e) {}
        };

        // 2. Phone normalization and 11-digit completeness check
        const cleanPhone = (val) => {
            let digits = (val || '').replace(/\D/g, '');
            if (digits.startsWith('8801') && digits.length === 13) {
                digits = digits.slice(2);
            }
            return digits;
        };

        const isComplete11DigitPhone = (val) => {
            const digits = cleanPhone(val);
            return digits.length === 11 && digits.startsWith('01');
        };

        if (phoneInput) {
            phoneInput.addEventListener('input', () => {
                let digits = cleanPhone(phoneInput.value);
                if (digits.length > 11) {
                    digits = digits.slice(0, 11);
                }
                // Auto-format only if input had non-digit characters or country prefix
                if (phoneInput.value.startsWith('+88') || phoneInput.value.startsWith('88') || /\D/.test(phoneInput.value)) {
                    phoneInput.value = digits;
                }
            });
        }

        // 3. Save progress for incomplete orders (STRICT: ONLY if phone has full 11 digits)
        let timer = null;

        const saveProgress = () => {
            saveToBrowserStorage();

            if (!csrf || !phoneInput) {
                return;
            }

            const currentPhone = cleanPhone(phoneInput.value);

            // Incomplete order phone number MUST be full complete 11 digits
            if (!isComplete11DigitPhone(currentPhone)) {
                return;
            }

            const body = new FormData();
            body.append('customer_name', nameInput?.value?.trim() || '');
            body.append('customer_phone', currentPhone);
            body.append('customer_address', addressInput?.value?.trim() || '');

            fetch(progressUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
                credentials: 'same-origin',
            }).catch(() => {});
        };

        const schedule = () => {
            clearTimeout(timer);
            timer = setTimeout(saveProgress, 400);
        };

        fields.forEach((field) => {
            field.addEventListener('input', schedule);
            field.addEventListener('change', schedule);
        });

        // Check Order OTP Status & Integration
        const otpStatusUrl = @json(route('checkout.otp.status'));
        const otpSendUrl = @json(route('checkout.otp.send'));
        const otpVerifyUrl = @json(route('checkout.otp.verify'));

        let isOtpRequired = false;
        let isPhoneVerified = false;
        let verifiedPhoneNum = '';

        const sendOtpBtn = document.getElementById('send-otp-btn');
        const otpContainer = document.getElementById('checkout-otp-container');
        const otpInput = document.getElementById('order_otp_input');
        const verifyOtpBtn = document.getElementById('verify-otp-btn');
        const otpMsgBox = document.getElementById('otp-message-box');
        const otpBadge = document.getElementById('otp-verified-badge');
        const timerLabel = document.getElementById('otp-timer-label');

        fetch(otpStatusUrl, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (data.required) {
                    isOtpRequired = true;
                    if (phoneInput && isComplete11DigitPhone(cleanPhone(phoneInput.value))) {
                        sendOtpBtn?.classList.remove('hidden');
                    }
                }
            })
            .catch(() => {});

        phoneInput?.addEventListener('input', () => {
            const current = cleanPhone(phoneInput.value);
            if (isOtpRequired) {
                if (isComplete11DigitPhone(current)) {
                    if (isPhoneVerified && current === verifiedPhoneNum) {
                        sendOtpBtn?.classList.add('hidden');
                    } else {
                        isPhoneVerified = false;
                        sendOtpBtn?.classList.remove('hidden');
                        otpBadge?.classList.add('hidden');
                    }
                } else {
                    sendOtpBtn?.classList.add('hidden');
                    otpContainer?.classList.add('hidden');
                }
            }
        });

        let countdownInterval = null;
        const startOtpTimer = (sec) => {
            clearInterval(countdownInterval);
            let left = sec;
            sendOtpBtn.disabled = true;
            countdownInterval = setInterval(() => {
                left--;
                if (left <= 0) {
                    clearInterval(countdownInterval);
                    timerLabel.textContent = '';
                    sendOtpBtn.disabled = false;
                    sendOtpBtn.textContent = 'Resend OTP';
                } else {
                    timerLabel.textContent = `পুনরায় পাঠান ${left}s`;
                }
            }, 1000);
        };

        sendOtpBtn?.addEventListener('click', async () => {
            const phone = cleanPhone(phoneInput?.value || '');
            if (!isComplete11DigitPhone(phone)) {
                window.showToast('সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন।', 'warning');
                return;
            }

            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'পাঠানো হচ্ছে...';
            otpMsgBox.className = 'mt-2 text-xs font-medium text-emerald-800';
            otpMsgBox.textContent = 'ওটিপি কোড পাঠানো হচ্ছে...';
            otpMsgBox.classList.remove('hidden');

            try {
                const res = await fetch(otpSendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ phone }),
                });
                const data = await res.json();
                if (res.ok && data.ok) {
                    otpContainer.classList.remove('hidden');
                    otpMsgBox.className = 'mt-2 text-xs font-semibold text-emerald-700';
                    otpMsgBox.textContent = data.message;
                    startOtpTimer(45);
                    otpInput?.focus();
                } else {
                    sendOtpBtn.disabled = false;
                    sendOtpBtn.textContent = 'Send OTP';
                    otpMsgBox.className = 'mt-2 text-xs font-semibold text-red-600';
                    otpMsgBox.textContent = data.message || 'ওটিপি পাঠানো যায়নি।';
                }
            } catch (err) {
                sendOtpBtn.disabled = false;
                sendOtpBtn.textContent = 'Send OTP';
                otpMsgBox.className = 'mt-2 text-xs font-semibold text-red-600';
                otpMsgBox.textContent = 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।';
            }
        });

        verifyOtpBtn?.addEventListener('click', async () => {
            const phone = cleanPhone(phoneInput?.value || '');
            const otp = otpInput?.value?.trim() || '';
            if (otp.length !== 6) {
                otpMsgBox.className = 'mt-2 text-xs font-semibold text-red-600';
                otpMsgBox.textContent = '৬ ডিজিটের ওটিপি কোডটি লিখুন।';
                otpMsgBox.classList.remove('hidden');
                return;
            }

            verifyOtpBtn.disabled = true;
            verifyOtpBtn.textContent = 'যাচাই হচ্ছে...';

            try {
                const res = await fetch(otpVerifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ phone, otp }),
                });
                const data = await res.json();
                if (res.ok && data.ok) {
                    isPhoneVerified = true;
                    verifiedPhoneNum = phone;
                    otpBadge?.classList.remove('hidden');
                    sendOtpBtn?.classList.add('hidden');
                    otpMsgBox.className = 'mt-2 text-xs font-bold text-emerald-700';
                    otpMsgBox.textContent = '✓ ' + data.message;
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.textContent = 'যাচাই সম্পন্ন';
                    verifyOtpBtn.classList.replace('bg-emerald-700', 'bg-emerald-600');
                } else {
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.textContent = 'যাচাই করুন';
                    otpMsgBox.className = 'mt-2 text-xs font-semibold text-red-600';
                    otpMsgBox.textContent = data.message || 'ভুল ওটিপি!';
                }
            } catch (err) {
                verifyOtpBtn.disabled = false;
                verifyOtpBtn.textContent = 'যাচাই করুন';
                otpMsgBox.className = 'mt-2 text-xs font-semibold text-red-600';
                otpMsgBox.textContent = 'নেটওয়ার্ক সমস্যা।';
            }
        });

        checkoutForm?.addEventListener('submit', (e) => {
            saveToBrowserStorage();
            if (isOtpRequired && !isPhoneVerified) {
                e.preventDefault();
                otpContainer?.classList.remove('hidden');
                otpInput?.focus();
                window.showToast('অর্ডার সম্পন্ন করতে অনুগ্রহ করে মোবাইল নম্বরে পাওয়া OTP কোডটি দিয়ে যাচাই (Verify) করুন।', 'warning');
                if (sendOtpBtn && !sendOtpBtn.disabled) {
                    sendOtpBtn.click();
                }
            }
        });

        // Initialize: auto-fill from browser saved address
        loadBrowserSavedInfo();

        if (phoneInput && isComplete11DigitPhone(phoneInput.value)) {
            schedule();
        }

        // After browser autofill kicks in (e.g. Chrome autofills upon user interaction or delay)
        setTimeout(() => {
            saveToBrowserStorage();
            if (phoneInput && isComplete11DigitPhone(phoneInput.value)) {
                saveProgress();
            }
        }, 500);
    })();
    </script>

    @php
        $cartContentIds = $lines->map(fn($line) => (string) $line['product']->id)->unique()->values()->all();
        $cartEcomItems = $lines->map(fn($line) => [
            'item_id' => (string) $line['product']->id,
            'item_name' => $line['product']->name,
            'item_category' => $line['product']->category?->name ?? '',
            'price' => (float) ($line['price'] ?? $line['product']->price),
            'quantity' => (int) $line['qty'],
        ])->values()->all();
    @endphp

    <script>
    (() => {
        const checkoutValue = {{ (float) $subtotal }};
        const contentIds = @json($cartContentIds);
        const items = @json($cartEcomItems);
        const numItems = {{ $cart->count() }};

        const eventId = @json($trackedEvent['event_id'] ?? ('ic_'.\Illuminate\Support\Str::uuid()->toString()));

        // Meta Pixel InitiateCheckout
        if (typeof fbq === 'function') {
            fbq('track', 'InitiateCheckout', {
                content_ids: contentIds,
                content_type: 'product',
                value: checkoutValue,
                currency: 'BDT',
                num_items: numItems
            }, {
                eventID: eventId
            });
        }

        // TikTok Pixel InitiateCheckout
        if (typeof ttq === 'object' && typeof ttq.track === 'function') {
            ttq.track('InitiateCheckout', {
                contents: items,
                value: checkoutValue,
                currency: 'BDT'
            }, {
                event_id: eventId
            });
        }

        // Google dataLayer begin_checkout
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: 'begin_checkout',
            event_id: eventId,
            ecommerce: {
                currency: 'BDT',
                value: checkoutValue,
                items: items
            }
        });
    })();
    </script>
@endsection
