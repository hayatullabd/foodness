@extends('layouts.admin')

@section('title', 'Courier Ratio Rules')
@section('heading', 'Courier Ratio Rules')
@section('subheading', 'Block checkout when courier success ratio is too low')

@section('content')
    @php
        $enabled = (bool) old('enabled', $settings->enabled);
        $minSuccessRatio = (int) old('min_success_ratio', $settings->min_success_ratio);
    @endphp

    <div class="mb-4 overflow-hidden rounded-lg bg-gradient-to-r from-ink to-sage px-4 py-5 text-white shadow-sm sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sage shadow-sm">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </span>
            <div>
                <h2 class="font-display text-lg font-semibold tracking-tight sm:text-xl">Courier Ratio Rules</h2>
                <p class="mt-0.5 text-sm text-white/80">Stop orders when a customer’s courier success ratio is at or below your threshold.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <form method="POST" action="{{ route('admin.fraud-order-rules.update') }}" class="admin-card">
                @csrf
                @method('PUT')

                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-ink">Ratio gate configuration</h3>
                            <p class="text-xs text-muted">Minimum courier success ratio required to order</p>
                        </div>
                    </div>
                </div>

                <div class="admin-card-body space-y-4">
                    <label class="flex items-center justify-between gap-3 rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <span>
                            <span class="block text-sm font-medium text-ink">Enable courier ratio blocking</span>
                            <span class="block text-xs text-muted">When off, checkout ignores this rule. Courier Ratio API must also be enabled.</span>
                        </span>
                        <input type="checkbox" name="enabled" value="1" class="h-4 w-4 rounded border-ink/30 text-sage focus:ring-sage" @checked($enabled)>
                    </label>

                    <div>
                        <label class="admin-label" for="min_success_ratio">Minimum success ratio (%)</label>
                        <div class="relative">
                            <input
                                id="min_success_ratio"
                                name="min_success_ratio"
                                type="number"
                                min="0"
                                max="100"
                                required
                                value="{{ $minSuccessRatio }}"
                                class="field pr-10"
                                placeholder="e.g. 50"
                            >
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-medium text-muted">%</span>
                        </div>
                        <p class="mt-1 text-xs text-muted">
                            Customers with a success ratio at or below this value cannot place an order. Default is 50.
                        </p>
                        @error('min_success_ratio') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="admin-btn-primary w-full py-2.5 text-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        Save Courier Ratio Rules
                    </button>

                    <div class="rounded-md border border-sage/25 bg-mist/80 px-3 py-3">
                        <div class="flex gap-2">
                            <span class="mt-0.5 inline-flex h-5 w-1 shrink-0 rounded-full bg-sage" aria-hidden="true"></span>
                            <div>
                                <h4 class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                                    <svg class="h-4 w-4 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                    How does it work?
                                </h4>
                                <ul class="mt-2 list-disc space-y-1 pl-4 text-xs text-muted">
                                    <li>New customers with no courier record can still order.</li>
                                    <li>Customers at or below the threshold are blocked at checkout.</li>
                                    <li>If the Courier Ratio API is down or disabled, checkout is not blocked.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-ink/5 text-ink">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.759 6.759 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-semibold text-ink">Current Settings</h3>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="space-y-2 rounded-md bg-mist px-3 py-3 text-sm">
                        <p class="flex justify-between gap-2">
                            <span class="text-muted">Status</span>
                            <span class="font-medium text-ink">{{ $settings->enabled ? 'Enabled' : 'Disabled' }}</span>
                        </p>
                        <p class="flex justify-between gap-2">
                            <span class="text-muted">Min ratio</span>
                            <span class="font-medium text-ink">{{ $settings->min_success_ratio }}%</span>
                        </p>
                        <p class="flex justify-between gap-2">
                            <span class="text-muted">Blocks when</span>
                            <span class="font-medium text-ink">≤ {{ $settings->min_success_ratio }}%</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-950">
                <div class="flex gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p>
                        <span class="font-semibold">Note:</span>
                        Requires an active Courier Ratio API key. Changes apply immediately to new checkout attempts.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
