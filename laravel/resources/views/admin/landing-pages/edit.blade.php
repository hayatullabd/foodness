@extends('layouts.admin')

@section('title', 'Edit Landing Page — ' . $landingPage->title)
@section('heading', 'Edit Landing Page')
@section('subheading', 'Modify custom HTML, CSS, JS or target product for this landing page.')

@section('actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('landing.show', $landingPage->slug) }}" target="_blank" class="btn-secondary inline-flex items-center gap-1.5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span>Live Preview</span>
        </a>
        <a href="{{ route('admin.landing-pages.index') }}" class="btn-secondary">
            Cancel
        </a>
        <button type="submit" form="landing-page-settings-form" class="btn-primary !bg-orange-500 hover:!bg-orange-600 !border-orange-500">
            Save Settings
        </button>
    </div>
@endsection

@section('content')
<form id="landing-page-settings-form" action="{{ route('admin.landing-pages.update', $landingPage) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')
    @include('admin.landing-pages.form')
</form>
@endsection
