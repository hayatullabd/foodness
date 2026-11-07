@extends('layouts.admin')

@section('title', 'Facebook Pixel')
@section('heading', 'Tracking & Analytics')
@section('subheading', 'Manage your storefront pixel, GTM, CAPI and event tracking')

@section('content')
    @include('admin.tracking._tabs')

    @php
        $hasPixel = filled($integration->credential('pixel_id'));
    @endphp

    <form method="POST" action="{{ route('admin.tracking.facebook.update') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <p class="text-xs text-muted">
            This is the browser Facebook Pixel ID (tracks PageView, ViewContent, AddToCart, Purchase). For server-side tracking, configure
            <a href="{{ route('admin.tracking.facebook-capi.edit') }}" class="font-medium text-slate-800 underline">Facebook CAPI</a>.
        </p>

        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label class="admin-label !mb-0" for="pixel_id">Pixel ID</label>
                @if ($hasPixel)
                    @include('admin.tracking._revoke', [
                        'action' => route('admin.tracking.facebook.revoke'),
                        'fields' => ['pixel_id'],
                        'deactivate' => true,
                        'label' => 'Revoke Pixel',
                        'confirm' => 'Revoke Facebook Pixel ID and disable Pixel tracking?',
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
            @error('pixel_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
            Enable Facebook Pixel
        </label>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <button type="submit" class="btn-primary">Save Facebook Pixel</button>
            @if ($hasPixel || $integration->is_active)
                @include('admin.tracking._revoke', [
                    'action' => route('admin.tracking.facebook.revoke'),
                    'scope' => 'all',
                    'label' => 'Revoke all & disable',
                    'confirm' => 'Clear Facebook Pixel code and disable it completely?',
                    'buttonClass' => 'admin-btn-danger',
                ])
            @endif
        </div>
    </form>
@endsection
