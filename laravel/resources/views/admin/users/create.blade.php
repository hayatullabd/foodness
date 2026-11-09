@extends('layouts.admin')

@section('title', 'Add User')
@section('heading', 'Add User')
@section('subheading', 'Create an admin account')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary">Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" class="admin-card max-w-2xl">
        @csrf
        <div class="admin-card-body space-y-4">
            @include('admin.users._form')
            <div class="flex justify-end border-t border-ink/10 pt-3">
                <button type="submit" class="admin-btn-primary">Create User</button>
            </div>
        </div>
    </form>
@endsection
