@extends('layouts.admin')

@section('title', 'Email Settings')
@section('heading', 'System Settings')
@section('subheading', 'Set up SMTP credentials, configure notification triggers, and customize email templates.')

@section('content')
    @php
        $emailActive = (bool) old('is_active', $emailSetting->is_active ?? true);
        $triggers = (array) old('triggers', $emailSetting->triggers ?? []);
        $orderEmail = (bool) old('order_email', $emailSetting->trigger('order_email', true));
        $adminOrderEmail = (bool) old('admin_order_email', $emailSetting->trigger('admin_order_email', true));
        $statusEmail = (bool) old('order_status_email', $emailSetting->trigger('order_status_email', false));
        $welcomeEmail = (bool) old('welcome_email', $emailSetting->trigger('welcome_email', false));
        $resetEmail = (bool) old('password_reset_email', $emailSetting->trigger('password_reset_email', false));
    @endphp

    <div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full border border-ink/10 bg-mist px-2.5 py-1 font-mono text-[11px] text-ink">
            SMTP · {{ strtoupper($emailSetting->mailer ?: 'smtp') }}
        </span>
        <span class="text-xs text-muted">Status: {{ $emailSetting->isConfigured() ? 'Configured' : 'Not Configured' }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            {{-- Main Form for SMTP, Triggers and Templates --}}
            <form method="POST" action="{{ route('admin.email-settings.update') }}" class="admin-card overflow-hidden" id="email-settings-form">
                @csrf
                @method('PUT')

                {{-- SMTP Credentials --}}
                <div class="admin-card-header flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Email Configuration (SMTP)</h2>
                            <p class="text-xs text-muted">Outgoing mail server connection credentials</p>
                        </div>
                    </div>

                    @if ($emailSetting->isConfigured())
                        <button
                            type="button"
                            onclick="if(confirm('Are you sure you want to remove and unconfigure SMTP credentials? This will wipe the host, username, password and disable email sending.')) document.getElementById('reset-email-form').submit();"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 hover:underline cursor-pointer"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Unconfigure / Remove Credentials
                        </button>
                    @endif
                </div>

                <div class="admin-card-body space-y-3.5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="mailer">Mailer Driver <span class="text-accent">*</span></label>
                            <input id="mailer" name="mailer" type="text" value="{{ old('mailer', $emailSetting->mailer ?: 'smtp') }}" required class="field" placeholder="smtp" autocomplete="off">
                            @error('mailer') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="host">Mail Host <span class="text-accent">*</span></label>
                            <input id="host" name="host" type="text" value="{{ old('host', $emailSetting->host) }}" required class="field" placeholder="smtp.gmail.com" autocomplete="off">
                            @error('host') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="port">Mail Port <span class="text-accent">*</span></label>
                            <select id="port" name="port" required class="field">
                                @foreach ($ports as $port)
                                    @php
                                        $portLabel = match ($port) {
                                            587 => '587 (TLS / STARTTLS)',
                                            465 => '465 (SSL)',
                                            25 => '25 (Standard)',
                                            2525 => '2525 (Alternative)',
                                            default => (string) $port,
                                        };
                                    @endphp
                                    <option value="{{ $port }}" @selected((int) old('port', $emailSetting->port) === $port)>{{ $portLabel }}</option>
                                @endforeach
                            </select>
                            @error('port') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="encryption">Encryption Protocol <span class="text-accent">*</span></label>
                            <select id="encryption" name="encryption" required class="field">
                                @php
                                    $currentEncryption = old('encryption', $emailSetting->encryption ?: 'none');
                                @endphp
                                <option value="tls" @selected($currentEncryption === 'tls')>TLS</option>
                                <option value="ssl" @selected($currentEncryption === 'ssl')>SSL</option>
                                <option value="none" @selected($currentEncryption === 'none' || $currentEncryption === null)>None</option>
                            </select>
                            @error('encryption') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="username">SMTP Username <span class="text-accent">*</span></label>
                            <input id="username" name="username" type="text" value="{{ old('username', $emailSetting->username) }}" required class="field" placeholder="your-email@domain.com" autocomplete="off">
                            @error('username') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="password">SMTP Password <span class="text-accent">*</span></label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                value=""
                                class="field"
                                autocomplete="new-password"
                                placeholder="{{ $emailSetting->password ? 'Leave blank to keep current password' : 'Enter SMTP password' }}"
                                @required(blank($emailSetting->password))
                            >
                            @if ($emailSetting->password)
                                <p class="mt-1 text-xs text-muted">A password is saved. Leave blank to keep it.</p>
                            @endif
                            @error('password') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="from_address">From Email Address <span class="text-accent">*</span></label>
                            <input id="from_address" name="from_address" type="email" value="{{ old('from_address', $emailSetting->from_address) }}" required class="field" placeholder="no-reply@yourdomain.com" autocomplete="off">
                            @error('from_address') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="from_name">From Sender Name <span class="text-accent">*</span></label>
                            <input id="from_name" name="from_name" type="text" value="{{ old('from_name', $emailSetting->from_name) }}" required class="field" placeholder="Foodness" autocomplete="off">
                            @error('from_name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="admin-label" for="admin_emails">Admin Notification Emails (comma separated)</label>
                        <input
                            id="admin_emails"
                            name="admin_emails"
                            type="text"
                            value="{{ old('admin_emails', $emailSetting->admin_emails) }}"
                            class="field"
                            placeholder="admin@yourdomain.com, orders@yourdomain.com"
                        >
                        <p class="mt-1 text-xs text-muted">New order alerts will be emailed to these addresses.</p>
                        @error('admin_emails') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Email Triggers --}}
                <div class="border-t border-ink/10">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-ink">Email Triggers</h2>
                                <p class="text-xs text-muted">Master switch and individual notification toggles</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-3">
                        {{-- Master Toggle --}}
                        <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-ink">Email Master Switch</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide {{ $emailActive ? 'text-sage' : 'text-muted' }}">{{ $emailActive ? 'On' : 'Off' }}</span>
                                </div>
                                <p class="text-xs text-muted">Enable or completely disable all outgoing email messages from the store.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked($emailActive)>
                                <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                            </label>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            {{-- Order Placed (Customer) --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Customer Order Email</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $orderEmail ? 'text-sage' : 'text-muted' }}">{{ $orderEmail ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Customer receipt on order placement</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="order_email" value="1" class="peer sr-only" @checked($orderEmail)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Order Placed (Admin) --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Admin Order Alert</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $adminOrderEmail ? 'text-sage' : 'text-muted' }}">{{ $adminOrderEmail ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Admin alerts on new orders</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="admin_order_email" value="1" class="peer sr-only" @checked($adminOrderEmail)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Order Status Update --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Order Status Update</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $statusEmail ? 'text-sage' : 'text-muted' }}">{{ $statusEmail ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Customer email on status change</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="order_status_email" value="1" class="peer sr-only" @checked($statusEmail)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Customer Welcome --}}
                            <div class="rounded-md border border-ink/10 bg-white px-3 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">Customer Welcome</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide {{ $welcomeEmail ? 'text-sage' : 'text-muted' }}">{{ $welcomeEmail ? 'On' : 'Off' }}</p>
                                        <p class="mt-1 text-xs text-muted">Welcome email on registration</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" name="welcome_email" value="1" class="peer sr-only" @checked($welcomeEmail)>
                                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Email Templates Section --}}
                <div class="border-t border-ink/10">
                    <div class="admin-card-header">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-ink">Email Templates</h2>
                                <p class="text-xs text-muted">Customize notification subjects and email bodies with dynamic tags</p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-card-body space-y-4">
                        @foreach ($templates as $tpl)
                            @php
                                $valSubject = old("templates.{$tpl['key']}.subject", $tpl['subject']);
                                $valBody = old("templates.{$tpl['key']}.body", $tpl['body']);
                            @endphp
                            <div class="rounded-lg border border-ink/10 bg-mist/20 p-3.5 transition focus-within:border-ink/25 focus-within:bg-white" data-email-template-item>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wider text-ink">
                                            {{ $tpl['label'] }}
                                        </label>
                                        <p class="text-[11px] text-muted">{{ $tpl['description'] }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-[11px] font-medium text-muted transition hover:text-ink underline"
                                        data-email-reset-btn
                                        data-target-subject="email_subject_{{ $tpl['key'] }}"
                                        data-target-body="email_body_{{ $tpl['key'] }}"
                                        data-default-subject="{{ $tpl['default_subject'] }}"
                                        data-default-body="{{ $tpl['default_body'] }}"
                                    >
                                        Reset to default
                                    </button>
                                </div>

                                {{-- Placeholder Tags --}}
                                <div class="mb-2.5 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[11px] font-medium text-muted">Insert Tag:</span>
                                    @foreach ($tpl['placeholders'] as $tag)
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded border border-ink/10 bg-white px-1.5 py-0.5 font-mono text-[11px] text-ink transition hover:border-ink/30 hover:bg-mist active:scale-95"
                                            data-email-insert-tag
                                            data-target="email_body_{{ $tpl['key'] }}"
                                            data-tag="{{ $tag }}"
                                            title="Click to insert {{ $tag }}"
                                        >
                                            + {{ $tag }}
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Subject --}}
                                <div class="mb-2">
                                    <label class="admin-label !mb-1 text-[11px]" for="email_subject_{{ $tpl['key'] }}">Email Subject</label>
                                    <input
                                        id="email_subject_{{ $tpl['key'] }}"
                                        name="templates[{{ $tpl['key'] }}][subject]"
                                        type="text"
                                        value="{{ $valSubject }}"
                                        class="field text-xs font-medium"
                                        placeholder="Email Subject Line..."
                                        data-email-subject-input
                                    >
                                </div>

                                {{-- Body --}}
                                <div>
                                    <label class="admin-label !mb-1 text-[11px]" for="email_body_{{ $tpl['key'] }}">Email Body Content</label>
                                    <textarea
                                        id="email_body_{{ $tpl['key'] }}"
                                        name="templates[{{ $tpl['key'] }}][body]"
                                        rows="6"
                                        class="field font-sans text-xs leading-relaxed"
                                        placeholder="Email body text..."
                                        data-email-body-textarea
                                        data-sample='@json($tpl['sample'])'
                                    >{{ $valBody }}</textarea>
                                </div>

                                {{-- Live Rendered Preview --}}
                                <div class="mt-2.5 rounded border border-ink/10 bg-white p-3">
                                    <div class="flex items-center justify-between pb-1.5 border-b border-ink/5">
                                        <span class="text-[10px] font-semibold uppercase tracking-wider text-muted">Live Preview</span>
                                        <span class="text-[10px] text-muted">Sample data applied</span>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-ink" data-email-preview-subject></p>
                                    <div class="mt-1.5 text-xs text-ink/80 leading-relaxed whitespace-pre-line" data-email-preview-body></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between border-t border-ink/10 bg-mist/30 px-4 py-3">
                    <p class="text-xs text-muted">Changes take effect immediately for new outgoing emails.</p>
                    <button type="submit" class="admin-btn-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        Save Configuration &amp; Templates
                    </button>
                </div>
            </form>

            {{-- Send Test Email Card --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sage/10 text-sage">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Send Test Email</h2>
                            <p class="text-xs text-muted">Verify SMTP delivery with a live test message</p>
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.email-settings.test') }}" class="admin-card-body space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="test_email">Recipient Email <span class="text-accent">*</span></label>
                            <input id="test_email" name="test_email" type="email" value="{{ old('test_email') }}" required class="field" placeholder="you@domain.com">
                            @error('test_email') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label" for="test_type">Test Message Type</label>
                            <select id="test_type" name="test_type" class="field">
                                <option value="simple">Simple SMTP Test Ping</option>
                                <option value="customer_order">Customer Order Confirmation Sample</option>
                                <option value="admin_order">Admin Order Alert Sample</option>
                                <option value="order_status">Order Status Update Sample</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="admin-btn-secondary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Send Test Email
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-2">
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
                            <h2 class="text-sm font-semibold text-ink">SMTP Setup Guide</h2>
                            <p class="text-xs text-muted">Common mail server configurations</p>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body space-y-3 text-xs leading-relaxed">
                    <div>
                        <h4 class="font-semibold text-ink">Gmail / Google Workspace:</h4>
                        <p class="text-muted">Host: <code class="font-mono text-ink">smtp.gmail.com</code>, Port: <code class="font-mono text-ink">587</code> (TLS). Generate an <em>App Password</em> in your Google Account security settings.</p>
                    </div>
                    <div class="border-t border-ink/5 pt-2">
                        <h4 class="font-semibold text-ink">cPanel / Custom Hosting:</h4>
                        <p class="text-muted">Host: <code class="font-mono text-ink">mail.yourdomain.com</code>, Port: <code class="font-mono text-ink">465</code> (SSL) or <code class="font-mono text-ink">587</code> (TLS).</p>
                    </div>
                    <div class="border-t border-ink/5 pt-2">
                        <h4 class="font-semibold text-ink">Brevo / Sendinblue / Mailtrap:</h4>
                        <p class="text-muted">Use the SMTP credentials provided in your transaction email dashboard.</p>
                    </div>
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
                            <p class="text-xs text-muted">Dynamic variables replaced upon sending</p>
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
                            <code class="font-mono text-[11px] font-semibold text-ink">{total}</code>
                            <span class="text-muted text-right">Total amount in Tk</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{status}</code>
                            <span class="text-muted text-right">Order status (e.g. Shipped)</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{payment_method}</code>
                            <span class="text-muted text-right">Payment method (e.g. COD)</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{customer_address}</code>
                            <span class="text-muted text-right">Customer delivery address</span>
                        </div>
                        <div class="flex items-start justify-between gap-2 pb-1.5 border-b border-ink/5">
                            <code class="font-mono text-[11px] font-semibold text-ink">{tracking_number}</code>
                            <span class="text-muted text-right">Courier tracking code</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <code class="font-mono text-[11px] font-semibold text-ink">{company_name}</code>
                            <span class="text-muted text-right">Store / App name</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            // Tag Insertion into Textarea
            document.querySelectorAll('[data-email-insert-tag]').forEach(btn => {
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
            document.querySelectorAll('[data-email-reset-btn]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const subjectInput = document.getElementById(btn.dataset.targetSubject);
                    const bodyTextarea = document.getElementById(btn.dataset.targetBody);

                    if (subjectInput) {
                        subjectInput.value = btn.dataset.defaultSubject || '';
                        subjectInput.dispatchEvent(new Event('input'));
                    }
                    if (bodyTextarea) {
                        bodyTextarea.value = btn.dataset.defaultBody || '';
                        bodyTextarea.dispatchEvent(new Event('input'));
                    }
                });
            });

            // Live Template Preview
            const updateEmailTemplateItem = (item) => {
                const subjectInput = item.querySelector('[data-email-subject-input]');
                const bodyTextarea = item.querySelector('[data-email-body-textarea]');
                const previewSubject = item.querySelector('[data-email-preview-subject]');
                const previewBody = item.querySelector('[data-email-preview-body]');
                if (!bodyTextarea) return;

                let sample = {};
                try {
                    sample = JSON.parse(bodyTextarea.dataset.sample || '{}');
                } catch (e) {}

                // Render Subject
                if (subjectInput && previewSubject) {
                    let renderedSubject = subjectInput.value;
                    for (const [k, v] of Object.entries(sample)) {
                        renderedSubject = renderedSubject.replaceAll('{' + k + '}', v).replaceAll('{ ' + k + ' }', v);
                    }
                    previewSubject.textContent = renderedSubject || '(No subject)';
                }

                // Render Body
                if (previewBody) {
                    let renderedBody = bodyTextarea.value;
                    for (const [k, v] of Object.entries(sample)) {
                        renderedBody = renderedBody.replaceAll('{' + k + '}', v).replaceAll('{ ' + k + ' }', v);
                    }
                    previewBody.textContent = renderedBody || '(No body text)';
                }
            };

            document.querySelectorAll('[data-email-template-item]').forEach(item => {
                const subjectInput = item.querySelector('[data-email-subject-input]');
                const bodyTextarea = item.querySelector('[data-email-body-textarea]');

                if (subjectInput) {
                    subjectInput.addEventListener('input', () => updateEmailTemplateItem(item));
                }
                if (bodyTextarea) {
                    bodyTextarea.addEventListener('input', () => updateEmailTemplateItem(item));
                }
                updateEmailTemplateItem(item);
            });
        })();
    </script>

    @if ($emailSetting->isConfigured())
        <form id="reset-email-form" method="POST" action="{{ route('admin.email-settings.reset') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection
