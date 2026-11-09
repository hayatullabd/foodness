            {{-- Sticky brand --}}
            <div class="admin-sidebar-brand shrink-0 px-3.5 py-3.5 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5" title="{{ $storeName }}">
                    @if ($storeLogo)
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/95 p-1 shadow-sm">
                            <img src="{{ asset('storage/'.$storeLogo) }}" alt="{{ $storeName }}" class="max-h-full max-w-full object-contain">
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold tracking-tight text-white">{{ $storeName }}</span>
                            <span class="block truncate text-[10px] font-medium uppercase tracking-wider text-slate-400">Admin</span>
                        </span>
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sage text-xs font-bold tracking-wide text-white shadow-sm">
                            {{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold tracking-tight text-white">{{ $storeName }}</span>
                            <span class="block truncate text-[10px] font-medium uppercase tracking-wider text-slate-400">Admin</span>
                        </span>
                    @endif
                </a>
            </div>
            <div class="flex-1 overflow-y-auto px-2.5 py-3 space-y-0.5 text-[13px] no-scrollbar">

                {{-- Overview --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">Overview</span>
                </div>
                {{-- Dashboard --}}
                <a
                    href="{{ route('admin.dashboard') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.dashboard'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                {{-- Reports & Analytics --}}
                <a
                    href="{{ route('admin.reports.index') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.reports.*'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <span>Reports & Analytics</span>
                </a>

                {{-- Orders --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">Orders</span>
                </div>
                {{-- All Orders (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $allOrdersOpen ?? $ordersOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            <span>All Orders</span>
                        </span>
                        <div class="flex items-center gap-1.5">
                            @if ($topbarNotificationCount > 0)
                                <span class="admin-badge-yellow">{{ $topbarNotificationCount }}</span>
                            @endif
                            <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! ($allOrdersOpen ?? $ordersOpen)]) data-group-content>
                        @php
                            $inAllOrdersSection = request()->routeIs('admin.orders.index') && ($currentSection ?? 'orders') === 'orders';
                            $sidebarOrderLinks = collect($allOrderLinks ?? $orderStatusLinks ?? [])
                                ->reject(fn ($link) => ($link['status'] ?? null) === 'all' || ($link['status'] ?? null) === null)
                                ->values();
                            $sidebarAllOrdersLink = collect($allOrderLinks ?? $orderStatusLinks ?? [])
                                ->first(fn ($link) => ($link['status'] ?? null) === 'all' || ($link['status'] ?? null) === null)
                                ?? ['label' => 'All Orders', 'status' => 'all'];
                        @endphp
                        @foreach ($sidebarOrderLinks as $statusLink)
                            @php
                                $isLinkActive = $inAllOrdersSection
                                    && request('tab') !== 'incomplete'
                                    && request('status') === $statusLink['status'];
                                $linkUrl = route('admin.orders.index', array_filter(['section' => 'orders', 'status' => $statusLink['status']]));
                            @endphp
                            <div class="admin-tree-item">
                                <a
                                    href="{{ $linkUrl }}"
                                    @class([
                                        'admin-tree-link',
                                        'is-active' => $isLinkActive,
                                    ])
                                >
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>{{ $statusLink['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.orders.index', ['tab' => 'incomplete']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.orders.*', 'admin.incomplete-orders.*') && request('tab') === 'incomplete',
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Incomplete</span>
                            </a>
                        </div>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.order-statuses.index') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.order-statuses.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.386a6.837 6.837 0 002.39-2.39c.486-.826.313-1.908-.386-2.607L8.27 3.659A2.25 2.25 0 006.679 3z" />
                                </svg>
                                <span>Statuses</span>
                            </a>
                        </div>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.orders.index', ['section' => 'orders', 'status' => 'all']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => $inAllOrdersSection && request('tab') !== 'incomplete' && request('status') === 'all',
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>{{ $sidebarAllOrdersLink['label'] ?? 'All Orders' }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Courier Orders (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $courierOrdersOpen ?? false,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75a1.125 1.125 0 01-1.125-1.125H5.625A1.125 1.125 0 004.5 3.75v10.5" />
                            </svg>
                            <span>Courier</span>
                        </span>
                        <div class="flex items-center gap-1.5">
                            <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! ($courierOrdersOpen ?? false)]) data-group-content>
                        @php
                            $inCourierSection = request()->routeIs('admin.orders.index') && ($currentSection ?? '') === 'courier';
                            $sidebarCourierLinks = collect($courierOrderLinks ?? [])
                                ->reject(fn ($link) => ($link['status'] ?? null) === 'all' || ($link['status'] ?? null) === null)
                                ->values();
                            $sidebarAllCourierLink = collect($courierOrderLinks ?? [])
                                ->first(fn ($link) => ($link['status'] ?? null) === 'all' || ($link['status'] ?? null) === null)
                                ?? ['label' => 'All Courier', 'status' => 'all'];
                        @endphp
                        @foreach ($sidebarCourierLinks as $cLink)
                            @php
                                $isCLinkActive = $inCourierSection && request('status') === $cLink['status'];
                            @endphp
                            <div class="admin-tree-item">
                                <a
                                    href="{{ route('admin.orders.index', ['section' => 'courier', 'status' => $cLink['status']]) }}"
                                    @class([
                                        'admin-tree-link',
                                        'is-active' => $isCLinkActive,
                                    ])
                                >
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25" />
                                    </svg>
                                    <span>{{ $cLink['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.orders.index', ['section' => 'courier', 'status' => 'all']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => $inCourierSection && request('status') === 'all',
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25" />
                                </svg>
                                <span>{{ $sidebarAllCourierLink['label'] ?? 'All Courier' }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Refunds (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $refundsOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            <span>Refunds</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $refundsOpen]) data-group-content>
                        @foreach ($refundStatusLinks as $statusLink)
                            <div class="admin-tree-item">
                                <a
                                    href="{{ route('admin.refunds.index', array_filter(['status' => $statusLink['status']])) }}"
                                    @class([
                                        'admin-tree-link',
                                        'is-active' => request()->routeIs('admin.refunds.index') && $currentRefundStatus === $statusLink['status'],
                                    ])
                                >
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                    </svg>
                                    <span>{{ $statusLink['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Catalog --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">Catalog</span>
                </div>
                {{-- Products (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $productsOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                            </svg>
                            <span>Products</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $productsOpen]) data-group-content>
                        @foreach ($productLinks as $productLink)
                            @php
                                $isActive = request()->routeIs($productLink['route'])
                                    && (
                                        ($productLink['route'] === 'admin.products.index' && ($currentProductFilter ?: null) === ($productLink['params']['filter'] ?? null))
                                        || ($productLink['route'] !== 'admin.products.index' && empty($productLink['params']))
                                    );
                                if ($productLink['route'] === 'admin.products.create') {
                                    $isActive = request()->routeIs('admin.products.create');
                                }
                                if (in_array($productLink['route'], [
                                    'admin.categories.index',
                                    'admin.subcategories.index',
                                    'admin.childcategories.index',
                                    'admin.brands.index',
                                    'admin.attributes.index',
                                ], true)) {
                                    $isActive = request()->routeIs(str_replace('.index', '.*', $productLink['route']));
                                }
                            @endphp
                            <div class="admin-tree-item">
                                <a
                                    href="{{ route($productLink['route'], $productLink['params']) }}"
                                    @class([
                                        'admin-tree-link',
                                        'is-active' => $isActive,
                                    ])
                                >
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 012.25 9.375v-2.25zM2.25 14.625c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 012.25 16.875v-2.25zM13.5 7.125c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 0113.5 9.375v-2.25zM13.5 14.625c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 0113.5 16.875v-2.25z" />
                                    </svg>
                                    <span>{{ $productLink['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Media --}}
                <a
                    href="{{ route('admin.media.index') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.media.*'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    <span>Media</span>
                    <span class="ml-auto rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-sky-400/15 text-sky-300 ring-1 ring-sky-400/25">Files</span>
                </a>

                {{-- Purchases --}}
                <a
                    href="{{ route('admin.purchases.index') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.purchases.*'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Purchases</span>
                </a>

                {{-- Suppliers --}}
                <a
                    href="{{ route('admin.suppliers.index') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.suppliers.*'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                    <span>Suppliers</span>
                </a>

                {{-- Marketing --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">Marketing</span>
                </div>
                {{-- Stories --}}
                <a
                    href="{{ route('admin.stories.index') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.stories.*'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
                    </svg>
                    <span>Stories</span>
                    <span class="ml-auto rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-rose-400/15 text-rose-300 ring-1 ring-rose-400/25">Story</span>
                </a>

                {{-- Customization Group (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $customizationOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                            </svg>
                            <span>Customization</span>
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="ml-auto rounded px-1.5 py-0.2 text-[9px] font-bold uppercase tracking-wider bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/25">Design</span>
                            <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $customizationOpen]) data-group-content>
                        {{-- 1. Product Page --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.product-page.edit') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.product-page.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 012.25 9.375v-2.25zM2.25 14.625c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-2.25zM13.5 7.125c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6A1.125 1.125 0 0113.5 9.375v-2.25zM13.5 14.625c0-.621.504-1.125 1.125-1.125h6c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-2.25z" />
                                </svg>
                                <span>Product Page</span>
                            </a>
                        </div>

                        {{-- 2. Header --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.customization.edit', ['tab' => 'header']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.customization.*') && in_array(request('tab', 'header'), ['header', 'branding'], true),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
                                </svg>
                                <span>Header</span>
                            </a>
                        </div>

                        {{-- 3. Homepage --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.customization.edit', ['tab' => 'home']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.customization.*') && in_array(request('tab'), ['home', 'hero', 'sections', 'stories', 'categories', 'gur_showcase', 'featured', 'story_reel', 'promises', 'faq'], true),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                <span>Homepage</span>
                            </a>
                        </div>

                        {{-- 4. Footer --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.customization.edit', ['tab' => 'footer']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.customization.*') && request('tab') === 'footer',
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" />
                                </svg>
                                <span>Footer</span>
                            </a>
                        </div>

                        {{-- 5. Theme --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.customization.edit', ['tab' => 'theme']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.customization.*') && in_array(request('tab'), ['theme', 'colors', 'widgets'], true),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" />
                                </svg>
                                <span>Theme</span>
                            </a>
                        </div>

                        {{-- 5. Pages & Policies --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.pages.index') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.pages.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <span>Pages & Policies</span>
                            </a>
                        </div>

                        {{-- 6. Landing Pages & Funnels --}}
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.landing-pages.index') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.landing-pages.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                                <span>Landing Pages</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Operations --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">Operations</span>
                </div>
                {{-- Shipping --}}
                <a
                    href="{{ route('admin.shipping-charges.index') }}"
                    @class([
                        'admin-nav-flat',
                        'is-active' => request()->routeIs('admin.shipping-charges.*'),
                    ])
                >
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    <span>Shipping</span>
                </a>

                {{-- Courier Dashboard (Tree Branch) --}}
                @canany(['courier.manage', 'api-manage', 'settings.manage'])
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $courierPanelOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg>
                            <span>Courier Dashboard</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $courierPanelOpen]) data-group-content>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.courier-panel.index') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.courier-panel.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                <span>Courier Dashboard</span>
                            </a>
                        </div>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.courier-apis.index') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.courier-apis.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.27.1.06-.12l-.773.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.27-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.11v-1.093c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Delivery Methods API</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- Courier Ratio Settings (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $fraudSettingsOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span>Fraud</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $fraudSettingsOpen]) data-group-content>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.fraud-settings.edit') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => $fraudApiOpen,
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                                <span>Manage Courier Ratio API</span>
                            </a>
                        </div>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.fraud-order-rules.edit') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => $fraudOrderRulesOpen,
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                <span>Courier Ratio Rules</span>
                            </a>
                        </div>
                    </div>
                </div>

                @if (! auth('admin')->check() || auth('admin')->user()->can('settings.manage'))
                {{-- Order Restrictions (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $orderRestrictionsOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span>Restrictions</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $orderRestrictionsOpen]) data-group-content>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.order-restrictions.edit') }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.order-restrictions.*'),
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                                <span>Restriction Rules</span>
                            </a>
                        </div>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.blocking-logs.index', ['tab' => 'history']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.blocking-logs.*') && request('tab') !== 'active-blocks',
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                </svg>
                                <span>Blocking Logs</span>
                            </a>
                        </div>
                        <div class="admin-tree-item">
                            <a
                                href="{{ route('admin.blocking-logs.index', ['tab' => 'active-blocks']) }}"
                                @class([
                                    'admin-tree-link',
                                    'is-active' => request()->routeIs('admin.blocking-logs.*') && request('tab') === 'active-blocks',
                                ])
                            >
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Active Blacklists</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Integrations --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">Integrations</span>
                </div>
                {{-- API Integration (Tree Branch) --}}
                @canany(['api-manage', 'settings.manage'])
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $apiIntegrationOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                            </svg>
                            <span>API Integration</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $apiIntegrationOpen]) data-group-content>
                        @foreach ($apiIntegrationLinks as $apiLink)
                            @continue(! auth('admin')->user()?->canany(['api-manage', 'settings.manage']))
                            <div class="admin-tree-item">
                                <a
                                    href="{{ route($apiLink['route']) }}"
                                    @class([
                                        'admin-tree-link',
                                        'is-active' => request()->routeIs($apiLink['match']),
                                    ])
                                >
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                    </svg>
                                    <span>{{ $apiLink['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endcanany

                {{-- Tracking (Tree Branch) --}}
                @canany(['pixel-manage', 'settings.manage'])
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $trackingOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            <span>Tracking</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $trackingOpen]) data-group-content>
                        @foreach ($trackingLinks as $trackingLink)
                            <div class="admin-tree-item">
                                <a
                                    href="{{ route($trackingLink['route']) }}"
                                    @class([
                                        'admin-tree-link',
                                        'is-active' => request()->routeIs($trackingLink['match']),
                                    ])
                                >
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                    </svg>
                                    <span>{{ $trackingLink['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endcanany

                {{-- People --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">People</span>
                </div>
                {{-- Users (Tree Branch) --}}
                @canany(['users.manage', 'roles.manage', 'permissions.manage', 'customers.view', 'customers.manage'])
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $usersOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span>Users</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $usersOpen]) data-group-content>
                        @foreach ($userLinks as $userLink)
                            @if (empty($userLink['permission']) || auth('admin')->user()?->can($userLink['permission']))
                                <div class="admin-tree-item">
                                    <a
                                        href="{{ route($userLink['route']) }}"
                                        @class([
                                            'admin-tree-link',
                                            'is-active' => request()->routeIs($userLink['match']),
                                        ])
                                    >
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                        <span>{{ $userLink['label'] }}</span>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endcanany

                {{-- System --}}
                <div class="admin-nav-section">
                    <span class="admin-nav-section-title">System</span>
                </div>
                {{-- Settings (Tree Branch) --}}
                <div class="space-y-0.5" data-collapsible-group>
                    <button
                        type="button"
                        @class([
                            'admin-group-header',
                            'is-open' => $settingsOpen,
                        ])
                        data-group-trigger
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.759 6.759 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Settings</span>
                        </span>
                        <svg class="admin-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div @class(['admin-tree-container', 'hidden' => ! $settingsOpen]) data-group-content>
                        @foreach ($settingsLinks as $settingsLink)
                            @if (empty($settingsLink['permission']) || auth('admin')->user()?->can($settingsLink['permission']))
                                <div class="admin-tree-item">
                                    <a
                                        href="{{ route($settingsLink['route']) }}"
                                        @class([
                                            'admin-tree-link',
                                            'is-active' => request()->routeIs($settingsLink['match']),
                                        ])
                                    >
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                        </svg>
                                        <span>{{ $settingsLink['label'] }}</span>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Pinned Footer matching screenshot --}}
            <div class="admin-sidebar-footer">
                <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2 min-w-0 group hover:opacity-90 transition" title="My Account & Profile">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/10 border border-white/15 text-[11px] font-bold text-white shadow-2xs">
                        {{ $adminInitials }}
                    </span>
                    <div class="min-w-0 flex items-center gap-1 leading-none">
                        <span class="truncate text-xs font-medium text-slate-100 max-w-[95px]">{{ $storeName }}</span>
                        <span class="text-[11px] text-slate-400 shrink-0">· Admin</span>
                    </div>
                </a>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('admin.settings.edit') }}" class="p-1 text-slate-400 hover:text-white hover:bg-white/10 rounded transition" title="Settings">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.759 6.759 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>
                    <button type="button" class="p-1 text-slate-400 hover:text-white hover:bg-white/10 rounded transition" data-admin-sidebar-toggle title="Toggle Sidebar">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                        </svg>
                    </button>
                </div>
            </div>
