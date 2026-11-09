@extends('layouts.admin')

@section('title', 'Add category')
@section('heading', 'Add category')

@section('content')
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.categories._form')
        <button type="submit" class="btn-primary">Create category</button>
    </form>
@endsection
