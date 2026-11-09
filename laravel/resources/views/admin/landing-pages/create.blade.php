@extends('layouts.admin')

@section('title', 'Create New Landing Page — Foodness')
@section('heading', 'Create New Landing Page')
@section('subheading', 'Design a high-converting sales funnel with custom HTML, CSS, JS and 1-click checkout.')

@section('actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.landing-pages.index') }}" class="btn-secondary">
            Cancel
        </a>
        <button type="submit" form="landing-page-settings-form" class="btn-primary !bg-orange-500 hover:!bg-orange-600 !border-orange-500">
            Save Settings
        </button>
    </div>
@endsection

@section('content')
<form id="landing-page-settings-form" action="{{ route('admin.landing-pages.store') }}" method="POST" class="space-y-6">
    @csrf
    @include('admin.landing-pages.form')
</form>
@endsection
