@extends('layouts.admin')

@section('title', 'Edit User')
@section('heading', 'Edit User')
@section('subheading', 'Update account details and roles')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary">Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-card max-w-2xl">
        @csrf
        @method('PUT')
        <div class="admin-card-body space-y-4">
            @include('admin.users._form', ['user' => $user])
            <div class="flex justify-end border-t border-ink/10 pt-3">
                <button type="submit" class="admin-btn-primary">Save changes</button>
            </div>
        </div>
    </form>
@endsection
