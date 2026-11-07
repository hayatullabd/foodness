@extends('layouts.admin')

@section('title', 'Add shipping charge')
@section('heading', 'Add shipping charge')

@section('content')
    <form method="POST" action="{{ route('admin.shipping-charges.store') }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @include('admin.shipping-charges._form')
        <button type="submit" class="btn-primary">Create</button>
    </form>
@endsection
