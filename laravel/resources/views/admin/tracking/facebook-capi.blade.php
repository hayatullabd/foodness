@extends('layouts.admin')

@section('title', 'Facebook CAPI (Conversions API)')
@section('heading', 'Tracking & Analytics')
@section('subheading', 'Manage your storefront pixel, GTM, CAPI and event tracking')

@section('content')
    @include('admin.tracking._tabs')

    @php
        $hasPixel = filled($integration->credential('pixel_id'));
        $hasToken = filled($maskedAccessToken);
        $hasTestCode = filled($integration->credential('test_event_code'));
        $hasAny = $hasPixel || $hasToken || $hasTestCode || $integration->is_active;
    @endphp

    <form method="POST" action="{{ route('admin.tracking.facebook-capi.update') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
            <span class="font-semibold text-slate-800">Conversions API (CAPI):</span>
            Sends server-side purchase conversions directly to Meta Graph API. Bypasses ad-blockers, iOS tracking restrictions, and network drops. If browser Pixel is also active, events are automatically deduplicated.
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="pixel_id">Pixel ID / Dataset ID</label>
                @if ($hasPixel)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.facebook-capi.revoke'),
                        'fields' => ['pixel_id'],
                        'label' => 'Revoke ID',
                        'confirm' => 'Remove Facebook CAPI Pixel / Dataset ID?',
                    ])
                @endif
            </div>
            <input
                id="pixel_id"
                name="pixel_id"
                type="text"
                value="{{ old('pixel_id', $integration->credential('pixel_id')) }}"
                class="field"
                autocomplete="off"
                placeholder="e.g. 123456789012345"
            >
            <p class="mt-1 text-xs text-muted">Usually the same Pixel ID used in Facebook Pixel.</p>
            @error('pixel_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="access_token">Conversions API Access Token</label>
                @if ($hasToken)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.facebook-capi.revoke'),
                        'fields' => ['access_token'],
                        'deactivate' => true,
                        'label' => 'Revoke Token',
                        'confirm' => 'Revoke Conversions API Access Token? CAPI will stop sending events until a new token is saved.',
                    ])
                @endif
            </div>
            <input
                id="access_token"
                name="access_token"
                type="password"
                value=""
                class="field"
                autocomplete="off"
                placeholder="{{ $maskedAccessToken ? 'Leave blank to keep current token' : 'Paste Meta System User Access Token' }}"
            >
            @if ($maskedAccessToken)
                <p class="mt-1 text-xs text-muted">Current token: <span class="font-mono tracking-wide">{{ $maskedAccessToken }}</span></p>
            @endif
            <p class="mt-1 text-xs text-muted">Generated from Meta Events Manager &rarr; Pixel Settings &rarr; Conversions API &rarr; Generate access token.</p>
            @error('access_token') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="test_event_code">Test Event Code (Optional)</label>
                @if ($hasTestCode)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.facebook-capi.revoke'),
                        'fields' => ['test_event_code'],
                        'label' => 'Clear code',
                        'confirm' => 'Clear Facebook CAPI Test Event Code?',
                    ])
                @endif
            </div>
            <input
                id="test_event_code"
                name="test_event_code"
                type="text"
                value="{{ old('test_event_code', $integration->credential('test_event_code')) }}"
                class="field"
                autocomplete="off"
                placeholder="e.g. TEST12345 (Leave empty in production)"
            >
            <p class="mt-1 text-xs text-muted">Found in Meta Events Manager &rarr; Test Events tab. Clear this field when running live ads.</p>
            @error('test_event_code') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
            Enable Facebook Conversions API (CAPI)
        </label>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <button type="submit" class="btn-primary">Save Facebook CAPI Settings</button>
            @if ($hasAny)
                @include('admin.tracking._revoke', [
                    'action' => route('admin.tracking.facebook-capi.revoke'),
                    'scope' => 'all',
                    'label' => 'Revoke all & disable',
                    'confirm' => 'Clear all Facebook CAPI codes/token and disable CAPI?',
                    'buttonClass' => 'admin-btn-danger',
                ])
            @endif
        </div>
    </form>
@endsection
