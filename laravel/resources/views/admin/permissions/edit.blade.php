@extends('layouts.admin')

@section('title', 'Edit Permission')
@section('heading', 'Edit Permission')
@section('subheading', 'Update capability key: ' . $permission->name)

@section('actions')
    <a href="{{ route('admin.permissions.index') }}" class="admin-btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Back to Permissions
    </a>
@endsection

@section('content')
    <div class="max-w-xl">
        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}" class="admin-card">
            @csrf
            @method('PUT')
            <div class="admin-card-body space-y-4">
                @include('admin.permissions._form', ['permission' => $permission])
                <div class="flex items-center justify-end gap-2 border-t border-ink/10 pt-3">
                    <a href="{{ route('admin.permissions.index') }}" class="admin-btn-secondary">Cancel</a>
                    <button type="submit" class="admin-btn-primary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
