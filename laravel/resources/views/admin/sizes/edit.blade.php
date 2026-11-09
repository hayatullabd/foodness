@extends('layouts.admin')

@section('title', 'Edit Size')
@section('heading', 'Edit Size')

@section('content')
    <form method="POST" action="{{ route('admin.sizes.update', $size) }}" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.sizes._form', ['size' => $size])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
