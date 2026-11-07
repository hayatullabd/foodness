@extends('layouts.admin')

@section('title', 'Edit Role')
@section('heading', 'Edit Role: ' . $role->name)
@section('subheading', 'Update role name and capability permissions')

@section('actions')
    <a href="{{ route('admin.roles.index') }}" class="admin-btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Back to Roles
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-4">
        @csrf
        @method('PUT')
        @include('admin.roles._form', ['role' => $role])
        <div class="sticky bottom-4 z-10 flex items-center justify-between rounded-lg border border-ink/10 bg-white/95 p-3 shadow-lg backdrop-blur-sm">
            <a href="{{ route('admin.roles.index') }}" class="admin-btn-secondary">Cancel</a>
            <button type="submit" class="admin-btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Save Changes
            </button>
        </div>
    </form>
@endsection
