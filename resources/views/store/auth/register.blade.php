@extends('layouts.store')

@section('title', ($store['register_title'] ?? 'অ্যাকাউন্ট তৈরি করুন').' — '.($store['store_name'] ?? 'Foodness'))

@section('content')
    <section class="store-auth store-container py-12 sm:py-16">
        <div class="mx-auto max-w-md rounded-2xl border border-ink/8 bg-white p-6 sm:p-8 shadow-sm">
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-ink">{{ $store['register_title'] ?? 'নতুন অ্যাকাউন্ট তৈরি করুন' }}</h1>
            <p class="mt-1.5 text-xs sm:text-sm text-muted">{{ $store['register_subtitle'] ?? (($store['store_name'] ?? 'Foodness').'-এ অ্যাকাউন্ট তৈরি করে আপনার ক্যাশ অন ডেলিভারি (COD) অর্ডার সহজে ট্র্যাক করুন ও দ্রুত কেনাকাটা সম্পন্ন করুন।') }}</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">পুরো নাম *</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="e.g. মোঃ সাকিব হাসান"
                        required
                        autofocus
                        class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Unified Email & Phone Field with Live Auto-Detection --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="email_or_phone" class="block text-xs font-bold uppercase tracking-wider text-muted">
                            ইমেইল অথবা ফোন নম্বর *
                        </label>
                        <span id="detectedBadge" class="hidden text-[11px] font-bold px-2 py-0.5 rounded-full transition-all"></span>
                    </div>

                    <div class="relative">
                        <input
                            id="email_or_phone"
                            type="text"
                            name="email_or_phone"
                            value="{{ old('email_or_phone', old('email', old('phone'))) }}"
                            placeholder="01XXXXXXXXX অথবা yourname@example.com"
                            required
                            class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent pr-10"
                        >
                        <div id="detectedIcon" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                    </div>

                    <p id="detectedHelper" class="mt-1 text-[11px] text-muted">
                        ফোন নম্বর লিখলে ফোন এবং ইমেইল লিখলে স্বয়ংক্রিয়ভাবে ইমেইল হিসেবে অ্যাকাউন্ট তৈরি হবে।
                    </p>

                    @error('email_or_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="address" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">ডেলিভারির ঠিকানা <span class="font-normal lowercase text-muted/70">(ঐচ্ছিক)</span></label>
                    <textarea
                        id="address"
                        name="address"
                        rows="2"
                        placeholder="আপনার নিয়মিত ডেলিভারি ঠিকানা"
                        class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >{{ old('address') }}</textarea>
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">পাসওয়ার্ড *</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">পাসওয়ার্ড নিশ্চিত করুন *</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="••••••••"
                        required
                        class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                </div>

                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-accent-deep"
                >
                    অ্যাকাউন্ট তৈরি করুন
                </button>
            </form>

            <div class="mt-6 border-t border-ink/8 pt-4 text-center text-xs text-muted">
                ইতিমধ্যে অ্যাকাউন্ট আছে?
                <a href="{{ route('login') }}" class="font-bold text-accent hover:underline">লগইন করুন</a>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const input = document.getElementById('email_or_phone');
            const badge = document.getElementById('detectedBadge');
            const helper = document.getElementById('detectedHelper');
            const iconContainer = document.getElementById('detectedIcon');

            if (!input || !badge || !helper || !iconContainer) return;

            const phoneIcon = '<svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
            const emailIcon = '<svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
            const defaultIcon = '<svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>';

            function updateDetection() {
                const val = input.value.trim();
                if (!val) {
                    badge.className = 'hidden';
                    badge.textContent = '';
                    helper.className = 'mt-1 text-[11px] text-muted';
                    helper.textContent = 'ফোন নম্বর লিখলে ফোন এবং ইমেইল লিখলে স্বয়ংক্রিয়ভাবে ইমেইল হিসেবে অ্যাকাউন্ট তৈরি হবে।';
                    iconContainer.innerHTML = defaultIcon;
                    return;
                }

                const isEmailCandidate = val.includes('@') || /[a-zA-Z]/.test(val);

                if (isEmailCandidate) {
                    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (emailRegex.test(val)) {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                        badge.innerHTML = '✓ সঠিক ইমেইল অ্যাড্রেস';
                        helper.className = 'mt-1 text-[11px] text-emerald-700 font-medium';
                        helper.textContent = 'অ্যাকাউন্টের যোগাযোগ মাধ্যম হিসেবে এই ইমেইল সংরক্ষিত হবে।';
                        iconContainer.innerHTML = emailIcon;
                    } else {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20';
                        badge.innerHTML = '✉️ ইমেইল টাইপ করছেন...';
                        helper.className = 'mt-1 text-[11px] text-indigo-700 font-medium';
                        helper.textContent = 'সঠিক ইমেইল ফরম্যাট দিন (যেমন: name@example.com)';
                        iconContainer.innerHTML = emailIcon;
                    }
                } else if (/^[0-9+() -]+$/.test(val)) {
                    const digits = val.replace(/\D/g, '');
                    let cleanPhone = digits;
                    if (cleanPhone.startsWith('880')) {
                        cleanPhone = cleanPhone.substring(2);
                    } else if (cleanPhone.startsWith('88') && cleanPhone.length > 11) {
                        cleanPhone = cleanPhone.substring(2);
                        if (!cleanPhone.startsWith('0')) cleanPhone = '0' + cleanPhone;
                    }

                    const bdPhoneRegex = /^01[3-9]\d{8}$/;

                    if (cleanPhone.length > 11) {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 ring-1 ring-red-600/20';
                        badge.innerHTML = `⚠️ ১১ ডিজিটের বেশি (${cleanPhone.length} ডিজিট)`;
                        helper.className = 'mt-1 text-[11px] text-red-700 font-medium';
                        helper.textContent = `মোবাইল নম্বর কান্ট্রি কোড বাদে ঠিক ১১ ডিজিট (01XXXXXXXXX) হতে হবে। আপনি অতিরিক্ত ${cleanPhone.length - 11} ডিজিট লিখেছেন।`;
                        iconContainer.innerHTML = phoneIcon;
                    } else if (bdPhoneRegex.test(cleanPhone)) {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                        badge.innerHTML = '✓ সঠিক ১১ ডিজিট মোবাইল নম্বর';
                        helper.className = 'mt-1 text-[11px] text-emerald-700 font-medium';
                        helper.textContent = 'অ্যাকাউন্টের যোগাযোগ মাধ্যম হিসেবে এই ফোন নম্বর সংরক্ষিত হবে।';
                        iconContainer.innerHTML = phoneIcon;
                    } else if (cleanPhone.startsWith('01') && cleanPhone.length < 11) {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
                        badge.innerHTML = `📱 ফোন (${cleanPhone.length}/১১ ডিজিট)`;
                        helper.className = 'mt-1 text-[11px] text-amber-700 font-medium';
                        helper.textContent = '১১ ডিজিটের মোবাইল নম্বর সম্পূর্ণ করুন (যেমন: 017XXXXXXXX)';
                        iconContainer.innerHTML = phoneIcon;
                    } else if (cleanPhone.length >= 3 && !/^01[3-9]/.test(cleanPhone)) {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 ring-1 ring-red-600/20';
                        badge.innerHTML = '⚠️ ভুল মোবাইল কোড';
                        helper.className = 'mt-1 text-[11px] text-red-700 font-medium';
                        helper.textContent = 'মোবাইল নম্বর 013, 014, 015, 016, 017, 018 বা 019 দিয়ে শুরু হতে হবে।';
                        iconContainer.innerHTML = phoneIcon;
                    } else {
                        badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
                        badge.innerHTML = '📱 ফোন নম্বর';
                        helper.className = 'mt-1 text-[11px] text-amber-700 font-medium';
                        helper.textContent = 'সঠিক ১১ ডিজিটের মোবাইল নম্বর লিখুন (যেমন: 017XXXXXXXX)';
                        iconContainer.innerHTML = phoneIcon;
                    }
                } else {
                    badge.className = 'hidden';
                    helper.className = 'mt-1 text-[11px] text-amber-700';
                    helper.textContent = 'সঠিক ইমেইল (name@example.com) অথবা ফোন (01XXXXXXXXX) লিখুন।';
                    iconContainer.innerHTML = defaultIcon;
                }
            }

            input.addEventListener('input', updateDetection);
            updateDetection();

            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const val = input.value.trim();
                    if (!val) return;

                    const isEmailCandidate = val.includes('@') || /[a-zA-Z]/.test(val);
                    if (isEmailCandidate) {
                        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!emailRegex.test(val)) {
                            e.preventDefault();
                            input.focus();
                            window.showToast('দয়া করে একটি সঠিক ও কার্যকর ইমেইল অ্যাড্রেস লিখুন (যেমন: name@example.com)।', 'warning');
                            return false;
                        }
                    } else {
                        const digits = val.replace(/\D/g, '');
                        let clean = digits;
                        if (clean.startsWith('880')) {
                            clean = clean.substring(2);
                        } else if (clean.startsWith('88') && clean.length > 11) {
                            clean = clean.substring(2);
                            if (!clean.startsWith('0')) clean = '0' + clean;
                        }

                        if (clean.startsWith('01') || digits.startsWith('880') || digits.startsWith('881')) {
                            if (clean.length !== 11) {
                                e.preventDefault();
                                input.focus();
                                window.showToast(`মোবাইল নম্বর কান্ট্রি কোড বাদে ঠিক ১১ ডিজিট (যেমন: 017XXXXXXXX) হতে হবে। আপনি ${clean.length} ডিজিট দিয়েছেন।`, 'warning');
                                return false;
                            }
                            if (!/^01[3-9]\d{8}$/.test(clean)) {
                                e.preventDefault();
                                input.focus();
                                window.showToast('মোবাইল নম্বর সঠিক অপারেটর কোড (013-019) দিয়ে শুরু হতে হবে।', 'warning');
                                return false;
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endsection
