@extends('layouts.admin')

@section('title', 'Google Tag Manager & GA4')
@section('heading', 'Tracking & Analytics')
@section('subheading', 'Manage your storefront pixel, GTM, CAPI and event tracking')

@section('content')
    @include('admin.tracking._tabs')

    @php
        $hasGtm = filled($integration->credential('gtm_container_id'));
        $hasServerUrl = filled($integration->credential('custom_gtm_server_url'));
        $hasGa4 = filled($integration->credential('ga4_measurement_id'));
        $hasSecret = filled($maskedApiSecret);
        $hasAds = filled($integration->credential('google_ads_id'));
        $hasAny = $hasGtm || $hasServerUrl || $hasGa4 || $hasSecret || $hasAds || $integration->is_active;
    @endphp

    <form method="POST" action="{{ route('admin.tracking.google.update') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="gtm_container_id">GTM Container ID</label>
                @if ($hasGtm)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.google.revoke'),
                        'fields' => ['gtm_container_id'],
                        'label' => 'Revoke GTM',
                        'confirm' => 'Revoke Google Tag Manager Container ID?',
                    ])
                @endif
            </div>
            <input
                id="gtm_container_id"
                name="gtm_container_id"
                type="text"
                value="{{ old('gtm_container_id', $integration->credential('gtm_container_id')) }}"
                class="field"
                autocomplete="off"
                placeholder="GTM-XXXXXXX"
            >
            <p class="mt-1 text-xs text-muted">Primary Google integration. Injects the standard GTM snippet in &lt;head&gt; and &lt;noscript&gt; after &lt;body&gt;.</p>
            @error('gtm_container_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="custom_gtm_server_url">Custom GTM Server Container URL (Optional)</label>
                @if ($hasServerUrl)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.google.revoke'),
                        'fields' => ['custom_gtm_server_url'],
                        'label' => 'Clear URL',
                        'confirm' => 'Clear custom GTM server URL?',
                    ])
                @endif
            </div>
            <input
                id="custom_gtm_server_url"
                name="custom_gtm_server_url"
                type="url"
                value="{{ old('custom_gtm_server_url', $integration->credential('custom_gtm_server_url')) }}"
                class="field"
                autocomplete="off"
                placeholder="https://ss.foodness.com"
            >
            <p class="mt-1 text-xs text-muted">If using a first-party Server-Side GTM domain. Leave blank to default to https://www.googletagmanager.com.</p>
            @error('custom_gtm_server_url') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-3 border-t border-ink/10 pt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Direct Server-Side GA4 &amp; Google Ads</p>
            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <label class="admin-label !mb-0" for="ga4_measurement_id">GA4 Measurement ID</label>
                    @if ($hasGa4)
                        @include('admin.tracking._revoke', [
                            'action' => route('admin.tracking.google.revoke'),
                            'fields' => ['ga4_measurement_id'],
                            'label' => 'Revoke GA4 ID',
                            'confirm' => 'Revoke GA4 Measurement ID?',
                        ])
                    @endif
                </div>
                <input
                    id="ga4_measurement_id"
                    name="ga4_measurement_id"
                    type="text"
                    value="{{ old('ga4_measurement_id', $integration->credential('ga4_measurement_id')) }}"
                    class="field"
                    autocomplete="off"
                    placeholder="G-XXXXXXXX"
                >
                @error('ga4_measurement_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <label class="admin-label !mb-0" for="ga4_api_secret">GA4 API Secret (Measurement Protocol)</label>
                    @if ($hasSecret)
                        @include('admin.tracking._revoke', [
                            'action' => route('admin.tracking.google.revoke'),
                            'fields' => ['ga4_api_secret'],
                            'label' => 'Revoke Secret',
                            'confirm' => 'Revoke GA4 API Secret?',
                        ])
                    @endif
                </div>
                <input
                    id="ga4_api_secret"
                    name="ga4_api_secret"
                    type="password"
                    value=""
                    class="field"
                    autocomplete="off"
                    placeholder="{{ !empty($maskedApiSecret) ? 'Leave blank to keep current secret' : 'Paste GA4 Measurement Protocol API Secret' }}"
                >
                @if (! empty($maskedApiSecret))
                    <p class="mt-1 text-xs text-muted">Current secret: <span class="font-mono tracking-wide">{{ $maskedApiSecret }}</span></p>
                @endif
                <p class="mt-1 text-xs text-muted">Generated from GA4 Admin &rarr; Data Streams &rarr; Measurement Protocol API secrets. Enables direct non-blocking server-side events.</p>
                @error('ga4_api_secret') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <label class="admin-label !mb-0" for="google_ads_id">Google Ads ID</label>
                    @if ($hasAds)
                        @include('admin.tracking._revoke', [
                            'action' => route('admin.tracking.google.revoke'),
                            'fields' => ['google_ads_id'],
                            'label' => 'Revoke Ads ID',
                            'confirm' => 'Revoke Google Ads ID?',
                        ])
                    @endif
                </div>
                <input
                    id="google_ads_id"
                    name="google_ads_id"
                    type="text"
                    value="{{ old('google_ads_id', $integration->credential('google_ads_id')) }}"
                    class="field"
                    autocomplete="off"
                    placeholder="AW-XXXXXXXX"
                >
                @error('google_ads_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
            Enable Google Tag Manager &amp; GA4
        </label>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <button type="submit" class="btn-primary">Save Google Tag Manager</button>
            @if ($hasAny)
                @include('admin.tracking._revoke', [
                    'action' => route('admin.tracking.google.revoke'),
                    'scope' => 'all',
                    'label' => 'Revoke all & disable',
                    'confirm' => 'Clear all GTM / GA4 / Ads codes and disable Google tracking?',
                    'buttonClass' => 'admin-btn-danger',
                ])
            @endif
        </div>
    </form>
@endsection
