@extends('layouts.admin')

@section('title', $product->name)
@section('heading', $product->name)
@section('subheading', 'Product details')

@section('actions')
    <a href="{{ route('admin.products.edit', $product) }}" class="btn-primary">Edit</a>
@endsection

@section('content')
    <div class="grid gap-3 lg:grid-cols-3">
        <div class="space-y-4 admin-card admin-card-body lg:col-span-2">
            <p class="text-sm text-muted">SKU: {{ $product->sku }}</p>
            @if ($product->short_description)
                <p class="mt-2 text-sm text-ink/80">{{ $product->short_description }}</p>
            @endif
            @if ($product->description)
                <div class="product-description mt-3 text-sm">{!! \App\Support\HtmlSanitizer::clean($product->description) !!}</div>
            @endif
            <p class="text-sm text-muted">Category: {{ $product->category?->name }}</p>
            @if ($product->brand)
                <p class="text-sm text-muted">Brand: {{ $product->brand->name }}</p>
            @endif
        </div>
        <div class="admin-card admin-card-body">
            <p class="text-lg font-semibold">৳{{ number_format($product->price, 2) }}</p>
            <p class="mt-1 text-sm text-muted">Stock: {{ $product->stock }}</p>
            <p class="mt-3 text-sm">Status: {{ $product->is_active ? 'Active' : 'Inactive' }}</p>
            <p class="text-sm">Hot Deal: {{ $product->is_hot ? 'Yes' : 'No' }}</p>
        </div>
    </div>
@endsection
