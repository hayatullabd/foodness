@extends('layouts.admin')

@section('title', 'Add Role')
@section('heading', 'Create Role')
@section('subheading', 'Define a new administrative role and grant fine-grained capability keys')

@section('actions')
    <a href="{{ route('admin.roles.index') }}" class="admin-btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Back to Roles
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-4">
        @csrf
        @include('admin.roles._form')
        <div class="sticky bottom-4 z-10 flex items-center justify-between rounded-lg border border-ink/10 bg-white/95 p-3 shadow-lg backdrop-blur-sm">
            <a href="{{ route('admin.roles.index') }}" class="admin-btn-secondary">Cancel</a>
            <button type="submit" class="admin-btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Role
            </button>
        </div>
    </form>
@endsection
