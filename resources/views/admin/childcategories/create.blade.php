@extends('layouts.admin')

@section('title', 'Add Childcategory')
@section('heading', 'Add Childcategory')

@section('content')
    <form method="POST" action="{{ route('admin.childcategories.store') }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.childcategories._form')
        <button type="submit" class="btn-primary">Create Childcategory</button>
    </form>
@endsection
