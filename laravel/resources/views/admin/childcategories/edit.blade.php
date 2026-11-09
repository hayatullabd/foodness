@extends('layouts.admin')

@section('title', 'Edit Childcategory')
@section('heading', 'Edit Childcategory')

@section('content')
    <form method="POST" action="{{ route('admin.childcategories.update', $childcategory) }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.childcategories._form', ['childcategory' => $childcategory])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
