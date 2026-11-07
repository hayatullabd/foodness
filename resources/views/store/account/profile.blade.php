@extends('layouts.store')

@section('title', 'প্রোফাইল সেটিংস — '.($store['store_name'] ?? 'Foodness'))

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Unified Customer Account Navigation --}}
        @include('store.account._nav')

        <div class="mt-8 grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            {{-- Left Column: Personal Info & Address --}}
            <div class="space-y-6">
                {{-- Personal Details Form --}}
                <div class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm">
                    <div class="border-b border-ink/8 pb-4">
                        <h2 class="font-display text-lg font-bold text-ink flex items-center gap-2">
                            <svg class="h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>ব্যক্তিগত তথ্য ও ডেলিভারি ঠিকানা</span>
                        </h2>
                        <p class="mt-1 text-xs text-muted">আপনার নাম, ফোন নম্বর এবং নিয়মিত ডেলিভারি ঠিকানা আপডেট করুন। চেকআউটের সময় এগুলো স্বয়ংক্রিয়ভাবে ব্যবহার হবে।</p>
                    </div>

                    <form method="POST" action="{{ route('account.profile.update') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">পুরো নাম *</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                required
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                placeholder="e.g. মোঃ সাকিব হাসান"
                            >
                            @error('name') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="email" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">ইমেইল অ্যাড্রেস * <span class="font-normal text-[11px] text-muted/80">(অথবা ফোন দিন)</span></label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email', $user->email) }}"
                                    class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                    placeholder="yourname@example.com"
                                >
                                @error('email') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="phone" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">ফোন নম্বর * <span class="font-normal text-[11px] text-muted/80">(অথবা ইমেইল দিন)</span></label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="text"
                                    value="{{ old('phone', $user->phone) }}"
                                    class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                    placeholder="01XXXXXXXXX"
                                >
                                @error('phone') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">নিয়মিত ডেলিভারি ঠিকানা <span class="font-normal lowercase text-muted/70">(ঐচ্ছিক)</span></label>
                            <textarea
                                id="address"
                                name="address"
                                rows="3"
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                placeholder="বাড়ি/ফ্ল্যাট নম্বর, রোড, এলাকা, জেলা"
                            >{{ old('address', $user->address) }}</textarea>
                            @error('address') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-accent-deep cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>তথ্য সংরক্ষণ করুন</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Right Column: Security / Password Change & Account Info --}}
            <div class="space-y-6">
                {{-- Password Change Form --}}
                <div class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm">
                    <div class="border-b border-ink/8 pb-4">
                        <h2 class="font-display text-lg font-bold text-ink flex items-center gap-2">
                            <svg class="h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>নিরাপত্তা ও পাসওয়ার্ড পরিবর্তন</span>
                        </h2>
                        <p class="mt-1 text-xs text-muted">আপনার বর্তমান পাসওয়ার্ড দিয়ে নতুন পাসওয়ার্ড সেট করুন।</p>
                    </div>

                    <form method="POST" action="{{ route('account.password.update') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">বর্তমান পাসওয়ার্ড *</label>
                            <input
                                id="current_password"
                                name="current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                placeholder="••••••••"
                            >
                            @error('current_password') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">নতুন পাসওয়ার্ড *</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                placeholder="কমপক্ষে ৮টি অক্ষর"
                            >
                            @error('password') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">নতুন পাসওয়ার্ড নিশ্চিত করুন *</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                                placeholder="••••••••"
                            >
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-accent-deep cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>পাসওয়ার্ড পরিবর্তন করুন</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Account Overview Box --}}
                <div class="rounded-2xl border border-ink/8 bg-canvas/60 p-5 text-xs space-y-2.5">
                    <span class="font-bold uppercase tracking-wider text-muted block">অ্যাকাউন্ট ওভারভিউ</span>
                    <div class="flex justify-between border-b border-ink/5 pb-2">
                        <span class="text-muted">কাস্টমার আইডি</span>
                        <span class="font-mono font-bold text-ink">#{{ $user->id }}</span>
                    </div>
                    <div class="flex justify-between border-b border-ink/5 pb-2">
                        <span class="text-muted">অ্যাকাউন্ট রোল</span>
                        <span class="font-bold text-ink capitalize">{{ $user->role ?? 'customer' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-ink/5 pb-2">
                        <span class="text-muted">যোগদানের সময়</span>
                        <span class="text-ink">{{ $user->created_at?->format('d M, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">মোট সফল ডেলিভারি</span>
                        <span class="font-bold text-emerald-700">{{ $stats['delivered'] ?? 0 }} টি</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
