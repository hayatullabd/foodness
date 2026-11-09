@extends('layouts.admin')

@section('title', 'Add Size')
@section('heading', 'Add Size')

@section('content')
    <form method="POST" action="{{ route('admin.sizes.store') }}" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.sizes._form')
        <button type="submit" class="btn-primary">Create Size</button>
    </form>
@endsection
