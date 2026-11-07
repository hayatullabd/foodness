@extends('layouts.admin')

@section('title', 'Create New Landing Page — Foodness')
@section('heading', 'Create New Landing Page')
@section('subheading', 'Design a high-converting sales funnel with custom HTML, CSS, JS and 1-click checkout.')

@section('actions')
    <a href="{{ route('admin.landing-pages.index') }}" class="btn-secondary">
        Back to List
    </a>
@endsection

@section('content')
<form action="{{ route('admin.landing-pages.store') }}" method="POST" class="space-y-6">
    @csrf
    @include('admin.landing-pages.form')

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <a href="{{ route('admin.landing-pages.index') }}" class="btn-secondary">
            Cancel
        </a>
        <button type="submit" class="btn-primary">
            Save & Publish Landing Page
        </button>
    </div>
</form>
@endsection
