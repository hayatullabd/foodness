<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $adminStoreName = config('app.name', 'Store');
        try {
            $adminStoreName = \App\Support\StoreSettings::get('store_name') ?: $adminStoreName;
        } catch (\Throwable) {
        }
    @endphp
    <title>@yield('title', 'Dashboard') · {{ $adminStoreName }} Admin</title>
    @php
        $adminFavicon = null;
        try {
            $adminFavicon = \App\Support\StoreSettings::get('site_favicon');
        } catch (\Throwable) {
        }
    @endphp
    @if (! empty($adminFavicon) && file_exists(public_path('storage/'.$adminFavicon)))
        <link rel="icon" href="{{ asset('storage/'.$adminFavicon) }}?v={{ filemtime(public_path('storage/'.$adminFavicon)) }}">
        <link rel="shortcut icon" href="{{ asset('storage/'.$adminFavicon) }}?v={{ filemtime(public_path('storage/'.$adminFavicon)) }}">
        <link rel="apple-touch-icon" href="{{ asset('storage/'.$adminFavicon) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}">
    @stack('styles')
</head>
<body class="admin-app min-h-screen bg-canvas font-sans text-ink antialiased">
    @php
        $activeOrderStatuses = \App\Models\OrderStatus::query()
            ->ordersSection()
            ->active()
            ->ordered()
            ->get();

        $allOrderLinks = [];
        foreach ($activeOrderStatuses as $st) {
            if ($st->slug === 'incomplete') {
                continue;
            }

            $allOrderLinks[] = ['label' => $st->name, 'status' => $st->slug, 'tab' => null];
        }
        $allOrderLinks[] = ['label' => 'All Orders', 'status' => 'all', 'tab' => null];

        $activeCourierStatuses = \App\Models\OrderStatus::query()
            ->courierSection()
            ->active()
            ->ordered()
            ->get();

        $courierOrderLinks = [];
        foreach ($activeCourierStatuses as $st) {
            $courierOrderLinks[] = ['label' => $st->name, 'status' => $st->slug];
        }
        $courierOrderLinks[] = ['label' => 'All Courier', 'status' => 'all'];

        $orderStatusLinks = $allOrderLinks;
        $refundStatusLinks = [
            ['label' => 'All Refunds', 'status' => null],
            ['label' => 'Pending Refunds', 'status' => 'pending'],
            ['label' => 'Approved Refunds', 'status' => 'approved'],
            ['label' => 'Processed Refunds', 'status' => 'processed'],
        ];
        $productLinks = [
            ['label' => 'All Products', 'route' => 'admin.products.index', 'params' => []],
            ['label' => 'Pending Products', 'route' => 'admin.products.index', 'params' => ['filter' => 'pending']],
            ['label' => 'Add Product', 'route' => 'admin.products.create', 'params' => []],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'params' => []],
            ['label' => 'Subcategories', 'route' => 'admin.subcategories.index', 'params' => []],
            ['label' => 'Childcategories', 'route' => 'admin.childcategories.index', 'params' => []],
            ['label' => 'Brands', 'route' => 'admin.brands.index', 'params' => []],
            ['label' => 'Attributes', 'route' => 'admin.attributes.index', 'params' => []],
            ['label' => 'Product Page Setup', 'route' => 'admin.product-page.edit', 'params' => []],
        ];
        $currentSection = request('section') ?? ($orderSection ?? null);
        $courierStatusSlugs = ['rts', 'shipped', 'pending_return', 'partial', 'lost'];
        if (! in_array($currentSection, ['orders', 'courier'], true)) {
            $currentSection = request()->filled('courier') || in_array(request('status'), $courierStatusSlugs, true)
                ? 'courier'
                : 'orders';
        }
        $allOrdersOpen = request()->routeIs('admin.orders.*', 'admin.incomplete-orders.*', 'admin.order-statuses.*') && $currentSection === 'orders';
        $courierOrdersOpen = request()->routeIs('admin.orders.*') && $currentSection === 'courier';
        $ordersOpen = $allOrdersOpen || $courierOrdersOpen;
        $refundsOpen = request()->routeIs('admin.refunds.*');
        $fraudSettingsOpen = request()->routeIs('admin.fraud-settings.*', 'admin.fraud-order-rules.*');
        $fraudApiOpen = request()->routeIs('admin.fraud-settings.*');
        $fraudOrderRulesOpen = request()->routeIs('admin.fraud-order-rules.*');
        $apiIntegrationOpen = request()->routeIs('admin.api-integrations.*') || request()->routeIs('admin.ai-providers.*');
        $courierPanelOpen = request()->routeIs('admin.courier-panel.*', 'admin.courier-apis.*');
        $trackingOpen = request()->routeIs('admin.tracking.*');
        $orderRestrictionsOpen = request()->routeIs('admin.order-restrictions.*', 'admin.blocking-logs.*');
        $customizationOpen = request()->routeIs('admin.customization.*', 'admin.product-page.*', 'admin.pages.*', 'admin.landing-pages.*');
        $settingsOpen = request()->routeIs('admin.settings.*', 'admin.email-settings.*', 'admin.seo-settings.*', 'admin.sitemap.*', 'admin.error-log.*', 'admin.cron-jobs.*', 'admin.system-update.*');
        $usersOpen = request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.permissions.*', 'admin.customers.*');
        $productsOpen = request()->routeIs(
            'admin.products.*',
            'admin.categories.*',
            'admin.subcategories.*',
            'admin.childcategories.*',
            'admin.brands.*',
            'admin.colors.*',
            'admin.sizes.*',
            'admin.attributes.*',
        );
        $currentOrderStatus = request()->routeIs('admin.orders.*') ? request('status') : null;
        $currentRefundStatus = request()->routeIs('admin.refunds.*') ? request('status') : null;
        $currentProductFilter = request()->routeIs('admin.products.index') ? request('filter') : null;
        $userLinks = [
            ['label' => 'User', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'permission' => 'users.manage'],
            ['label' => 'Roles', 'route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'permission' => 'roles.manage'],
            ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'match' => 'admin.permissions.*', 'permission' => 'permissions.manage'],
            ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'permission' => 'customers.view'],
        ];
        $links = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
            ['label' => 'Reports & Analytics', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*'],
            ['label' => 'Shipping', 'route' => 'admin.shipping-charges.index', 'match' => 'admin.shipping-charges.*'],
        ];
        $apiIntegrationLinks = [
            ['label' => 'Payment Gateway', 'route' => 'admin.api-integrations.payment-gateway.edit', 'match' => 'admin.api-integrations.payment-gateway.*'],
            ['label' => 'SMS Gateway', 'route' => 'admin.api-integrations.sms-gateway.edit', 'match' => 'admin.api-integrations.sms-gateway.*'],
            ['label' => 'AI Providers', 'route' => 'admin.ai-providers.index', 'match' => 'admin.ai-providers.*|admin.api-integrations.gemini-ai.*'],
            ['label' => 'Social Login', 'route' => 'admin.api-integrations.social-login.edit', 'match' => 'admin.api-integrations.social-login.*'],
        ];
        $settingsLinks = [
            ['label' => 'Store Settings', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*'],
            ['label' => 'Email Settings', 'route' => 'admin.email-settings.edit', 'match' => 'admin.email-settings.*'],
            ['label' => 'SEO Settings', 'route' => 'admin.seo-settings.edit', 'match' => 'admin.seo-settings.*'],
            ['label' => 'Sitemap', 'route' => 'admin.sitemap.edit', 'match' => 'admin.sitemap.*'],
            ['label' => 'Error Log', 'route' => 'admin.error-log.index', 'match' => 'admin.error-log.*'],
            ['label' => 'Cron Jobs', 'route' => 'admin.cron-jobs.index', 'match' => 'admin.cron-jobs.*'],
            ['label' => 'System Update', 'route' => 'admin.system-update.index', 'match' => 'admin.system-update.*', 'permission' => 'settings.manage'],
        ];
        $trackingLinks = [
            ['label' => 'Facebook Pixel', 'route' => 'admin.tracking.facebook.edit', 'match' => 'admin.tracking.facebook.*'],
            ['label' => 'Facebook CAPI', 'route' => 'admin.tracking.facebook-capi.edit', 'match' => 'admin.tracking.facebook-capi.*'],
            ['label' => 'Google Tag Manager', 'route' => 'admin.tracking.google.edit', 'match' => 'admin.tracking.google.*'],
            ['label' => 'TikTok Pixel', 'route' => 'admin.tracking.tiktok.edit', 'match' => 'admin.tracking.tiktok.*'],
        ];
        $storeName = config('app.name', 'Foodness');
        $storeLogo = null;
        try {
            $storeSettings = \App\Support\StoreSettings::all();
            $storeName = $storeSettings['store_name'] ?? $storeName;
            $storeLogo = $storeSettings['store_logo'] ?? null;
        } catch (\Throwable) {
            // Settings table may be unavailable during install.
        }
        $adminUser = auth('admin')->user();
        $adminDisplayName = $adminUser?->name ?: 'Admin';
        $adminInitials = collect(preg_split('/\s+/', trim($adminDisplayName)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
        if ($adminInitials === '') {
            $adminInitials = 'A';
        }

        $topbarNotifications = [];
        $topbarNotificationCount = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('orders')) {
                $pendingOrders = \App\Models\Order::query()
                    ->where('status', 'pending')
                    ->latest('id')
                    ->limit(5)
                    ->get(['id', 'invoice_no', 'customer_name', 'total', 'created_at']);
                $pendingCount = \App\Models\Order::query()->where('status', 'pending')->count();
                foreach ($pendingOrders as $pendingOrder) {
                    $topbarNotifications[] = [
                        'label' => ($pendingOrder->invoice_no ?: 'Order #'.$pendingOrder->id).' · '.($pendingOrder->customer_name ?: 'Customer'),
                        'meta' => 'Pending',
                        'url' => route('admin.orders.show', $pendingOrder),
                    ];
                }
                $topbarNotificationCount += $pendingCount;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('incomplete_orders')) {
                $incompleteOrders = \App\Models\IncompleteOrder::query()
                    ->latest('id')
                    ->limit(5)
                    ->get(['id', 'customer_name', 'customer_phone', 'total', 'created_at']);
                $incompleteCount = \App\Models\IncompleteOrder::query()->count();
                foreach ($incompleteOrders as $incompleteOrder) {
                    $topbarNotifications[] = [
                        'label' => ($incompleteOrder->customer_name ?: 'Guest').' · '.($incompleteOrder->customer_phone ?: 'No phone'),
                        'meta' => 'Incomplete',
                        'url' => route('admin.orders.index', ['tab' => 'incomplete']),
                    ];
                }
                $topbarNotificationCount += $incompleteCount;
            }
            $topbarNotifications = array_slice($topbarNotifications, 0, 8);
        } catch (\Throwable) {
            $topbarNotifications = [];
            $topbarNotificationCount = 0;
        }
    @endphp

    <header class="admin-topbar sticky top-0 z-40">
        <div class="flex h-14 sm:h-16 items-center justify-between gap-3 px-3 sm:px-4">
            <div class="flex min-w-0 items-center gap-2.5 sm:gap-4">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5 py-1 lg:hidden" title="{{ $storeName }} - Dashboard">
                    @if ($storeLogo)
                        <span class="flex h-9 w-auto max-w-[140px] items-center rounded-lg bg-white/95 px-2 py-1">
                            <img
                                src="{{ asset('storage/'.$storeLogo) }}"
                                alt="{{ $storeName }}"
                                class="h-7 w-auto max-w-full object-contain"
                            >
                        </span>
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sage text-xs font-bold tracking-wide text-white shadow-2xs">
                            {{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span class="block truncate font-display text-sm font-semibold tracking-tight text-white">{{ $storeName }}</span>
                            <span class="hidden truncate text-[10px] font-medium text-slate-400 sm:block">Admin panel</span>
                        </span>
                    @endif
                </a>

                <button
                    type="button"
                    class="admin-topbar-icon"
                    data-admin-sidebar-toggle
                    aria-controls="admin-sidebar"
                    aria-expanded="false"
                    aria-label="Toggle sidebar"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <a
                    href="{{ route('home') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-white/15 bg-white/5 px-2.5 py-1 text-xs font-medium text-slate-200 transition hover:bg-white/10 hover:text-white"
                >
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12h19.5M12 2.25c2.485 2.7 3.75 5.85 3.75 9.75S14.485 19.05 12 21.75M12 2.25C9.515 4.95 8.25 8.1 8.25 12s1.265 6.75 3.75 9.75" />
                    </svg>
                    <span class="hidden sm:inline">Visit Site</span>
                </a>
            </div>

            {{-- Global Topbar Search --}}
            <div class="hidden md:flex flex-1 max-w-sm lg:max-w-md mx-4">
                <form action="{{ route('admin.orders.index') }}" method="GET" class="w-full relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="q"
                        placeholder="Search orders, products..."
                        class="w-full pl-9 pr-4 py-1.5 text-xs bg-white/10 hover:bg-white/15 focus:bg-white border border-white/10 focus:border-sage/40 rounded-xl text-slate-100 focus:text-slate-900 placeholder-slate-500 focus:placeholder-slate-400 focus:outline-none transition"
                    >
                </form>
            </div>

            <div class="flex shrink-0 items-center gap-1 sm:gap-1.5">
                @canany(['cache-clear', 'settings.manage'])
                    <form method="POST" action="{{ route('admin.clear-cache') }}" class="inline" data-reload-on-success>
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/15 bg-white/5 px-2 sm:px-2.5 py-1 text-xs font-medium text-slate-200 transition hover:bg-white/10 hover:text-white cursor-pointer"
                            title="Clear application cache"
                            onclick="return confirm('Clear application cache now?')"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span class="hidden sm:inline">Clear Cache</span>
                        </button>
                    </form>
                @endcanany

                <button
                    type="button"
                    class="admin-topbar-icon"
                    data-admin-fullscreen
                    aria-label="Toggle fullscreen"
                    title="Fullscreen"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9V4.5A.75.75 0 014.5 3.75H9M15 3.75h4.5a.75.75 0 01.75.75V9M20.25 15v4.5a.75.75 0 01-.75.75H15M9 20.25H4.5a.75.75 0 01-.75-.75V15" />
                    </svg>
                </button>

                <div class="relative" data-admin-dropdown>
                    <button
                        type="button"
                        class="admin-topbar-icon relative"
                        data-admin-dropdown-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="Notifications"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        @if ($topbarNotificationCount > 0)
                            <span class="admin-topbar-badge">{{ $topbarNotificationCount > 99 ? '99+' : $topbarNotificationCount }}</span>
                        @endif
                    </button>
                    <div class="admin-topbar-menu right-0 hidden w-72" data-admin-dropdown-panel role="menu">
                        <div class="border-b border-slate-100 px-3 py-2">
                            <p class="text-xs font-semibold text-slate-800">Notifications</p>
                            <p class="text-[11px] text-slate-500">Pending & incomplete orders</p>
                        </div>
                        <div class="max-h-72 overflow-y-auto py-1">
                            @forelse ($topbarNotifications as $notification)
                                <a href="{{ $notification['url'] }}" class="block px-3 py-2 transition hover:bg-slate-50" role="menuitem">
                                    <span class="block truncate text-xs font-medium text-slate-800">{{ $notification['label'] }}</span>
                                    <span class="mt-0.5 block text-[11px] text-slate-500">{{ $notification['meta'] }}</span>
                                </a>
                            @empty
                                <p class="px-3 py-4 text-center text-xs text-slate-500">No pending notifications</p>
                            @endforelse
                        </div>
                        <div class="flex gap-1 border-t border-slate-100 p-1.5">
                            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="flex-1 rounded px-2 py-1.5 text-center text-[11px] font-medium text-slate-600 hover:bg-slate-50">Pending</a>
                            <a href="{{ route('admin.orders.index', ['tab' => 'incomplete']) }}" class="flex-1 rounded px-2 py-1.5 text-center text-[11px] font-medium text-slate-600 hover:bg-slate-50">Incomplete</a>
                        </div>
                    </div>
                </div>

                <div class="relative" data-admin-dropdown>
                    <button
                        type="button"
                        class="ml-0.5 inline-flex items-center gap-1.5 rounded-lg border border-white/15 bg-white/5 px-2 py-1 text-slate-100 transition hover:bg-white/10"
                        data-admin-dropdown-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="Admin menu"
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sage/20 text-[10px] font-semibold text-emerald-200 border border-sage/30">
                            {{ $adminInitials }}
                        </span>
                        <span class="hidden text-xs font-medium text-slate-200 sm:inline">Admin</span>
                        <svg class="hidden h-3.5 w-3.5 text-slate-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div class="admin-topbar-menu right-0 hidden w-48" data-admin-dropdown-panel role="menu">
                        <div class="border-b border-slate-100 px-3 py-2">
                            <p class="truncate text-xs font-semibold text-slate-800">{{ $adminDisplayName }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ $adminUser?->email }}</p>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('admin.profile.edit') }}" class="block px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 font-medium flex items-center gap-1.5" role="menuitem">
                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>My Account</span>
                            </a>
                            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50" role="menuitem">Dashboard</a>
                            <a href="{{ route('admin.settings.edit') }}" class="block px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50" role="menuitem">Settings</a>
                        </div>
                        <div class="border-t border-slate-100 p-1">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded px-2 py-1.5 text-left text-xs text-red-700 hover:bg-red-50" role="menuitem">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-shell flex min-h-[calc(100vh-3.5rem)] sm:min-h-[calc(100vh-4rem)]">
        <div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop hidden></div>
        <aside id="admin-sidebar" class="admin-sidebar hidden w-60 shrink-0 flex-col lg:flex" data-admin-sidebar>
            @include('admin.partials.sidebar')
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="admin-page-header sticky top-14 sm:top-16 z-20 flex-wrap sm:flex-nowrap">
                <div class="min-w-0 flex-1">
                    <h1 class="admin-page-title truncate">@yield('heading', 'Dashboard')</h1>
                    @hasSection('subheading')
                        <p class="admin-page-subtitle truncate">@yield('subheading')</p>
                    @endif
                </div>
                <div class="admin-page-actions flex items-center gap-1.5 sm:gap-2">
                    @yield('actions')
                </div>
            </header>

            <main class="admin-content flex-1 px-3 py-5 pb-20 lg:pb-8 lg:px-6">
                @if (session('success'))
                    <div class="admin-flash-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="admin-flash-error">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Mobile Bottom Navigation Bar (Shopify / App-like) --}}
    <nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden border-t border-ink/10 bg-white/95 backdrop-blur-md pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
        <div class="flex items-center justify-around h-14 px-1">
            {{-- Dashboard --}}
            <a
                href="{{ route('admin.dashboard') }}"
                @class([
                    'flex flex-col items-center justify-center flex-1 py-1 transition group',
                    'text-sage font-bold' => request()->routeIs('admin.dashboard'),
                    'text-muted hover:text-ink font-medium' => ! request()->routeIs('admin.dashboard'),
                ])
            >
                <svg class="h-5 w-5 mb-0.5 transition-transform group-active:scale-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.dashboard') ? '2.25' : '1.75' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="text-[10px] tracking-tight">Dashboard</span>
            </a>

            {{-- Orders --}}
            <a
                href="{{ route('admin.orders.index') }}"
                @class([
                    'flex flex-col items-center justify-center flex-1 py-1 transition group',
                    'text-sage font-bold' => request()->routeIs('admin.orders.*', 'admin.incomplete-orders.*'),
                    'text-muted hover:text-ink font-medium' => ! request()->routeIs('admin.orders.*', 'admin.incomplete-orders.*'),
                ])
            >
                <svg class="h-5 w-5 mb-0.5 transition-transform group-active:scale-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.orders.*', 'admin.incomplete-orders.*') ? '2.25' : '1.75' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                </svg>
                <span class="text-[10px] tracking-tight">Orders</span>
            </a>

            {{-- Products --}}
            <a
                href="{{ route('admin.products.index') }}"
                @class([
                    'flex flex-col items-center justify-center flex-1 py-1 transition group',
                    'text-sage font-bold' => request()->routeIs('admin.products.*', 'admin.categories.*', 'admin.attributes.*', 'admin.brands.*'),
                    'text-muted hover:text-ink font-medium' => ! request()->routeIs('admin.products.*', 'admin.categories.*', 'admin.attributes.*', 'admin.brands.*'),
                ])
            >
                <svg class="h-5 w-5 mb-0.5 transition-transform group-active:scale-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.products.*', 'admin.categories.*', 'admin.attributes.*', 'admin.brands.*') ? '2.25' : '1.75' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <span class="text-[10px] tracking-tight">Products</span>
            </a>

            {{-- Courier / Customers --}}
            @canany(['courier.manage', 'api-manage', 'settings.manage'])
                <a
                    href="{{ route('admin.courier-panel.index') }}"
                    @class([
                        'flex flex-col items-center justify-center flex-1 py-1 transition group',
                        'text-sage font-bold' => request()->routeIs('admin.courier-panel.*', 'admin.fraud-settings.*', 'admin.fraud-order-rules.*', 'admin.courier-apis.*'),
                        'text-muted hover:text-ink font-medium' => ! request()->routeIs('admin.courier-panel.*', 'admin.fraud-settings.*', 'admin.fraud-order-rules.*', 'admin.courier-apis.*'),
                    ])
                >
                    <svg class="h-5 w-5 mb-0.5 transition-transform group-active:scale-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('admin.courier-panel.*', 'admin.fraud-settings.*', 'admin.fraud-order-rules.*', 'admin.courier-apis.*') ? '2.25' : '1.75' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                    <span class="text-[10px] tracking-tight">Courier</span>
                </a>
            @else
                <a
                    href="{{ route('admin.customers.index') }}"
                    @class([
                        'flex flex-col items-center justify-center flex-1 py-1 transition group',
                        'text-sage font-bold' => request()->routeIs('admin.customers.*'),
                        'text-muted hover:text-ink font-medium' => ! request()->routeIs('admin.customers.*'),
                    ])
                >
                    <svg class="h-5 w-5 mb-0.5 transition-transform group-active:scale-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="text-[10px] tracking-tight">Customers</span>
                </a>
            @endcanany

            {{-- All Menu (Opens full categorized drawer) --}}
            <button
                type="button"
                data-admin-sidebar-toggle
                class="flex flex-col items-center justify-center flex-1 py-1 text-muted hover:text-ink font-medium transition group"
                aria-label="Open menu"
            >
                <svg class="h-5 w-5 mb-0.5 transition-transform group-active:scale-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <span class="text-[10px] tracking-tight">All Menu</span>
            </button>
        </div>
    </nav>

    <script>
        (() => {
            const body = document.body;
            const sidebar = document.querySelector('[data-admin-sidebar]');
            const backdrop = document.querySelector('[data-admin-sidebar-backdrop]');
            const toggleButtons = document.querySelectorAll('[data-admin-sidebar-toggle]');
            const desktopQuery = window.matchMedia('(min-width: 1024px)');

            const setToggleExpanded = (expanded) => {
                toggleButtons.forEach((button) => {
                    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                });
            };

            const closeMobileSidebar = () => {
                if (!sidebar || desktopQuery.matches) {
                    return;
                }
                sidebar.classList.add('hidden');
                sidebar.classList.remove('admin-sidebar-open');
                backdrop?.classList.add('hidden');
                setToggleExpanded(false);
            };

            const openMobileSidebar = () => {
                if (!sidebar || desktopQuery.matches) {
                    return;
                }
                sidebar.classList.remove('hidden');
                sidebar.classList.add('admin-sidebar-open');
                backdrop?.classList.remove('hidden');
                setToggleExpanded(true);
            };

            toggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (desktopQuery.matches) {
                        body.classList.toggle('admin-sidebar-collapsed');
                        const collapsed = body.classList.contains('admin-sidebar-collapsed');
                        try {
                            localStorage.setItem('admin.sidebarCollapsed', collapsed ? '1' : '0');
                        } catch (_) {}
                        setToggleExpanded(! collapsed);
                        return;
                    }

                    if (sidebar?.classList.contains('admin-sidebar-open')) {
                        closeMobileSidebar();
                    } else {
                        openMobileSidebar();
                    }
                });
            });

            backdrop?.addEventListener('click', closeMobileSidebar);

            desktopQuery.addEventListener('change', () => {
                closeMobileSidebar();
                if (desktopQuery.matches) {
                    sidebar?.classList.remove('hidden');
                    sidebar?.classList.add('lg:flex');
                } else {
                    sidebar?.classList.add('hidden');
                    sidebar?.classList.remove('admin-sidebar-open');
                }
            });

            try {
                if (localStorage.getItem('admin.sidebarCollapsed') === '1') {
                    body.classList.add('admin-sidebar-collapsed');
                    setToggleExpanded(false);
                } else {
                    setToggleExpanded(desktopQuery.matches);
                }
            } catch (_) {
                setToggleExpanded(desktopQuery.matches);
            }

            document.querySelectorAll('[data-admin-dropdown]').forEach((dropdown) => {
                const trigger = dropdown.querySelector('[data-admin-dropdown-trigger]');
                const panel = dropdown.querySelector('[data-admin-dropdown-panel]');
                if (! trigger || ! panel) {
                    return;
                }

                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const willOpen = panel.classList.contains('hidden');
                    document.querySelectorAll('[data-admin-dropdown-panel]').forEach((other) => {
                        other.classList.add('hidden');
                    });
                    document.querySelectorAll('[data-admin-dropdown-trigger]').forEach((other) => {
                        other.setAttribute('aria-expanded', 'false');
                    });
                    if (willOpen) {
                        panel.classList.remove('hidden');
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            document.addEventListener('click', () => {
                document.querySelectorAll('[data-admin-dropdown-panel]').forEach((panel) => {
                    panel.classList.add('hidden');
                });
                document.querySelectorAll('[data-admin-dropdown-trigger]').forEach((trigger) => {
                    trigger.setAttribute('aria-expanded', 'false');
                });
            });

            document.querySelector('[data-admin-fullscreen]')?.addEventListener('click', async () => {
                try {
                    if (! document.fullscreenElement) {
                        await document.documentElement.requestFullscreen();
                    } else {
                        await document.exitFullscreen();
                    }
                } catch (_) {}
            });

            document.querySelectorAll('[data-group-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const group = trigger.closest('[data-collapsible-group]');
                    const content = group?.querySelector('[data-group-content]');
                    if (content) {
                        content.classList.toggle('hidden');
                        trigger.classList.toggle('is-open');
                    }
                });
            });
        })();
    </script>
    <script src="{{ asset('vendor/cropperjs/cropper.min.js') }}"></script>
    @include('admin.partials.image-cropper-modal')
    @include('admin.partials.media-picker-modal')
    @include('admin.partials.ajax-form-handler')
    @include('admin.partials.dropzone-script')
    @stack('scripts')
    @include('partials.password-toggle')
</body>
</html>
