@extends('layouts.admin')

@section('title', 'Order Restriction Settings')
@section('heading', 'Order Restriction Settings')
@section('subheading', 'Multi-vector advance protection against fake orders, bots, and spam (Device, IP, Phone & Junk filter)')

@section('content')
    @php
        $enabled = (bool) old('enabled', $settings->enabled);
        $hours = (int) old('hours', $settings->hours);
        $maxPerProduct = (int) old('max_per_product', $settings->max_per_product);

        $blockIpEnabled = (bool) old('block_ip_enabled', $settings->block_ip_enabled);
        $blockedIps = old('blocked_ips', $settings->blocked_ips);
        $maxPerIp = old('max_per_ip', $settings->max_per_ip);

        $blockPhoneEnabled = (bool) old('block_phone_enabled', $settings->block_phone_enabled);
        $blockedPhones = old('blocked_phones', $settings->blocked_phones);
        $maxPerPhone = old('max_per_phone', $settings->max_per_phone);
        $validateBdPhone = (bool) old('validate_bd_phone', $settings->validate_bd_phone ?? true);

        $blockDeviceEnabled = (bool) old('block_device_enabled', $settings->block_device_enabled);
        $blockedDevices = old('blocked_devices', $settings->blocked_devices);
        $maxPerDevice = old('max_per_device', $settings->max_per_device);

        $blockWordsEnabled = (bool) old('block_words_enabled', $settings->block_words_enabled);
        $blockedWords = old('blocked_words', $settings->blocked_words);
        $minAddressLength = old('min_address_length', $settings->min_address_length ?? 10);
    @endphp

    {{-- Navigation Tabs & Actions Toolbar --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2.5 border-b border-slate-200 bg-white px-3 py-2 rounded-lg shadow-2xs">
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            {{-- Tab 1: Restriction Settings --}}
            <button
                type="button"
                id="tab-btn-settings"
                data-tab-trigger="settings"
                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer {{ ($activeTab ?? 'settings') === 'settings' ? 'border border-slate-900 bg-slate-900 text-white shadow-2xs' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
            >
                <svg class="h-4 w-4 {{ ($activeTab ?? 'settings') === 'settings' ? 'text-emerald-400' : 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 12h9.75M10.5 18h9.75M4.5 6h.008v.008H4.5V6zm0 6h.008v.008H4.5V12zm0 6h.008v.008H4.5V18z" />
                </svg>
                Restriction Settings
            </button>

            {{-- Tab 2: Blocking History & Logs --}}
            <button
                type="button"
                id="tab-btn-history"
                data-tab-trigger="history"
                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer {{ ($activeTab ?? 'settings') === 'history' ? 'border border-emerald-600 bg-emerald-50 text-emerald-900 shadow-2xs ring-1 ring-emerald-600' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
            >
                <svg class="h-4 w-4 {{ ($activeTab ?? 'settings') === 'history' ? 'text-emerald-700' : 'text-rose-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z" />
                </svg>
                Blocking History &amp; Logs
                <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-800 px-1.5 py-0.2 text-[10px] font-bold">
                    {{ number_format($totalBlockedAttempts ?? 0) }}
                </span>
            </button>

            {{-- Tab 3: Active Blacklists --}}
            <button
                type="button"
                id="tab-btn-active-blocks"
                data-tab-trigger="active-blocks"
                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer {{ ($activeTab ?? 'settings') === 'active-blocks' ? 'border border-indigo-600 bg-indigo-50 text-indigo-900 shadow-2xs ring-1 ring-indigo-600' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
            >
                <svg class="h-4 w-4 {{ ($activeTab ?? 'settings') === 'active-blocks' ? 'text-indigo-700' : 'text-emerald-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                Active Blacklists (Block &amp; Unblock)
                <span class="inline-flex items-center rounded-full bg-indigo-100 text-indigo-800 px-1.5 py-0.2 text-[10px] font-bold">
                    {{ count($activeBlacklistRows ?? []) }}
                </span>
            </button>
        </div>

        {{-- Right Actions Toolbar --}}
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $enabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $enabled ? 'bg-emerald-500 animate-pulse' : 'bg-rose-400' }}"></span>
                {{ $enabled ? 'Protection Active' : 'Protection Disabled' }}
            </span>

            <a
                href="{{ route('admin.blocking-logs.export') }}"
                class="inline-flex items-center gap-1.5 rounded border border-emerald-700 bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-emerald-700"
                title="Download this entire audit log as a Microsoft Excel / CSV spreadsheet"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export Excel (.csv)
            </a>

            @if (($totalBlockedAttempts ?? 0) > 0 || count($activeBlacklistRows ?? []) > 0)
                <form
                    method="POST"
                    action="{{ route('admin.blocking-logs.clear') }}"
                    onsubmit="return confirm('Clear all blocking history logs and reset all active blacklists (IPs, Phones, Devices)?')"
                    class="inline-block"
                >
                    @csrf
                    <input type="hidden" name="tab" value="{{ $activeTab ?? 'settings' }}">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1 rounded border border-rose-300 bg-rose-50 px-2 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100 shadow-2xs"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Clear All
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB PANEL 1: RESTRICTION SETTINGS                                         --}}
    {{-- ========================================================================= --}}
    <div id="tab-panel-settings" class="{{ ($activeTab ?? 'settings') === 'settings' ? '' : 'hidden' }}">
        <form method="POST" action="{{ route('admin.order-restrictions.update') }}">
        @csrf
        @method('PUT')

        <div class="grid gap-4 lg:grid-cols-5">
            {{-- Left Column: 3 cols --}}
            <div class="space-y-4 lg:col-span-3">

                {{-- CARD 1: Master Switch & Time Window --}}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-indigo-50 text-indigo-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Master Protection &amp; Time Window</h3>
                                <p class="text-[11px] text-muted">Global switch and look-back window for all rate limits</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-4">
                        <div class="rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                            @include('admin.partials.toggle-switch', [
                                'name' => 'enabled',
                                'checked' => $enabled,
                                'label' => 'Enable Order Restrictions & Anti-Fraud',
                                'hint' => 'When disabled, all checkout restrictions below are bypassed.',
                            ])
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="admin-label" for="hours">Order restriction time (hours)</label>
                                <input
                                    id="hours"
                                    name="hours"
                                    type="number"
                                    min="1"
                                    max="8760"
                                    required
                                    value="{{ $hours }}"
                                    class="field"
                                    placeholder="24"
                                >
                                <p class="mt-1 text-[11px] text-muted">Time frame to calculate order counts per phone/device/IP (e.g. 24 = last 24h).</p>
                                @error('hours') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="admin-label" for="max_per_product">Max order count per product</label>
                                <input
                                    id="max_per_product"
                                    name="max_per_product"
                                    type="number"
                                    min="1"
                                    max="1000"
                                    required
                                    value="{{ $maxPerProduct }}"
                                    class="field"
                                    placeholder="2"
                                >
                                <p class="mt-1 text-[11px] text-muted">Max times a customer can order the same product in that window.</p>
                                @error('max_per_product') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: Device Level Protection --}}
                <div class="admin-card border-l-4 border-l-purple-500">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-purple-50 text-purple-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Device Protection</h3>
                                <p class="text-[11px] text-muted">Detect &amp; restrict same device / browser even if customer changes name or phone</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3.5">
                        <div class="rounded-md border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                            @include('admin.partials.toggle-switch', [
                                'name' => 'block_device_enabled',
                                'checked' => $blockDeviceEnabled,
                                'label' => 'Enable Device Protection & Fingerprint Limiting',
                                'hint' => 'Tracks persistent device tokens and automated browser tools.',
                            ])
                        </div>

                        <div>
                            <label class="admin-label" for="max_per_device">Max Orders Per Device (Within Look-Back Window)</label>
                            <input
                                id="max_per_device"
                                name="max_per_device"
                                type="number"
                                min="1"
                                max="1000"
                                value="{{ $maxPerDevice }}"
                                class="field"
                                placeholder="e.g. 2"
                            >
                            <p class="mt-1 text-[11px] text-muted">If a single phone/computer places more than this number of orders within {{ $hours }} hours, checkout is blocked.</p>
                            @error('max_per_device') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="blocked_devices">Blacklisted Device IDs &amp; User-Agents</label>
                            <textarea
                                id="blocked_devices"
                                name="blocked_devices"
                                rows="2"
                                class="field font-mono text-xs"
                                placeholder="did_xxxxxx, HeadlessChrome, python-requests, curl, etc. (Separate with commas or newlines)"
                            >{{ $blockedDevices }}</textarea>
                            <p class="mt-1 text-[11px] text-muted">Orders originating from these device IDs or user-agent signatures will be immediately denied.</p>
                            @error('blocked_devices') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- CARD 3: IP Address Level Protection --}}
                <div class="admin-card border-l-4 border-l-sky-500">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-50 text-sky-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">IP Address Protection</h3>
                                <p class="text-[11px] text-muted">Block specific malicious IP addresses, subnets, and limit orders per network</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3.5">
                        <div class="rounded-md border border-sky-100 bg-sky-50/40 px-3 py-2.5">
                            @include('admin.partials.toggle-switch', [
                                'name' => 'block_ip_enabled',
                                'checked' => $blockIpEnabled,
                                'label' => 'Enable IP Address Protection & Blacklist',
                                'hint' => 'Automatically checks both this blacklist and the IP Blocks table.',
                            ])
                        </div>

                        <div>
                            <label class="admin-label" for="max_per_ip">Max Orders Per IP (Within Look-Back Window)</label>
                            <input
                                id="max_per_ip"
                                name="max_per_ip"
                                type="number"
                                min="1"
                                max="1000"
                                value="{{ $maxPerIp }}"
                                class="field"
                                placeholder="e.g. 5"
                            >
                            <p class="mt-1 text-[11px] text-muted">Limits multiple orders originating from the same IP network within {{ $hours }} hours.</p>
                            @error('max_per_ip') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="blocked_ips">Blacklisted IP Addresses &amp; Subnets</label>
                            <textarea
                                id="blocked_ips"
                                name="blocked_ips"
                                rows="2"
                                class="field font-mono text-xs"
                                placeholder="103.145.2.45, 192.168.1.*, 10.0.0.0/24 (Separate with commas or newlines)"
                            >{{ $blockedIps }}</textarea>
                            <p class="mt-1 text-[11px] text-muted">Supports single IPv4/IPv6, wildcards (*), and CIDR ranges. Orders from these IPs will be blocked immediately.</p>
                            @error('blocked_ips') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- CARD 4: Phone Number Level Protection --}}
                <div class="admin-card border-l-4 border-l-emerald-500">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Phone Number Protection</h3>
                                <p class="text-[11px] text-muted">Block abusive phone numbers, validate BD operators, and limit velocity</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3.5">
                        <div class="rounded-md border border-emerald-100 bg-emerald-50/40 px-3 py-2.5">
                            @include('admin.partials.toggle-switch', [
                                'name' => 'block_phone_enabled',
                                'checked' => $blockPhoneEnabled,
                                'label' => 'Enable Phone Number Protection & Blacklist',
                                'hint' => 'Checks blacklist and enforces maximum orders allowed per customer phone.',
                            ])
                        </div>

                        <div class="rounded-md border border-emerald-100 bg-emerald-50/20 px-3 py-2.5">
                            @include('admin.partials.toggle-switch', [
                                'name' => 'validate_bd_phone',
                                'checked' => $validateBdPhone,
                                'label' => 'Strict BD Phone Number Validation (013-019)',
                                'hint' => 'Rejects fake non-operator numbers (e.g. 012..., 011...) or repeating dummy digits (01711111111, 01700000000).',
                            ])
                        </div>

                        <div>
                            <label class="admin-label" for="max_per_phone">Max Orders Per Phone (Within Look-Back Window)</label>
                            <input
                                id="max_per_phone"
                                name="max_per_phone"
                                type="number"
                                min="1"
                                max="1000"
                                value="{{ $maxPerPhone }}"
                                class="field"
                                placeholder="e.g. 3"
                            >
                            <p class="mt-1 text-[11px] text-muted">If a single phone places more than this number of orders within {{ $hours }} hours, subsequent orders are blocked.</p>
                            @error('max_per_phone') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="blocked_phones">Blacklisted Phone Numbers</label>
                            <textarea
                                id="blocked_phones"
                                name="blocked_phones"
                                rows="2"
                                class="field font-mono text-xs"
                                placeholder="01712345678, 01898765432, +8801911223344 (Separate with commas or newlines)"
                            >{{ $blockedPhones }}</textarea>
                            <p class="mt-1 text-[11px] text-muted">Supports 11-digit or +880 format. Customers with these numbers will be prohibited from ordering.</p>
                            @error('blocked_phones') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- CARD 5: Anti-Spam Address & Content Filter --}}
                <div class="admin-card border-l-4 border-l-amber-500">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-amber-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Anti-Spam &amp; Junk Order Filter</h3>
                                <p class="text-[11px] text-muted">Filter out fake random addresses, junk text, and dummy names</p>
                            </div>
                        </div>
                    </div>
                    <div class="admin-card-body space-y-3.5">
                        <div class="rounded-md border border-amber-100 bg-amber-50/40 px-3 py-2.5">
                            @include('admin.partials.toggle-switch', [
                                'name' => 'block_words_enabled',
                                'checked' => $blockWordsEnabled,
                                'label' => 'Enable Anti-Spam Content Filter',
                                'hint' => 'Rejects orders with suspicious junk keywords or incomplete addresses.',
                            ])
                        </div>

                        <div>
                            <label class="admin-label" for="min_address_length">Minimum Address Length (Characters)</label>
                            <input
                                id="min_address_length"
                                name="min_address_length"
                                type="number"
                                min="3"
                                max="100"
                                value="{{ $minAddressLength }}"
                                class="field"
                                placeholder="10"
                            >
                            <p class="mt-1 text-[11px] text-muted">Prevents customers from typing incomplete 1-word addresses like "Dhaka" or "asdf". Requires at least this many characters.</p>
                            @error('min_address_length') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="blocked_words">Blacklisted Junk Words &amp; Patterns</label>
                            <textarea
                                id="blocked_words"
                                name="blocked_words"
                                rows="2"
                                class="field font-mono text-xs"
                                placeholder="test, fake, asdf, qwer, 12345, null, testing (Separate with commas or newlines)"
                            >{{ $blockedWords }}</textarea>
                            <p class="mt-1 text-[11px] text-muted">If customer name or address contains any of these words, the checkout attempt is blocked.</p>
                            @error('blocked_words') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="admin-btn-primary w-full py-3 text-sm font-semibold shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Update Settings
                </button>

            </div>

            {{-- Right Column: 2 cols --}}
            <div class="space-y-4 lg:col-span-2">

                {{-- Current Settings Card (Matching Tests) --}}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-semibold text-ink">Current Settings</h3>
                        </div>
                    </div>
                    <div class="admin-card-body divide-y divide-ink/5 text-xs">
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">Status</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $settings->enabled ? 'text-emerald-700' : 'text-slate-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $settings->enabled ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                {{ $settings->enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">Time</span>
                            <span class="font-semibold text-slate-900">{{ $settings->hours }} Hours</span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">Max</span>
                            <span class="font-semibold text-slate-900">{{ $settings->max_per_product }} Orders</span>
                        </div>
                    </div>
                </div>

                {{-- Protection Vectors Status Card --}}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-semibold text-ink">Protection Vectors</h3>
                        </div>
                    </div>
                    <div class="admin-card-body divide-y divide-ink/5 text-xs">
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">Device Protection</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $settings->block_device_enabled ? 'text-purple-700' : 'text-slate-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $settings->block_device_enabled ? 'bg-purple-500' : 'bg-slate-300' }}"></span>
                                {{ $settings->block_device_enabled ? 'Active (Max '.($settings->max_per_device ?? '∞').'/dev)' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">IP Address Protection</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $settings->block_ip_enabled ? 'text-sky-700' : 'text-slate-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $settings->block_ip_enabled ? 'bg-sky-500' : 'bg-slate-300' }}"></span>
                                {{ $settings->block_ip_enabled ? 'Active (Max '.($settings->max_per_ip ?? '∞').'/IP)' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">Phone Number Protection</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $settings->block_phone_enabled ? 'text-emerald-700' : 'text-slate-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $settings->block_phone_enabled ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                {{ $settings->block_phone_enabled ? 'Active (Max '.($settings->max_per_phone ?? '∞').'/phone)' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-700">Anti-Spam Filter</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $settings->block_words_enabled ? 'text-amber-700' : 'text-slate-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $settings->block_words_enabled ? 'bg-amber-500' : 'bg-slate-300' }}"></span>
                                {{ $settings->block_words_enabled ? 'Active (Min '.$settings->min_address_length.' chars)' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Direct Link to Blocking Logs & Blacklists --}}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted">Blocking History &amp; Blacklists</h3>
                            <a href="{{ route('admin.blocking-logs.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">
                                View Logs &rarr;
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body text-xs text-muted">
                        <p>View real-time audit logs of prevented fake orders, ban or unblock individual IPs, phones, and devices, and clear blocking history logs anytime.</p>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('admin.blocking-logs.index', ['tab' => 'history']) }}" class="admin-btn-secondary py-1 text-xs">
                                View History Log
                            </a>
                            <a href="{{ route('admin.blocking-logs.index', ['tab' => 'active-blocks']) }}" class="admin-btn-secondary py-1 text-xs">
                                Manage Blacklists
                            </a>
                        </div>
                    </div>
                </div>

                {{-- How does it work? Card (Matching Tests) --}}
                <div class="rounded-lg border border-indigo-200 bg-indigo-50/70 p-4 text-xs text-indigo-950">
                    <div class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <div class="space-y-1.5">
                            <h4 class="font-semibold text-indigo-950">How does it work?</h4>
                            <p class="text-indigo-900/80">Every checkout attempt is scanned in real-time across four defense layers before any database record is created:</p>
                            <ul class="list-disc space-y-1 pl-4 text-indigo-900/80">
                                <li><strong>Device:</strong> Tracks persistent device cookie/ID and stops rapid bot submissions from the same browser.</li>
                                <li><strong>IP:</strong> Blocks known attacker IPs, CIDR subnets, and caps orders per IP within the time window.</li>
                                <li><strong>Phone:</strong> Enforces authentic 11-digit Bangladeshi mobile formats (013-019), rejects dummy patterns (e.g. 01700000000), and checks the blacklist.</li>
                                <li><strong>Anti-Spam:</strong> Drops requests with test words (fake, test, asdf) or inadequate delivery addresses.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB PANEL 2: BLOCKING HISTORY & LOGS (Excel Audit Ledger)                 --}}
    {{-- ========================================================================= --}}
    <div id="tab-panel-history" class="{{ ($activeTab ?? 'settings') === 'history' ? '' : 'hidden' }} space-y-3">
        {{-- Excel Filter Toolbar --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-2.5 shadow-2xs">
            <form method="GET" action="{{ route('admin.order-restrictions.edit') }}" class="flex flex-wrap items-center justify-between gap-2">
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
                                href="{{ route('admin.order-restrictions.edit', ['tab' => 'history']) }}"
                                class="absolute right-2 top-1.5 text-xs text-slate-400 hover:text-slate-700"
                            >&times;</a>
                        @endif
                    </div>

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

                    <button type="submit" class="h-8 rounded bg-slate-200 px-3 text-xs font-semibold text-slate-800 hover:bg-slate-300">
                        Apply Filter
                    </button>
                </div>

                <div class="text-[11px] text-slate-500">
                    Showing <strong>{{ isset($logs) ? ($logs->firstItem() ?? 0) : 0 }}</strong> to <strong>{{ isset($logs) ? ($logs->lastItem() ?? 0) : 0 }}</strong> of <strong>{{ isset($logs) ? $logs->total() : 0 }}</strong> records
                </div>
            </form>
        </div>

        {{-- Audit Log Spreadsheet Table --}}
        <div class="overflow-x-auto rounded-lg border border-slate-300 bg-white shadow-2xs">
            <table class="min-w-full text-left text-xs divide-y divide-slate-200 border-collapse">
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
                    @forelse (($logs ?? []) as $index => $log)
                        @php
                            $rowNumber = (isset($logs) && method_exists($logs, 'firstItem') ? $logs->firstItem() : 1) + $index;
                            $vectorBadge = match($log->block_type) {
                                'ip_blacklist' => 'bg-rose-100 text-rose-800 border-rose-300',
                                'ip_rate_limit' => 'bg-amber-100 text-amber-800 border-amber-300',
                                'phone_blacklist' => 'bg-red-100 text-red-800 border-red-300',
                                'phone_rate_limit' => 'bg-orange-100 text-orange-800 border-orange-300',
                                'invalid_phone' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                'device_blacklist' => 'bg-purple-100 text-purple-800 border-purple-300',
                                'device_rate_limit' => 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-300',
                                'spam_content' => 'bg-pink-100 text-pink-800 border-pink-300',
                                'short_address' => 'bg-sky-100 text-sky-800 border-sky-300',
                                'product_limit' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                default => 'bg-slate-100 text-slate-800 border-slate-300',
                            };
                        @endphp
                        <tr
                            data-log-details="{{ json_encode($log->detailsArray(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                            class="divide-x divide-slate-200 cursor-pointer hover:bg-emerald-50/50 transition-colors {{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }}"
                            title="Click row to view full device, IP, browser & location details"
                        >
                            <td class="px-2.5 py-2 text-center font-mono text-slate-500 bg-slate-50/80">{{ $rowNumber }}</td>
                            <td class="px-2.5 py-2 whitespace-nowrap font-mono text-slate-600 text-[11px]">
                                <div>{{ ($log->last_attempt_at ?: $log->created_at)?->format('Y-m-d H:i:s') }}</div>
                                @if ($log->last_attempt_at && $log->created_at && $log->last_attempt_at->ne($log->created_at))
                                    <div class="text-[10px] text-muted">First: {{ $log->created_at->format('M d, H:i') }}</div>
                                @endif
                            </td>
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
                            <td class="px-2 py-2 text-center whitespace-nowrap">
                                <span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold {{ ($log->attempts_count ?? 1) > 1 ? 'bg-rose-100 text-rose-800 ring-1 ring-rose-300' : 'bg-slate-100 text-slate-700' }}">
                                    {{ number_format($log->attempts_count ?? 1) }}
                                </span>
                            </td>
                            <td class="px-2.5 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-semibold {{ $vectorBadge }}">
                                    {{ str_replace('_', ' ', strtoupper($log->block_type)) }}
                                </span>
                            </td>
                            <td class="px-2.5 py-2 whitespace-nowrap font-mono font-bold text-rose-700 select-all">
                                {{ $log->offending_value ?: '-' }}
                            </td>
                            <td class="px-2.5 py-2 whitespace-nowrap font-medium text-slate-900">
                                {{ $log->customer_name ?: 'Guest / Unknown' }}
                            </td>
                            <td class="px-2.5 py-2 whitespace-nowrap font-mono text-slate-700 select-all">
                                {{ $log->phone ?: '-' }}
                            </td>
                            <td class="px-2.5 py-2 whitespace-nowrap font-mono text-slate-700 select-all">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="px-2.5 py-2 whitespace-nowrap font-mono text-slate-500 text-[11px]" title="{{ $log->device_id }}">
                                {{ $log->device_id ? \Illuminate\Support\Str::limit($log->device_id, 14) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-slate-600 text-[11px]" title="{{ $log->customer_address }}">
                                {{ $log->customer_address ? \Illuminate\Support\Str::limit($log->customer_address, 35) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-slate-800 font-medium">
                                {{ $log->reason }}
                            </td>
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
        @if (isset($logs) && method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="flex items-center justify-between border-t border-slate-200 bg-white px-3 py-2 rounded-b-lg shadow-2xs">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB PANEL 3: ACTIVE BLACKLISTS (Instant Management)                       --}}
    {{-- ========================================================================= --}}
    <div id="tab-panel-active-blocks" class="{{ ($activeTab ?? 'settings') === 'active-blocks' ? '' : 'hidden' }} space-y-3">
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
                    @forelse (($activeBlacklistRows ?? []) as $idx => $row)
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

    {{-- Instant 3-Way Tab Switching & Client-Side Search (Zero Page Reload) --}}
    <script>
        (function () {
            function switchRestrictionTab(tabKey) {
                const panels = {
                    'settings': document.getElementById('tab-panel-settings'),
                    'history': document.getElementById('tab-panel-history'),
                    'active-blocks': document.getElementById('tab-panel-active-blocks'),
                };
                const buttons = {
                    'settings': document.getElementById('tab-btn-settings'),
                    'history': document.getElementById('tab-btn-history'),
                    'active-blocks': document.getElementById('tab-btn-active-blocks'),
                };

                // Toggle panels visibility
                Object.keys(panels).forEach(function (key) {
                    if (panels[key]) {
                        panels[key].classList.toggle('hidden', key !== tabKey);
                    }
                });

                // Base inactive style
                const baseInactive = 'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer border border-slate-200 bg-white text-slate-700 hover:bg-slate-50';

                if (buttons['settings']) {
                    buttons['settings'].className = (tabKey === 'settings')
                        ? 'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer border border-slate-900 bg-slate-900 text-white shadow-2xs'
                        : baseInactive;
                }
                if (buttons['history']) {
                    buttons['history'].className = (tabKey === 'history')
                        ? 'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer border border-emerald-600 bg-emerald-50 text-emerald-900 shadow-2xs ring-1 ring-emerald-600'
                        : baseInactive;
                }
                if (buttons['active-blocks']) {
                    buttons['active-blocks'].className = (tabKey === 'active-blocks')
                        ? 'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 font-semibold transition cursor-pointer border border-indigo-600 bg-indigo-50 text-indigo-900 shadow-2xs ring-1 ring-indigo-600'
                        : baseInactive;
                }

                // Update hidden inputs in forms
                document.querySelectorAll('input[name="tab"]').forEach(function (input) {
                    input.value = tabKey;
                });

                // Update browser URL query string without reloading
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabKey);
                window.history.replaceState({ tab: tabKey }, '', url.toString());
            }

            document.querySelectorAll('[data-tab-trigger]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    switchRestrictionTab(this.getAttribute('data-tab-trigger'));
                });
            });

            window.addEventListener('popstate', function () {
                const urlParams = new URLSearchParams(window.location.search);
                switchRestrictionTab(urlParams.get('tab') || 'settings');
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
