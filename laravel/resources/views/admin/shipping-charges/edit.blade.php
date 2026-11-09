@extends('layouts.admin')

@section('title', 'Edit shipping charge')
@section('heading', 'Edit shipping charge')

@section('content')
    <form method="POST" action="{{ route('admin.shipping-charges.update', $shippingCharge) }}" class="max-w-xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')
        @include('admin.shipping-charges._form', ['shippingCharge' => $shippingCharge])
        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
