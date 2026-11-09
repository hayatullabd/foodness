@extends('layouts.admin')

@section('title', 'Add Color')
@section('heading', 'Add Color')

@section('content')
    <form method="POST" action="{{ route('admin.colors.store') }}" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.colors._form')
        <button type="submit" class="btn-primary">Create Color</button>
    </form>
@endsection
