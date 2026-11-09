@extends('layouts.admin')

@section('title', 'Edit Brand')
@section('heading', 'Edit Brand')

@section('content')
    <form method="POST" action="{{ route('admin.brands.update', $brand) }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.brands._form', ['brand' => $brand])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
