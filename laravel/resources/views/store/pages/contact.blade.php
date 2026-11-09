@extends('layouts.store')

@section('title', ($page->meta_title ?? 'যোগাযোগ ও সাপোর্ট').' — '.($store['store_name'] ?? 'Foodness'))
@section('meta_description', $page->meta_description ?? 'Foodness কাস্টমার কেয়ার হেল্পলাইন ও সরাসরি যোগাযোগ।')

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-muted mb-4">
            <a href="{{ route('home') }}" class="hover:text-accent transition">হোম</a>
            <span>/</span>
            <span class="text-ink font-semibold">যোগাযোগ ও সাপোর্ট</span>
        </nav>

        {{-- Page Header Banner --}}
        <div class="rounded-2xl border border-ink/8 bg-gradient-to-r from-canvas via-white to-canvas p-6 sm:p-8 shadow-xs text-center max-w-3xl mx-auto mb-8">
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-accent mb-1.5">সরাসরি সহায়তা</span>
            <h1 class="font-display text-2xl sm:text-4xl font-extrabold text-ink tracking-tight">{{ $page->title ?? 'যোগাযোগ ও সাপোর্ট' }}</h1>
            <p class="mt-2 text-xs sm:text-sm text-muted max-w-xl mx-auto leading-relaxed">
                {{ $page->subtitle ?? 'অর্ডার, ডেলিভারি বা যেকোনো তথ্যের জন্য আমাদের কাস্টমার কেয়ার টিম সপ্তাহের ৭ দিনই প্রস্তুত।' }}
            </p>
        </div>

        {{-- Flash Success Alert --}}
        @if (session('status'))
            <div class="max-w-4xl mx-auto mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-2 shadow-2xs">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="max-w-4xl mx-auto grid gap-8 lg:grid-cols-[0.9fr_1.1fr] items-start">
            {{-- Left Column: Contact Channels --}}
            <div class="space-y-4">
                {{-- Phone / Call Card --}}
                @if (!empty($store['store_phone']))
                    <div class="rounded-2xl border border-ink/8 bg-white p-5 shadow-xs flex items-start gap-4">
                        <div class="h-10 w-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted block">হটলাইন টেলিফোন</span>
                            <a href="tel:{{ $store['store_phone'] }}" class="font-display font-extrabold text-base text-ink hover:text-accent transition">
                                {{ $store['store_phone'] }}
                            </a>
                            <p class="text-xs text-muted mt-0.5">সরাসরি কথা বলতে ট্যাপ করুন</p>
                        </div>
                    </div>
                @endif

                {{-- WhatsApp Card --}}
                @if (!empty($store['floating_whatsapp_number'] ?? ($store['store_phone'] ?? null)))
                    @php
                        $waNum = preg_replace('/[^0-9]/', '', $store['floating_whatsapp_number'] ?? ($store['store_phone'] ?? ''));
                        if (!str_starts_with($waNum, '880') && str_starts_with($waNum, '01')) {
                            $waNum = '880'.substr($waNum, 1);
                        }
                    @endphp
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 shadow-xs flex items-start gap-4">
                        <div class="h-10 w-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.04 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 15 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67Z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 block">হোয়াটসঅ্যাপ চ্যাট</span>
                            <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener noreferrer" class="font-display font-extrabold text-base text-emerald-900 hover:underline">
                                WhatsApp এ মেসেজ দিন &rarr;
                            </a>
                            <p class="text-xs text-emerald-700/80 mt-0.5">২৪/৭ অনলাইন চ্যাট সহায়তা</p>
                        </div>
                    </div>
                @endif

                {{-- Email Card --}}
                @if (!empty($store['store_email']))
                    <div class="rounded-2xl border border-ink/8 bg-white p-5 shadow-xs flex items-start gap-4">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted block">অফিসিয়াল ইমেইল</span>
                            <a href="mailto:{{ $store['store_email'] }}" class="font-semibold text-sm text-ink hover:text-accent transition">
                                {{ $store['store_email'] }}
                            </a>
                            <p class="text-xs text-muted mt-0.5">যেকোনো প্রশ্ন লিখে পাঠাতে পারেন</p>
                        </div>
                    </div>
                @endif

                {{-- Address Card --}}
                @if (!empty($store['store_address']))
                    <div class="rounded-2xl border border-ink/8 bg-white p-5 shadow-xs flex items-start gap-4">
                        <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted block">অফিস ও আউটলেট ঠিকানা</span>
                            <p class="text-xs text-ink/90 leading-relaxed font-medium mt-0.5">{{ $store['store_address'] }}</p>
                        </div>
                    </div>
                @endif

                {{-- Support Hours Box --}}
                <div class="rounded-2xl border border-ink/8 bg-mist/40 p-4 text-xs text-muted flex items-center gap-3">
                    <svg class="h-5 w-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>কাস্টমার সার্ভিস সময়: প্রতিদিন সকাল ৯:০০ টা থেকে রাত ১০:০০ টা পর্যন্ত।</span>
                </div>
            </div>

            {{-- Right Column: Interactive Message Form --}}
            <div class="rounded-2xl border border-ink/8 bg-white p-6 sm:p-7 shadow-xs">
                <div class="border-b border-ink/8 pb-4 mb-5">
                    <h2 class="font-display text-lg font-bold text-ink">সরাসরি বার্তা পাঠান</h2>
                    <p class="text-xs text-muted mt-0.5">নিচের ফর্মটি পূরণ করে পাঠান, আমরা দ্রুত যোগাযোগ করবো।</p>
                </div>

                <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">আপনার পুরো নাম *</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', auth()->user()?->name) }}"
                            required
                            placeholder="e.g. মোঃ সাকিব হাসান"
                            class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                        >
                        @error('name') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="phone" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">মোবাইল নম্বর *</label>
                            <input
                                id="phone"
                                name="phone"
                                type="text"
                                value="{{ old('phone', auth()->user()?->phone) }}"
                                required
                                placeholder="01XXXXXXXXX"
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                            @error('phone') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">ইমেইল <span class="font-normal lowercase text-muted/70">(ঐচ্ছিক)</span></label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', auth()->user()?->email) }}"
                                placeholder="yourname@example.com"
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                            @error('email') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">বিষয় <span class="font-normal lowercase text-muted/70">(ঐচ্ছিক)</span></label>
                        <input
                            id="subject"
                            name="subject"
                            type="text"
                            value="{{ old('subject') }}"
                            placeholder="যেমন: অর্ডার ডেলিভারি, এক্সচেঞ্জ বা অনুসন্ধান"
                            class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                        >
                    </div>

                    <div>
                        <label for="message" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">আপনার বার্তা / জিজ্ঞাসা *</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="4"
                            required
                            placeholder="আপনার প্রশ্ন বা মতামত বিস্তারিত লিখুন..."
                            class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                        >{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-accent px-6 py-3 text-xs font-bold text-white shadow-md transition hover:bg-accent-deep cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span>বার্তা পাঠান</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
