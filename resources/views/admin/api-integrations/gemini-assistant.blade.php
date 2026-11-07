@extends('layouts.admin')

@section('title', 'Gemini Assistant')
@section('heading', 'Gemini Assistant')
@section('subheading', 'Assistant API key and system prompt')

@section('content')
    <form method="POST" action="{{ route('admin.api-integrations.gemini-assistant.update') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <div>
            <label class="admin-label" for="api_key">API key</label>
            <input
                id="api_key"
                name="api_key"
                type="password"
                value=""
                class="field"
                autocomplete="off"
                placeholder="{{ $maskedApiKey ? 'Leave blank to keep current key' : 'Paste Gemini API key' }}"
            >
            @if ($maskedApiKey)
                <p class="mt-1 text-xs text-muted">Current key: <span class="font-mono tracking-wide">{{ $maskedApiKey }}</span></p>
            @endif
            @error('api_key') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-label" for="system_prompt">System prompt</label>
            <textarea id="system_prompt" name="system_prompt" rows="6" class="field" placeholder="You are a helpful store assistant...">{{ old('system_prompt', $integration->credential('system_prompt')) }}</textarea>
            @error('system_prompt') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $integration->is_active))>
            Enable Gemini Assistant
        </label>

        <button type="submit" class="btn-primary">Save Gemini Assistant settings</button>
    </form>
@endsection
