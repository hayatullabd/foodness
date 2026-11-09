@php
    $user = auth()->user();
    $roleName = match(strtolower($user->role ?? 'user')) {
        'admin', 'super_admin' => 'অ্যাডমিন',
        'manager' => 'ম্যানেজার',
        'staff' => 'স্টাফ',
        default => 'সম্মানিত কাস্টমার',
    };
@endphp

<div class="border-b border-ink/8 pb-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-3.5">
            <div class="flex h-12 w-12 sm:h-14 sm:w-14 shrink-0 items-center justify-center rounded-2xl bg-accent/10 text-accent font-display font-extrabold text-base sm:text-lg border border-accent/20 shadow-xs">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-display text-xl sm:text-3xl font-extrabold text-ink">{{ $user->name }}</h1>
                    <span @class([
                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' => in_array(strtolower($user->role ?? 'user'), ['user', 'customer']),
                        'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20' => in_array(strtolower($user->role ?? ''), ['admin', 'super_admin', 'manager', 'staff']),
                    ])>
                        <span class="h-1.5 w-1.5 rounded-full {{ in_array(strtolower($user->role ?? 'user'), ['user', 'customer']) ? 'bg-emerald-500' : 'bg-purple-500' }}"></span>
                        {{ $roleName }}
                    </span>
                    @if (in_array(strtolower($user->role ?? ''), ['admin', 'super_admin']) || $user->can('access-admin'))
                        <a href="{{ route('admin.dashboard') }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white hover:bg-slate-800 transition">
                            অ্যাডমিন প্যানেল &rarr;
                        </a>
                    @endif
                </div>
                <p class="mt-1 text-xs text-muted flex flex-wrap items-center gap-2">
                    @if ($user->email)
                        <span>{{ $user->email }}</span>
                    @endif
                    @if ($user->phone)
                        <span>{{ $user->email ? '· ' : '' }}{{ $user->phone }}</span>
                    @endif
                    <span>· সদস্য: {{ $user->created_at?->format('d M, Y') ?? 'N/A' }}</span>
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-ink/15 px-3.5 py-2 text-xs font-bold text-ink hover:bg-mist transition cursor-pointer">
                <svg class="h-3.5 w-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>সাইন আউট</span>
            </button>
        </form>
    </div>

    {{-- Account Navigation Tabs --}}
    <div class="mt-6 flex items-center gap-2 overflow-x-auto no-scrollbar">
        <a
            href="{{ route('account.index') }}"
            @class([
                'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition shrink-0',
                'bg-accent text-white shadow-xs' => request()->routeIs('account.index') || request()->routeIs('account.orders.*'),
                'bg-white border border-ink/10 text-ink hover:bg-mist/50' => !(request()->routeIs('account.index') || request()->routeIs('account.orders.*')),
            ])
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span>আমার অর্ডারসমূহ</span>
        </a>

        <a
            href="{{ route('account.profile') }}"
            @class([
                'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition shrink-0',
                'bg-accent text-white shadow-xs' => request()->routeIs('account.profile'),
                'bg-white border border-ink/10 text-ink hover:bg-mist/50' => !request()->routeIs('account.profile'),
            ])
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>প্রোফাইল সেটিংস</span>
        </a>
    </div>
</div>

{{-- Flash Alerts --}}
@if (session('status') || session('success'))
    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/90 p-3.5 text-xs font-semibold text-emerald-800 flex items-center gap-2 shadow-2xs">
        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ session('status') ?? session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50/90 p-3.5 text-xs font-semibold text-rose-800 flex items-center gap-2 shadow-2xs">
        <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{{ session('error') }}</span>
    </div>
@endif
