@extends('layouts.admin')

@section('title', 'Edit status')
@section('heading', 'Edit status')

@section('content')
    <form method="POST" action="{{ route('admin.order-statuses.update', $orderStatus) }}" class="max-w-lg space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.order-statuses._form')
        <button type="submit" class="btn-primary">Update</button>
    </form>
@endsection
