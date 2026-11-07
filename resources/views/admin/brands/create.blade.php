@extends('layouts.admin')

@section('title', 'Add Brand')
@section('heading', 'Add Brand')

@section('content')
    <form method="POST" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.brands._form')
        <button type="submit" class="btn-primary">Create Brand</button>
    </form>
@endsection
