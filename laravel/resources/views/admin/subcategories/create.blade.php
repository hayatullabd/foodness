@extends('layouts.admin')

@section('title', 'Add Subcategory')
@section('heading', 'Add Subcategory')

@section('content')
    <form method="POST" action="{{ route('admin.subcategories.store') }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.subcategories._form')
        <button type="submit" class="btn-primary">Create Subcategory</button>
    </form>
@endsection
