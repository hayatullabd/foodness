@extends('layouts.admin')

@section('title', 'Edit Color')
@section('heading', 'Edit Color')

@section('content')
    <form method="POST" action="{{ route('admin.colors.update', $color) }}" class="max-w-2xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.colors._form', ['color' => $color])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
