@extends('layouts.admin')

@section('title', 'TikTok Pixel & Events API')
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

    <form method="POST" action="{{ route('admin.tracking.tiktok.update') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
            <span class="font-semibold text-slate-800">TikTok Events API:</span>
            Sends server-side events directly to TikTok Business API. Bypasses browser restrictions and ad-blockers. When both Browser Pixel and Events API are enabled, events are automatically deduplicated using the unique <code class="bg-slate-200 px-1 py-0.5 rounded text-[11px]">event_id</code>.
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="pixel_id">TikTok Pixel ID / Dataset ID</label>
                @if ($hasPixel)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.tiktok.revoke'),
                        'fields' => ['pixel_id'],
                        'deactivate' => true,
                        'label' => 'Revoke Pixel',
                        'confirm' => 'Revoke TikTok Pixel ID?',
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
                placeholder="e.g. CXXXXXXXXXXXXXXXXX"
            >
            @error('pixel_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="access_token">Events API Access Token (Optional for Server-Side)</label>
                @if ($hasToken)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.tiktok.revoke'),
                        'fields' => ['access_token'],
                        'label' => 'Revoke Token',
                        'confirm' => 'Revoke TikTok Events API Access Token?',
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
                placeholder="{{ !empty($maskedAccessToken) ? 'Leave blank to keep current token' : 'Paste TikTok Events API Access Token' }}"
            >
            @if (! empty($maskedAccessToken))
                <p class="mt-1 text-xs text-muted">Current token: <span class="font-mono tracking-wide">{{ $maskedAccessToken }}</span></p>
            @endif
            <p class="mt-1 text-xs text-muted">Generated from TikTok Ads Manager &rarr; Events &rarr; Web Events &rarr; Settings &rarr; Generate Access Token.</p>
            @error('access_token') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="test_event_code">Test Event Code (Optional)</label>
                @if ($hasTestCode)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.tiktok.revoke'),
                        'fields' => ['test_event_code'],
                        'label' => 'Clear code',
                        'confirm' => 'Clear TikTok Test Event Code?',
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
            <p class="mt-1 text-xs text-muted">Found in TikTok Events Manager &rarr; Test Events tab.</p>
            @error('test_event_code') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
            Enable TikTok Pixel &amp; Events API
        </label>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <button type="submit" class="btn-primary">Save TikTok Settings</button>
            @if ($hasAny)
                @include('admin.tracking._revoke', [
                    'action' => route('admin.tracking.tiktok.revoke'),
                    'scope' => 'all',
                    'label' => 'Revoke all & disable',
                    'confirm' => 'Clear all TikTok codes/token and disable tracking?',
                    'buttonClass' => 'admin-btn-danger',
                ])
            @endif
        </div>
    </form>
@endsection
