@extends('layouts.admin')

@section('title', 'Edit Subcategory')
@section('heading', 'Edit Subcategory')

@section('content')
    <form method="POST" action="{{ route('admin.subcategories.update', $subcategory) }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.subcategories._form', ['subcategory' => $subcategory])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
