@extends('layouts.admin')

@section('title', 'Facebook CAPI')
@section('heading', 'Facebook CAPI')
@section('subheading', 'Conversions API pixel and access token')

@section('content')
    <form method="POST" action="{{ route('admin.api-integrations.facebook-capi.update') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <div>
            <label class="admin-label" for="pixel_id">Pixel ID</label>
            <input id="pixel_id" name="pixel_id" type="text" value="{{ old('pixel_id', $integration->credential('pixel_id')) }}" class="field" autocomplete="off">
            @error('pixel_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label" for="access_token">Access token</label>
            <input
                id="access_token"
                name="access_token"
                type="password"
                value=""
                class="field"
                autocomplete="off"
                placeholder="{{ $maskedAccessToken ? 'Leave blank to keep current token' : 'Paste access token' }}"
            >
            @if ($maskedAccessToken)
                <p class="mt-1 text-xs text-muted">Current token: <span class="font-mono tracking-wide">{{ $maskedAccessToken }}</span></p>
            @endif
            @error('access_token') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label" for="test_event_code">Test event code</label>
            <input id="test_event_code" name="test_event_code" type="text" value="{{ old('test_event_code', $integration->credential('test_event_code')) }}" class="field" autocomplete="off">
            @error('test_event_code') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
            Enable Facebook CAPI
        </label>

        <button type="submit" class="btn-primary">Save Facebook CAPI settings</button>
    </form>
@endsection
