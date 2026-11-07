@extends('layouts.admin')

@section('title', 'Product Edit')
@section('heading', 'Product Edit')
@section('subheading', $product->name)

@section('actions')
    @if ($product->slug)
        <a href="{{ route('product.show', $product->slug) }}" target="_blank" rel="noopener noreferrer" class="admin-btn-secondary inline-flex items-center gap-1.5" title="View product in storefront">
            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span>View Product</span>
        </a>
    @endif
    <a href="{{ route('admin.products.index') }}" class="admin-btn-secondary">Cancel</a>
    <button type="submit" form="duplicate-product-form" class="admin-btn-secondary inline-flex items-center gap-1.5">
        <svg class="h-4 w-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <span>Duplicate</span>
    </button>
    <button type="submit" form="product-edit-form" class="admin-btn-primary">Update Product</button>
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


    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-4" id="product-edit-form">
        @csrf
        @method('PUT')

        @include('admin.products._form', ['product' => $product])

        {{-- Mobile Sticky Action Bar --}}
        <div class="sticky bottom-14 z-30 -mx-3 -mb-4 mt-6 border-t border-line/80 bg-white/95 px-4 py-2.5 backdrop-blur shadow-lg lg:hidden flex items-center justify-between gap-2">
            @if ($product->slug)
                <a href="{{ route('product.show', $product->slug) }}" target="_blank" rel="noopener noreferrer" class="admin-btn-secondary text-xs px-2.5 py-2 inline-flex items-center gap-1" title="View product in storefront">
                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>View</span>
                </a>
            @endif
            <a href="{{ route('admin.products.index') }}" class="admin-btn-secondary text-xs px-3 text-center py-2">Cancel</a>
            <button type="submit" form="duplicate-product-form" class="admin-btn-secondary text-xs px-3 py-2 flex items-center gap-1" title="Duplicate product">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Duplicate</span>
            </button>
            <button type="submit" class="admin-btn-primary text-xs flex-1 py-2 font-semibold">Update Product</button>
        </div>
    </form>

    <form
        id="duplicate-product-form"
        method="POST"
        action="{{ route('admin.products.duplicate', $product) }}"
        class="hidden"
        onsubmit="return confirm('Duplicate this product as a draft?')"
    >
        @csrf
    </form>

    @if ($product->meta_image)
        <form
            id="delete-product-meta-image"
            method="POST"
            action="{{ route('admin.products.meta-image.destroy', $product) }}"
            class="hidden"
        >
            @csrf
            @method('DELETE')
        </form>
    @endif

    @foreach ($product->images as $image)
        <form
            id="delete-product-image-{{ $image->id }}"
            method="POST"
            action="{{ route('admin.products.images.destroy', [$product, $image]) }}"
            class="hidden"
        >
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    @foreach ($product->videos as $video)
        <form
            id="delete-product-video-{{ $video->id }}"
            method="POST"
            action="{{ route('admin.products.videos.destroy', [$product, $video]) }}"
            class="hidden"
        >
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
