@extends('layouts.admin')

@section('title', 'New status')
@section('heading', 'New status')

@section('content')
    <form method="POST" action="{{ route('admin.order-statuses.store') }}" class="max-w-lg space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.order-statuses._form')
        <button type="submit" class="btn-primary">Save</button>
    </form>
@endsection
