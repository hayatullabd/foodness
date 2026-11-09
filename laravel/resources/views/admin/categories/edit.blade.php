@extends('layouts.admin')

@section('title', 'Edit category')
@section('heading', 'Edit category')

@section('content')
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.categories._form', ['category' => $category])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
