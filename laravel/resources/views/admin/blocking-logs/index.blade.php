@extends('layouts.admin')

@section('title', 'Blocking History & Anti-Fraud Logs')
@section('heading', 'Blocking History & Security Logs')
@section('subheading', 'Detailed spreadsheet-style audit ledger of blocked fake orders, abusive bots, and blacklist management')

@section('content')
    @php
        $activeTab = request('tab', 'history');
    @endphp

    <div class="space-y-3">
        {{-- Top Navigation & Sheet Tabs (Excel Workbook Style) --}}
        <div class="flex flex-wrap items-center justify-between gap-2.5 border-b border-slate-200 bg-white px-3 py-2 rounded-t-lg shadow-2xs">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                {{-- Restriction Settings Tab Button --}}
                <a
                    href="{{ route('admin.order-restrictions.edit', ['tab' => 'settings']) }}"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                >
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 12h9.75M10.5 18h9.75M4.5 6h.008v.008H4.5V6zm0 6h.008v.008H4.5V12zm0 6h.008v.008H4.5V18z" />
                    </svg>
                    Restriction Settings
                </a>

                {{-- Blocked Attempts Tab Button (No Page Reload) --}}
                <button
                    type="button"
                    id="tab-btn-history"
                    data-tab-trigger="history"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer {{ $activeTab === 'history' ? 'border border-emerald-600 bg-emerald-50 text-emerald-900 shadow-2xs ring-1 ring-emerald-600' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <svg class="h-4 w-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z" />
                    </svg>
                    Blocking History &amp; Logs
                    <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-800 px-1.5 py-0.2 text-[10px] font-bold">
                        {{ number_format($totalBlockedAttempts) }}
                    </span>
                </button>

                {{-- Active Blacklists Tab Button (No Page Reload) --}}
                <button
                    type="button"
                    id="tab-btn-active-blocks"
                    data-tab-trigger="active-blocks"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer {{ $activeTab === 'active-blocks' ? 'border border-indigo-600 bg-indigo-50 text-indigo-900 shadow-2xs ring-1 ring-indigo-600' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <svg class="h-4 w-4 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Active Blacklists (Block &amp; Unblock)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 text-indigo-800 px-1.5 py-0.2 text-[10px] font-bold">
                        {{ count($activeBlacklistRows) }}
                    </span>
                </button>
            </div>

            {{-- Right Actions Toolbar --}}
            <div class="flex items-center gap-2">
                {{-- Export to CSV / Excel Button --}}
                <a
                    href="{{ route('admin.blocking-logs.export', request()->all()) }}"
                    class="inline-flex items-center gap-1.5 rounded border border-emerald-700 bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-emerald-700"
                    title="Download this entire audit log as a Microsoft Excel / CSV spreadsheet"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export to Excel (.csv)
                </a>

                {{-- Clear Logs & Blacklists Action --}}
                @if ($totalBlockedAttempts > 0 || count($activeBlacklistRows) > 0)
                    <form
                        method="POST"
                        action="{{ route('admin.blocking-logs.clear') }}"
                        onsubmit="return confirm('Are you sure you want to clear all blocking logs and reset all active blacklists (IPs, Phones, Devices)?')"
                        class="inline-block"
                    >
                        @csrf
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100 shadow-2xs"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                            Clear All Logs &amp; Blacklists
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- PANEL 1: BLOCKED ATTEMPTS LOG (Excel Spreadsheet Audit Ledger)             --}}
        {{-- ========================================================================= --}}
        <div id="tab-panel-history" class="{{ $activeTab === 'history' ? '' : 'hidden' }} space-y-3">
            {{-- Excel Filter Toolbar --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-2.5 shadow-2xs">
                <form method="GET" action="{{ route('admin.blocking-logs.index') }}" class="flex flex-wrap items-center justify-between gap-2">
                    <input type="hidden" name="tab" value="history">

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Search Input --}}
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Filter by IP, phone, device, name..."
                            class="h-8 w-64 rounded border border-slate-300 bg-white pl-8 pr-7 text-xs font-mono text-slate-800 placeholder:font-sans placeholder:text-slate-400 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                        >
                        @if (request('search'))
                            <a
                                href="{{ route('admin.blocking-logs.index', ['tab' => $activeTab]) }}"
                                class="absolute right-2 top-1.5 text-xs text-slate-400 hover:text-slate-700"
                            >&times;</a>
                        @endif
                    </div>

                    @if ($activeTab === 'history')
                        {{-- Security Vector Filter --}}
                        <select
                            name="type"
                            onchange="this.form.submit()"
                            class="h-8 rounded border border-slate-300 bg-white px-2.5 text-xs font-medium text-slate-700 focus:border-emerald-600"
                        >
                            <option value="">All Protection Vectors</option>
                            <option value="ip_blacklist" {{ request('type') === 'ip_blacklist' ? 'selected' : '' }}>IP Blacklist</option>
                            <option value="ip_rate_limit" {{ request('type') === 'ip_rate_limit' ? 'selected' : '' }}>IP Rate Limit</option>
                            <option value="phone_blacklist" {{ request('type') === 'phone_blacklist' ? 'selected' : '' }}>Phone Blacklist</option>
                            <option value="phone_rate_limit" {{ request('type') === 'phone_rate_limit' ? 'selected' : '' }}>Phone Rate Limit</option>
                            <option value="invalid_phone" {{ request('type') === 'invalid_phone' ? 'selected' : '' }}>Invalid Phone Format</option>
                            <option value="device_blacklist" {{ request('type') === 'device_blacklist' ? 'selected' : '' }}>Device Blacklist</option>
                            <option value="device_rate_limit" {{ request('type') === 'device_rate_limit' ? 'selected' : '' }}>Device Rate Limit</option>
                            <option value="spam_content" {{ request('type') === 'spam_content' ? 'selected' : '' }}>Spam Keyword</option>
                            <option value="short_address" {{ request('type') === 'short_address' ? 'selected' : '' }}>Incomplete Address</option>
                            <option value="product_limit" {{ request('type') === 'product_limit' ? 'selected' : '' }}>Product Order Limit</option>
                        </select>

                        {{-- Date Filter --}}
                        <select
                            name="date"
                            onchange="this.form.submit()"
                            class="h-8 rounded border border-slate-300 bg-white px-2.5 text-xs font-medium text-slate-700 focus:border-emerald-600"
                        >
                            <option value="">All Time</option>
                            <option value="today" {{ request('date') === 'today' ? 'selected' : '' }}>Today Only</option>
                            <option value="week" {{ request('date') === 'week' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="month" {{ request('date') === 'month' ? 'selected' : '' }}>Last 30 Days</option>
                        </select>

                        {{-- Per Page --}}
                        <select
                            name="per_page"
                            onchange="this.form.submit()"
                            class="h-8 rounded border border-slate-300 bg-white px-2 text-xs font-mono text-slate-700 focus:border-emerald-600"
                        >
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 rows</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 rows</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 rows</option>
                        </select>
                    @endif

                    <button type="submit" class="h-8 rounded bg-slate-200 px-3 text-xs font-semibold text-slate-800 hover:bg-slate-300">
                        Apply Filter
                    </button>
                </div>

                <div class="text-[11px] text-slate-500">
                    Showing <strong>{{ $logs->firstItem() ?? 0 }}</strong> to <strong>{{ $logs->lastItem() ?? 0 }}</strong> of <strong>{{ $logs->total() }}</strong> records
                </div>
            </form>
        </div>

        {{-- Audit Log Spreadsheet Table --}}
        <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white shadow-2xs">
                <table class="min-w-full text-left text-xs divide-y divide-slate-200 border-collapse">
                    {{-- Excel Column Headers --}}
                    <thead class="bg-slate-100 text-slate-700 select-none">
                        <tr class="divide-x divide-slate-200 text-[11px] font-bold uppercase tracking-wider">
                            <th class="w-12 px-2.5 py-2 text-center bg-slate-200/80">#</th>
                            <th class="w-36 px-2.5 py-2 whitespace-nowrap">Date &amp; Time</th>
                            <th class="w-36 px-2.5 py-2 text-center whitespace-nowrap">Block Expires In</th>
                            <th class="w-20 px-2 py-2 text-center whitespace-nowrap">Attempts</th>
                            <th class="w-36 px-2.5 py-2 whitespace-nowrap">Vector / Type</th>
                            <th class="w-40 px-2.5 py-2 whitespace-nowrap">Offending Target</th>
                            <th class="w-36 px-2.5 py-2 whitespace-nowrap">Customer Name</th>
                            <th class="w-32 px-2.5 py-2 whitespace-nowrap">Phone Number</th>
                            <th class="w-32 px-2.5 py-2 whitespace-nowrap">IP Address</th>
                            <th class="w-32 px-2.5 py-2 whitespace-nowrap">Device Token</th>
                            <th class="min-w-[200px] px-3 py-2">Delivery Address</th>
                            <th class="min-w-[220px] px-3 py-2">Enforced Reason / Message</th>
                            <th class="w-16 px-2 py-2 text-center whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-sans">
                        @forelse ($logs as $index => $log)
                            @php
                                $rowNumber = $logs->firstItem() + $index;
                                $vectorBadge = match($log->block_type) {
                                    'ip_blacklist', 'ip_rate_limit' => 'bg-sky-50 text-sky-800 border-sky-300',
                                    'phone_blacklist', 'phone_rate_limit', 'invalid_phone' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                    'device_blacklist', 'device_rate_limit' => 'bg-purple-50 text-purple-800 border-purple-300',
                                    'spam_content', 'short_address' => 'bg-amber-50 text-amber-900 border-amber-300',
                                    'product_limit' => 'bg-indigo-50 text-indigo-900 border-indigo-300',
                                    default => 'bg-slate-50 text-slate-800 border-slate-300',
                                };
                            @endphp
                            <tr
                                data-log-details="{{ json_encode($log->detailsArray(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                class="divide-x divide-slate-200 cursor-pointer hover:bg-sky-50/70 transition-colors {{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }}"
                                title="Click row to view full device, IP, browser & location details"
                            >
                                {{-- # Row Number --}}
                                <td class="px-2.5 py-2 text-center font-mono text-slate-500 bg-slate-50/80">
                                    {{ $rowNumber }}
                                </td>

                                {{-- Date & Time --}}
                                <td class="px-2.5 py-2 whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    <span class="block font-semibold text-slate-900">{{ ($log->last_attempt_at ?: $log->created_at)?->format('Y-m-d H:i') }}</span>
                                    @if ($log->last_attempt_at && $log->created_at && $log->last_attempt_at->ne($log->created_at))
                                        <span class="text-[10px] text-slate-400">First: {{ $log->created_at->format('M d, H:i') }}</span>
                                    @else
                                        <span class="text-[10px] text-slate-400">{{ ($log->last_attempt_at ?: $log->created_at)?->diffForHumans() }}</span>
                                    @endif
                                </td>

                                {{-- Block Expiration Countdown Timer --}}
                                <td class="px-2.5 py-2 text-center whitespace-nowrap font-mono text-[11px]">
                                    @php
                                        $blockHours = max(1, (int) ($settings->hours ?? 24));
                                        $attemptTime = $log->last_attempt_at ?: $log->created_at;
                                        $blockExpiresAt = $attemptTime ? $attemptTime->copy()->addHours($blockHours) : null;
                                    @endphp
                                    @if ($blockExpiresAt)
                                        <span
                                            data-countdown-target="{{ $blockExpiresAt->toIso8601String() }}"
                                            class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200"
                                            title="Block restriction window: {{ $blockHours }}h (Expires at {{ $blockExpiresAt->format('Y-m-d H:i:s') }})"
                                        >
                                            <svg class="h-3 w-3 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="countdown-display">Calculating...</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Attempts Count --}}
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold {{ ($log->attempts_count ?? 1) > 1 ? 'bg-rose-100 text-rose-800 ring-1 ring-rose-300' : 'bg-slate-100 text-slate-700' }}">
                                        {{ number_format($log->attempts_count ?? 1) }}
                                    </span>
                                </td>

                                {{-- Vector / Type --}}
                                <td class="px-2.5 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-semibold {{ $vectorBadge }}">
                                        {{ $log->typeLabel() }}
                                    </span>
                                </td>

                                {{-- Offending Target --}}
                                <td class="px-2.5 py-2 font-mono text-xs font-bold text-rose-700 select-all whitespace-nowrap">
                                    @if ($log->offending_value)
                                        <code class="rounded bg-rose-50 px-1.5 py-0.5 border border-rose-200">{{ $log->offending_value }}</code>
                                    @elseif ($log->phone)
                                        <code class="rounded bg-slate-100 px-1 py-0.5">{{ $log->phone }}</code>
                                    @elseif ($log->ip_address)
                                        <code class="rounded bg-slate-100 px-1 py-0.5">{{ $log->ip_address }}</code>
                                    @else
                                        <span class="text-slate-300">&mdash;</span>
                                    @endif
                                </td>

                                {{-- Customer Name --}}
                                <td class="px-2.5 py-2 font-medium text-slate-900 whitespace-nowrap">
                                    {{ $log->customer_name ?: 'Guest / Bot' }}
                                </td>

                                {{-- Phone Number --}}
                                <td class="px-2.5 py-2 font-mono text-xs text-slate-800 whitespace-nowrap select-all">
                                    {{ $log->phone ?: '—' }}
                                </td>

                                {{-- IP Address --}}
                                <td class="px-2.5 py-2 font-mono text-xs text-slate-800 whitespace-nowrap select-all">
                                    {{ $log->ip_address ?: '—' }}
                                </td>

                                {{-- Device Token --}}
                                <td class="px-2.5 py-2 font-mono text-[11px] text-slate-600 whitespace-nowrap select-all" title="{{ $log->device_id }}">
                                    {{ $log->device_id ? Str::limit($log->device_id, 14) : '—' }}
                                </td>

                                {{-- Delivery Address --}}
                                <td class="px-3 py-2 text-slate-700 text-xs max-w-xs truncate" title="{{ $log->customer_address }}">
                                    {{ $log->customer_address ?: '—' }}
                                </td>

                                {{-- Reason / Policy Triggered --}}
                                <td class="px-3 py-2 text-slate-800 text-xs">
                                    <span class="block text-slate-900 font-medium">{{ $log->reason }}</span>
                                </td>

                                {{-- Action: View details & Delete single row --}}
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            onclick="openBlockingLogModal({{ json_encode($log->detailsArray(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})"
                                            class="rounded p-1 text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 transition cursor-pointer"
                                            title="View full device, IP, browser & location details"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.blocking-logs.destroy', $log) }}"
                                            onsubmit="return confirm('Delete this log entry?')"
                                            class="inline-block"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
                                                title="Delete log record"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-12 text-center text-slate-400">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-2">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                        </svg>
                                    </div>
                                    <p class="font-medium text-slate-700">No blocked order attempts found matching your filter criteria.</p>
                                    <p class="text-xs text-slate-400 mt-0.5">When orders are blocked by Device, IP, Phone, or Spam filter, every attempt will be listed here in detail.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Excel Table Footer Pagination --}}
            @if ($logs->hasPages())
                <div class="flex items-center justify-between border-t border-slate-200 bg-white px-3 py-2 rounded-b-lg shadow-2xs">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- PANEL 2: ACTIVE BLACKLISTS (Instant Management)                           --}}
        {{-- ========================================================================= --}}
        <div id="tab-panel-active-blocks" class="{{ $activeTab === 'active-blocks' ? '' : 'hidden' }} space-y-3">
            {{-- Inline Quick Block Row & Live Search --}}
            <div class="rounded-lg border border-slate-300 bg-white p-3 shadow-2xs">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="POST" action="{{ route('admin.blocking-logs.block') }}" class="flex flex-wrap items-end gap-2.5 flex-1">
                        @csrf
                        <input type="hidden" name="redirect_tab" value="active-blocks">

                        <div>
                            <label class="block text-[11px] font-semibold uppercase text-slate-600 mb-1" for="new_type">Target Type *</label>
                            <select id="new_type" name="type" class="h-8 rounded border border-slate-300 bg-white px-2.5 text-xs text-slate-800" required>
                                <option value="ip">IP Address / Subnet</option>
                                <option value="phone">Phone Number</option>
                                <option value="device">Device ID / User-Agent</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[11px] font-semibold uppercase text-slate-600 mb-1" for="new_value">Value to Blacklist *</label>
                            <input
                                id="new_value"
                                name="value"
                                type="text"
                                placeholder="e.g. 103.14.25.10 or 01712345678 or did_token"
                                class="h-8 w-full rounded border border-slate-300 bg-white px-2.5 text-xs font-mono text-slate-800"
                                required
                            >
                        </div>

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[11px] font-semibold uppercase text-slate-600 mb-1" for="new_reason">Reason / Note</label>
                            <input
                                id="new_reason"
                                name="reason"
                                type="text"
                                placeholder="Abuse, fraud order, refusal, etc."
                                class="h-8 w-full rounded border border-slate-300 bg-white px-2.5 text-xs text-slate-800"
                            >
                        </div>

                        <button type="submit" class="h-8 rounded bg-rose-600 px-4 text-xs font-semibold text-white shadow-2xs hover:bg-rose-700">
                            + Blacklist Target Now
                        </button>
                    </form>

                    {{-- Live filter search for active blacklists --}}
                    <div class="relative w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <input
                            id="blacklist-search-input"
                            type="search"
                            placeholder="Live search active blacklist..."
                            class="h-8 w-full rounded border border-slate-300 bg-white pl-8 pr-2.5 text-xs font-mono text-slate-800 placeholder:font-sans placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                        >
                    </div>
                </div>
            </div>

            {{-- Excel Grid Table for Active Blacklist --}}
            <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white shadow-2xs">
                <table id="active-blacklist-table" class="min-w-full text-left text-xs divide-y divide-slate-200 border-collapse">
                    <thead class="bg-slate-100 text-slate-700 select-none">
                        <tr class="divide-x divide-slate-200 text-[11px] font-bold uppercase tracking-wider">
                            <th class="w-12 px-2.5 py-2 text-center bg-slate-200/80">#</th>
                            <th class="w-32 px-3 py-2">Entity Type</th>
                            <th class="w-56 px-3 py-2">Blacklisted Value / Target</th>
                            <th class="px-3 py-2">Reason / Note</th>
                            <th class="w-40 px-3 py-2">Added Source</th>
                            <th class="w-28 px-3 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($activeBlacklistRows as $idx => $row)
                            @php
                                $typeBadge = match($row['raw_type']) {
                                    'ip' => 'bg-sky-100 text-sky-800 border-sky-300',
                                    'phone' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    'device' => 'bg-purple-100 text-purple-800 border-purple-300',
                                    default => 'bg-slate-100 text-slate-800 border-slate-300',
                                };
                            @endphp
                            <tr data-blacklist-row class="divide-x divide-slate-200 hover:bg-emerald-50/30 transition-colors {{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }}">
                                <td class="px-2.5 py-2 text-center font-mono text-slate-500 bg-slate-50/80">{{ $idx + 1 }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-semibold {{ $typeBadge }}">
                                        {{ $row['type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono font-bold text-slate-900 select-all">
                                    {{ $row['value'] }}
                                </td>
                                <td class="px-3 py-2 text-slate-700">
                                    {{ $row['reason'] }}
                                </td>
                                <td class="px-3 py-2 text-slate-500 font-mono text-[11px]">
                                    {{ $row['source'] }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.blocking-logs.unblock') }}"
                                        onsubmit="return confirm('Unblock {{ $row['value'] }}? It will be removed from all blacklists.')"
                                        class="inline-block"
                                    >
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $row['raw_type'] }}">
                                        <input type="hidden" name="value" value="{{ $row['value'] }}">
                                        <input type="hidden" name="redirect_tab" value="active-blocks">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 rounded border border-emerald-600 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-600 hover:text-white shadow-2xs"
                                        >
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            Unblock
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                    No active blacklisted entities found. Use the quick blacklist form above to block any IP, phone, or device.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="blacklist-empty-search" style="display: none;">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                No blacklisted entities match your search query.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Instant Tab Switching & Client-Side Search (Zero Page Reload) --}}
    <script>
        (function () {
            function switchBlockingLogTab(tabKey) {
                const isHistory = tabKey === 'history';

                const panelHistory = document.getElementById('tab-panel-history');
                const panelActive = document.getElementById('tab-panel-active-blocks');
                const btnHistory = document.getElementById('tab-btn-history');
                const btnActive = document.getElementById('tab-btn-active-blocks');

                if (!panelHistory || !panelActive || !btnHistory || !btnActive) return;

                if (isHistory) {
                    panelHistory.classList.remove('hidden');
                    panelActive.classList.add('hidden');

                    btnHistory.className = 'inline-flex items-center gap-1.5 rounded border px-3 py-1.5 font-semibold transition cursor-pointer border-emerald-600 bg-emerald-50 text-emerald-900 shadow-2xs ring-1 ring-emerald-600';
                    btnActive.className = 'inline-flex items-center gap-1.5 rounded border px-3 py-1.5 font-semibold transition cursor-pointer border-slate-200 bg-white text-slate-600 hover:bg-slate-50';
                } else {
                    panelHistory.classList.add('hidden');
                    panelActive.classList.remove('hidden');

                    btnHistory.className = 'inline-flex items-center gap-1.5 rounded border px-3 py-1.5 font-semibold transition cursor-pointer border-slate-200 bg-white text-slate-600 hover:bg-slate-50';
                    btnActive.className = 'inline-flex items-center gap-1.5 rounded border px-3 py-1.5 font-semibold transition cursor-pointer border-indigo-600 bg-indigo-50 text-indigo-900 shadow-2xs ring-1 ring-indigo-600';
                }

                // Update tab input in all forms so filters / clear know the active tab
                document.querySelectorAll('input[name="tab"]').forEach(function (input) {
                    input.value = tabKey;
                });

                // Update URL query string without reloading page
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabKey);
                window.history.replaceState({ tab: tabKey }, '', url.toString());
            }

            document.querySelectorAll('[data-tab-trigger]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    switchBlockingLogTab(this.getAttribute('data-tab-trigger'));
                });
            });

            window.addEventListener('popstate', function () {
                const urlParams = new URLSearchParams(window.location.search);
                switchBlockingLogTab(urlParams.get('tab') || 'history');
            });

            // Live countdown timer for block expiration
            function updateRowTimers() {
                const now = Date.now();
                document.querySelectorAll('[data-countdown-target]').forEach(function (el) {
                    const targetIso = el.getAttribute('data-countdown-target');
                    if (!targetIso) return;
                    const target = new Date(targetIso).getTime();
                    const diff = target - now;
                    const display = el.querySelector('.countdown-display') || el;

                    if (diff <= 0) {
                        display.textContent = 'Expired / Lifted';
                        el.className = 'inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200';
                        const svg = el.querySelector('svg');
                        if (svg) svg.className = 'h-3 w-3 text-emerald-600 shrink-0';
                        return;
                    }

                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                    const pad = (n) => String(n).padStart(2, '0');
                    if (days > 0) {
                        display.textContent = `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                    } else {
                        display.textContent = `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                    }
                });
            }
            updateRowTimers();
            setInterval(updateRowTimers, 1000);

            // Instant live search filter for active blacklists
            const blacklistSearchInput = document.getElementById('blacklist-search-input');
            if (blacklistSearchInput) {
                blacklistSearchInput.addEventListener('input', function () {
                    const query = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#active-blacklist-table tbody tr[data-blacklist-row]');
                    let visibleCount = 0;
                    rows.forEach(function (row) {
                        const match = row.textContent.toLowerCase().includes(query);
                        row.style.display = match ? '' : 'none';
                        if (match) visibleCount++;
                    });
                    const emptyRow = document.getElementById('blacklist-empty-search');
                    if (emptyRow) {
                        emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
                    }
                });
            }
        })();
    </script>

    {{-- Detail Slide-Over Modal for Full Device, IP, Browser & Location Inspection --}}
    @include('admin.blocking-logs.partials.detail-modal')
@endsection
