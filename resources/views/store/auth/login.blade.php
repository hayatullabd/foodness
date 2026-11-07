@extends('layouts.store')

@section('title', ($store['login_title'] ?? 'লগইন').' — '.($store['store_name'] ?? 'Foodness'))

@section('content')
    @php
        $socialProviders = $socialProviders ?? ['facebook' => false, 'google' => false];
        $hasSocial = ($socialProviders['facebook'] ?? false) || ($socialProviders['google'] ?? false);
    @endphp

    <section class="store-auth store-container py-12 sm:py-16">
        <div class="mx-auto max-w-md rounded-2xl border border-ink/8 bg-white p-6 sm:p-8 shadow-sm">
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-ink">{{ $store['login_title'] ?? 'স্বাগতম' }}</h1>
            <p class="mt-1.5 text-xs sm:text-sm text-muted">{{ $store['login_subtitle'] ?? 'আপনার অর্ডার ট্র্যাক করতে এবং অ্যাকাউন্ট পরিচালনা করতে লগইন করুন।' }}</p>

            @if ($hasSocial)
                <div class="mt-6 space-y-2.5">
                    @if ($socialProviders['google'] ?? false)
                        <a
                            href="{{ route('customer.auth.provider.redirect', 'google') }}"
                            class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl border border-ink/15 bg-white px-4 py-2.5 text-xs font-bold text-ink transition hover:bg-mist shadow-xs"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Continue with Google
                        </a>
                    @endif
                    @if ($socialProviders['facebook'] ?? false)
                        <a
                            href="{{ route('customer.auth.provider.redirect', 'facebook') }}"
                            class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl border border-ink/15 bg-[#1877F2] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-[#166FE5] shadow-xs"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12.07C22 6.48 17.52 2 11.93 2S1.86 6.48 1.86 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.02H7.9v-2.91h2.4V9.84c0-2.37 1.4-3.69 3.56-3.69 1.03 0 2.11.19 2.11.19v2.32h-1.19c-1.17 0-1.54.73-1.54 1.48v1.78h2.62l-.42 2.91h-2.2V22c4.78-.75 8.44-4.91 8.44-9.93z"/></svg>
                            Continue with Facebook
                        </a>
                    @endif
                </div>

                <div class="my-6 flex items-center gap-3 text-[11px] uppercase tracking-wider text-muted">
                    <span class="h-px flex-1 bg-ink/10"></span>
                    <span>অথবা ইমেইল / ফোন দিয়ে লগইন</span>
                    <span class="h-px flex-1 bg-ink/10"></span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="{{ $hasSocial ? '' : 'mt-6 ' }}space-y-4">
                @csrf
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-muted">
                            ইমেইল অথবা ফোন নম্বর
                        </label>
                        <span id="loginDetectedBadge" class="hidden text-[11px] font-bold px-2 py-0.5 rounded-full transition-all"></span>
                    </div>
                    <div class="relative">
                        <input
                            id="email"
                            type="text"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="yourname@example.com অথবা 01XXXXXXXXX"
                            required
                            autofocus
                            class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent pr-10"
                        >
                        <div id="loginDetectedIcon" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                    </div>
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-bold uppercase tracking-wider text-muted">পাসওয়ার্ড</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        class="w-full rounded-xl border border-ink/15 bg-white px-3.5 py-2.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-muted">
                        <input type="checkbox" name="remember" value="1" class="rounded text-accent focus:ring-accent">
                        <span>আমাকে মনে রাখুন</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-accent-deep"
                >
                    লগইন করুন
                </button>
            </form>

            <div class="mt-6 border-t border-ink/8 pt-4 text-center text-xs text-muted">
                কোনো অ্যাকাউন্ট নেই?
                <a href="{{ route('register') }}" class="font-bold text-accent hover:underline">নতুন অ্যাকাউন্ট তৈরি করুন</a>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const input = document.getElementById('email');
            const badge = document.getElementById('loginDetectedBadge');
            const iconContainer = document.getElementById('loginDetectedIcon');

            if (!input || !badge || !iconContainer) return;

            const phoneIcon = '<svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
            const emailIcon = '<svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
            const defaultIcon = '<svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>';

            function updateDetection() {
                const val = input.value.trim();
                if (!val) {
                    badge.className = 'hidden';
                    badge.textContent = '';
                    iconContainer.innerHTML = defaultIcon;
                    return;
                }

                if (val.includes('@') || /[a-zA-Z]/.test(val)) {
                    badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20';
                    badge.innerHTML = '✉️ ইমেইল';
                    iconContainer.innerHTML = emailIcon;
                } else if (/^[0-9+() -]+$/.test(val)) {
                    badge.className = 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                    badge.innerHTML = '📱 ফোন নম্বর';
                    iconContainer.innerHTML = phoneIcon;
                } else {
                    badge.className = 'hidden';
                    iconContainer.innerHTML = defaultIcon;
                }
            }

            input.addEventListener('input', updateDetection);
            updateDetection();
        })();
    </script>
@endsection
