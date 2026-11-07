@extends('layouts.admin')

@section('title', 'Add Product')
@section('heading', 'Add Product')
@section('subheading', 'Create a new in-house product')

@section('actions')
    <a href="{{ route('admin.products.index') }}" class="admin-btn-secondary">Cancel</a>
    <button type="submit" form="product-create-form" class="admin-btn-primary">Create Product</button>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-3 rounded-md border border-accent/30 bg-accent/5 px-3 py-2 text-sm text-accent">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-4" id="product-create-form">
        @csrf

        @include('admin.products._form')

        {{-- Mobile Sticky Action Bar --}}
        <div class="sticky bottom-14 z-30 -mx-3 -mb-4 mt-6 border-t border-line/80 bg-white/95 px-4 py-2.5 backdrop-blur shadow-lg lg:hidden flex items-center justify-between gap-3">
            <a href="{{ route('admin.products.index') }}" class="admin-btn-secondary text-xs flex-1 text-center py-2">Cancel</a>
            <button type="submit" class="admin-btn-primary text-xs flex-1 py-2 font-semibold">Create Product</button>
        </div>
    </form>
@endsection
