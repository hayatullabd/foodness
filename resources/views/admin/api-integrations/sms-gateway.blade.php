@extends('layouts.admin')

@section('title', 'SMS Gateway')
@section('heading', 'BulkSMSBD · SMS Gateway')
@section('subheading', 'Set your API Key and Sender ID, configure message templates, and manage SMS triggers.')

@section('content')
    @php
        $gatewayOn = (bool) old('is_active', $integration->is_active);
        $orderSms = (bool) old('order_sms', $integration->credential('order_sms', false));
        $orderStatusSms = (bool) old('order_status_sms', $integration->credential('order_status_sms', false));
        $forgotOtp = (bool) old('forgot_password_otp', $integration->credential('forgot_password_otp', false));
        $registerOtp = (bool) old('registration_otp', $integration->credential('registration_otp', false));
        $orderOtp = (bool) old('order_otp', $integration->credential('order_otp', false));
        $balanceFlash = session('sms_balance');
    @endphp

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full border border-ink/10 bg-mist px-2.5 py-1 font-mono text-[11px] text-ink">
            GET bulksmsbd.net/api/smsapi
        </span>
        <span class="text-xs text-muted">Success response code: {{ $successResponseCode }}</span>
    </div>

    {{-- Balance check --}}
    <div class="admin-card mb-4">
        <div class="admin-card-body flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-ink/5 text-ink">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75V9" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-ink">Balance</p>
                    <p class="text-lg font-semibold tabular-nums text-ink">{{ $balanceFlash !== null ? $balanceFlash : '—' }}</p>
                    <p class="text-xs text-muted">Save your API key, then check balance via getBalanceApi.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.api-integrations.sms-gateway.balance') }}">
                @csrf
                <button type="submit" class="admin-btn-secondary">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.03 4.356v4.992" />
                    </svg>
                    Check Balance
                </button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            {{-- Unified Settings & Templates Form --}}
            <form method="POST" action="{{ route('admin.api-integrations.sms-gateway.update') }}" class="admin-card overflow-hidden" id="sms-settings-form">
                @csrf
                @method('PUT')

                {{-- API Settings --}}
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">API Settings</h2>
                            <p class="text-xs text-muted">Credentials from bulksmsbd.net</p>
                        </div>
                    </div>
                </div>

                <div class="admin-card-body space-y-3.5">
                    <div>
                        <label class="admin-label" for="api_key">API Key *</label>
                        <input
                            id="api_key"
                            name="api_key"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            data-preview-api-key="{{ $maskedApiKey ?: '' }}"
                            placeholder="{{ $maskedApiKey ? 'Leave blank to keep current key' : 'Paste API key' }}"
                        >
                        @if ($maskedApiKey)
                            <p class="mt-1 text-xs text-muted">Current key: <span class="font-mono tracking-wide">{{ $maskedApiKey }}</span></p>
                        @endif
                        <p class="mt-1 text-xs text-muted">bulksmsbd.net → My Account → API</p>
                        @error('api_key') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="sender_id">Sender ID</label>
                        <input
                            id="sender_id"
                            name="sender_id"
                            type="text"
                            value="{{ old('sender_id', $integration->credential('sender_id')) }}"
                            class="field"
                            autocomplete="off"
                            placeholder="Approved masking number"
                        >
                        <p class="mt-1 text-xs text-muted">Approved masking number / Sender ID</p>
                        @error('sender_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="admin_numbers">Admin Number (comma separated)</label>
                        <input
                            id="admin_numbers"
                            name="admin_numbers"
                            type="text"
                            value="{{ old('admin_numbers', $integration->credential('admin_numbers')) }}"
                            class="field"
                            autocomplete="off"
                            placeholder="88017XXXXXXXX,88018XXXXXXXX"
                        >
                        <p class="mt-1 text-xs text-muted">SMS alerts sent to these numbers when a new order is placed</p>
                        @error('admin_numbers') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- SMS Triggers --}}
                <div class="border-t border-ink/10">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-ink">SMS Triggers</h2>
                                <p class="text-xs text-muted">Master switch and event notification toggles</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-3">
                        <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-ink">Gateway Master Switch</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide {{ $gatewayOn ? 'text-sage' : 'text-muted' }}">{{ $gatewayOn ? 'On' : 'Off' }}</span>
                                </div>
                                <p class="text-xs text-muted">Enable or completely disable all outgoing SMS messages from the store.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked($gatewayOn)>
                                <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                            </label>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            {{-- Order Placed SMS --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Order Placed SMS</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $orderSms ? 'text-sage' : 'text-muted' }}">{{ $orderSms ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Customer &amp; Admin order notifications</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="order_sms" value="1" class="peer sr-only" @checked($orderSms)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Order Status Update SMS --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Order Status SMS</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $orderStatusSms ? 'text-sage' : 'text-muted' }}">{{ $orderStatusSms ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Customer SMS when order status changes</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="order_status_sms" value="1" class="peer sr-only" @checked($orderStatusSms)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Forgot Password OTP --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Forgot Password OTP</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $forgotOtp ? 'text-sage' : 'text-muted' }}">{{ $forgotOtp ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Reset verification code via SMS</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="forgot_password_otp" value="1" class="peer sr-only" @checked($forgotOtp)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Registration OTP --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Registration OTP</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $registerOtp ? 'text-sage' : 'text-muted' }}">{{ $registerOtp ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Account verification during registration</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="registration_otp" value="1" class="peer sr-only" @checked($registerOtp)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Order Place OTP Verification --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5 sm:col-span-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-ink">Order Placement OTP Verification</p>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20">Fraud Prevention</span>
                                        </div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $orderOtp ? 'text-sage' : 'text-muted' }}">{{ $orderOtp ? 'Active' : 'Disabled' }}</p>
                                        <p class="mt-1 text-xs text-muted">Require customers to verify their 11-digit phone number with an SMS OTP before completing any order.</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="order_otp" value="1" class="peer sr-only" @checked($orderOtp)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Message Templates Section --}}
                <div class="border-t border-ink/10">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-ink">Message Templates</h2>
                                <p class="text-xs text-muted">Customize SMS content with dynamic placeholders for orders, status updates, and OTPs</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-4">
                        @foreach ($templates as $tpl)
                            @php
                                $val = old($tpl['key'], $tpl['value']);
                            @endphp
                            <div class="rounded-lg border border-ink/10 bg-mist/20 p-3.5 transition focus-within:border-ink/25 focus-within:bg-white" data-template-item>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wider text-ink" for="template_{{ $tpl['key'] }}">
                                            {{ $tpl['label'] }}
                                        </label>
                                        <p class="text-[11px] text-muted">{{ $tpl['description'] }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-[11px] font-medium text-muted transition hover:text-ink underline"
                                        data-reset-btn
                                        data-target="template_{{ $tpl['key'] }}"
                                        data-default="{{ $tpl['default'] }}"
                                    >
                                        Reset to default
                                    </button>
                                </div>

                                {{-- Placeholder Tags --}}
                                <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[11px] font-medium text-muted">Insert Tag:</span>
                                    @foreach ($tpl['placeholders'] as $tag)
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded border border-ink/10 bg-white px-1.5 py-0.5 font-mono text-[11px] text-ink transition hover:border-ink/30 hover:bg-mist active:scale-95"
                                            data-insert-tag
                                            data-target="template_{{ $tpl['key'] }}"
                                            data-tag="{{ $tag }}"
                                            title="Click to insert {{ $tag }}"
                                        >
                                            + {{ $tag }}
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Textarea --}}
                                <div>
                                    <textarea
                                        id="template_{{ $tpl['key'] }}"
                                        name="{{ $tpl['key'] }}"
                                        rows="3"
                                        class="field font-sans text-xs leading-relaxed"
                                        placeholder="Enter template message..."
                                        data-template-textarea
                                        data-sample='@json($tpl['sample'])'
                                    >{{ $val }}</textarea>
                                </div>

                                @error($tpl['key'])
                                    <p class="mt-1 text-xs text-accent">{{ $message }}</p>
                                @enderror

                                {{-- Counter --}}
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium text-ink" data-char-count>0</span> characters
                                        <span class="text-ink/30">·</span>
                                        <span class="font-medium text-ink" data-sms-count>1</span> SMS (<span data-sms-encoding>GSM</span>)
                                    </div>
                                </div>

                                {{-- Live Preview --}}
                                <div class="mt-2 rounded border border-ink/10 bg-white/90 p-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-semibold uppercase tracking-wider text-muted">Live Preview:</span>
                                        <span class="text-[10px] text-muted">Sample data applied</span>
                                    </div>
                                    <p class="mt-1 font-mono text-xs text-ink leading-relaxed" data-template-preview></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between border-t border-ink/10 bg-mist/30 px-4 py-3">
                    <p class="text-xs text-muted">Changes take effect immediately for new outgoing SMS.</p>
                    <button type="submit" class="admin-btn-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        Save Settings &amp; Templates
                    </button>
                </div>
            </form>

            {{-- Test SMS Card --}}
            <form method="POST" action="{{ route('admin.api-integrations.sms-gateway.test') }}" class="admin-card">
                @csrf
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sage/10 text-sage">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Test SMS</h2>
                            <p class="text-xs text-muted">Send a live test message through BulkSMSBD</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="test_number">Recipient Number</label>
                            <input id="test_number" name="test_number" type="text" value="{{ old('test_number') }}" class="field" placeholder="88017XXXXXXXX" required>
                            @error('test_number') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="admin-label" for="test_message">Message Text</label>
                                <span class="text-[11px] text-muted">Quick fill:</span>
                            </div>
                            <div class="mb-1 flex flex-wrap gap-1">
                                <button type="button" class="text-[10px] text-ink hover:underline rounded bg-mist px-1.5 py-0.5" data-fill-test="Test SMS from Foodness">Simple</button>
                                <button type="button" class="text-[10px] text-ink hover:underline rounded bg-mist px-1.5 py-0.5" data-fill-test="Foodness: Your order AS-DEMO has been placed. Total: Tk 1250.00. Thank you!">Order SMS</button>
                                <button type="button" class="text-[10px] text-ink hover:underline rounded bg-mist px-1.5 py-0.5" data-fill-test="Foodness verification code: 849201">OTP SMS</button>
                            </div>
                            <input id="test_message" name="test_message" type="text" value="{{ old('test_message', 'Test SMS from Amar Style') }}" class="field" required>
                            @error('test_message') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button type="submit" class="admin-btn-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Send Test Message
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4 lg:col-span-2">
            {{-- Request Preview --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 font-mono text-[10px] font-bold text-ink">&lt;/&gt;</span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Request Preview</h2>
                            <p class="text-xs text-muted">Live sample GET request sent to BulkSMSBD</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body">
                    <pre id="sms-request-preview" class="overflow-x-auto rounded-md bg-ink px-3 py-3 font-mono text-[11px] leading-relaxed text-mist">GET {{ $smsApiUrl }}

api_key={{ $previewQuery['api_key'] }}
type={{ $previewQuery['type'] }}
number={{ $previewQuery['number'] }}
senderid={{ $previewQuery['senderid'] }}
message={{ $previewQuery['message'] }}</pre>
                    <p class="mt-2 text-xs text-muted">Preview updates automatically when credentials change. API key is safely masked.</p>
                </div>
            </div>

            {{-- Placeholders Guide --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Available Placeholders</h2>
                            <p class="text-xs text-muted">Dynamic tags replaced during sending</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="space-y-2 text-xs">
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{order_id}</code>
                            <span class="text-muted text-right">Invoice number (e.g. AS-261021-001)</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{customer_name}</code>
                            <span class="text-muted text-right">Customer full name</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{phone}</code>
                            <span class="text-muted text-right">Customer phone number</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{total}</code>
                            <span class="text-muted text-right">Order total amount</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{status}</code>
                            <span class="text-muted text-right">Order status (e.g. Shipped)</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{company_name}</code>
                            <span class="text-muted text-right">Store / App name</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{otp}</code>
                            <span class="text-muted text-right">Verification OTP security code</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <code class="font-mono text-[11px] font-semibold text-ink">{tracking_number}</code>
                            <span class="text-muted text-right">Courier tracking code</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Guide --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Quick Guide</h2>
                            <p class="text-xs text-muted">Get sending in a few steps</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body space-y-3">
                    <ol class="list-decimal space-y-1.5 pl-4 text-sm text-ink">
                        <li>Log in to <span class="font-medium">bulksmsbd.net</span>.</li>
                        <li>Collect your API key from My Account → API.</li>
                        <li>Sender ID must be approved by the operator.</li>
                        <li>Customize your message templates and save.</li>
                    </ol>
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        Success response code is configured as: <span class="font-semibold">{{ $successResponseCode }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            // Live Query Preview
            const senderInput = document.getElementById('sender_id');
            const apiInput = document.getElementById('api_key');
            const preview = document.getElementById('sms-request-preview');

            if (senderInput && apiInput && preview) {
                const storedMasked = apiInput.dataset.previewApiKey || '(api_key)';

                const renderPreview = () => {
                    const sender = senderInput.value.trim() || '(senderid)';
                    const typed = apiInput.value.trim();
                    const key = typed
                        ? (typed.length <= 4 ? '•'.repeat(typed.length) : '•'.repeat(Math.max(8, typed.length - 4)) + typed.slice(-4))
                        : (storedMasked || '(api_key)');

                    preview.textContent =
`GET {{ $smsApiUrl }}

api_key=${key}
type=text
number=8801711111111
senderid=${sender}
message={{ addslashes($previewQuery['message']) }}`;
                };

                senderInput.addEventListener('input', renderPreview);
                apiInput.addEventListener('input', renderPreview);
            }

            // Tag Insertion into Textarea
            document.querySelectorAll('[data-insert-tag]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetId = btn.dataset.target;
                    const tag = btn.dataset.tag;
                    const textarea = document.getElementById(targetId);
                    if (!textarea) return;

                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = textarea.value;

                    textarea.value = text.substring(0, start) + tag + text.substring(end);
                    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
                    textarea.focus();
                    textarea.dispatchEvent(new Event('input'));
                });
            });

            // Reset to Default Template
            document.querySelectorAll('[data-reset-btn]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetId = btn.dataset.target;
                    const defaultText = btn.dataset.default;
                    const textarea = document.getElementById(targetId);
                    if (!textarea) return;

                    textarea.value = defaultText;
                    textarea.dispatchEvent(new Event('input'));
                });
            });

            // Live Template Counter and Render Preview
            const updateTemplateItem = (item) => {
                const textarea = item.querySelector('[data-template-textarea]');
                const charCountEl = item.querySelector('[data-char-count]');
                const smsCountEl = item.querySelector('[data-sms-count]');
                const encodingEl = item.querySelector('[data-sms-encoding]');
                const previewEl = item.querySelector('[data-template-preview]');
                if (!textarea) return;

                const text = textarea.value;
                let sample = {};
                try {
                    sample = JSON.parse(textarea.dataset.sample || '{}');
                } catch (e) {}

                // Non-GSM characters check (Unicode / Bengali)
                const isUnicode = /[^\u0000-\u007F]/.test(text);
                const length = text.length;
                let smsParts = 1;
                if (isUnicode) {
                    smsParts = length === 0 ? 1 : (length <= 70 ? 1 : Math.ceil(length / 67));
                } else {
                    smsParts = length === 0 ? 1 : (length <= 160 ? 1 : Math.ceil(length / 153));
                }

                if (charCountEl) charCountEl.textContent = length;
                if (smsCountEl) smsCountEl.textContent = smsParts;
                if (encodingEl) encodingEl.textContent = isUnicode ? 'Unicode / Bengali' : 'Standard GSM';

                // Render with sample variables
                let rendered = text;
                for (const [k, v] of Object.entries(sample)) {
                    rendered = rendered.replaceAll('{' + k + '}', v).replaceAll('{ ' + k + ' }', v);
                }
                if (previewEl) {
                    previewEl.textContent = rendered || '(Template is empty)';
                }
            };

            document.querySelectorAll('[data-template-item]').forEach(item => {
                const textarea = item.querySelector('[data-template-textarea]');
                if (textarea) {
                    textarea.addEventListener('input', () => updateTemplateItem(item));
                    updateTemplateItem(item);
                }
            });

            // Quick Fill for Test SMS
            document.querySelectorAll('[data-fill-test]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const testInput = document.getElementById('test_message');
                    if (testInput) {
                        testInput.value = btn.dataset.fillTest;
                        testInput.focus();
                    }
                });
            });
        })();
    </script>
@endsection
