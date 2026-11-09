@php
    use App\Support\Money;

    $imagePath = $product->primaryImagePath();
    $imageUrl = null;
    if ($imagePath && file_exists(public_path('storage/'.$imagePath))) {
        $imageUrl = asset('storage/'.$imagePath);
    } elseif ($product->images->first()?->image_path && file_exists(public_path('storage/'.$product->images->first()->image_path))) {
        $imageUrl = asset('storage/'.$product->images->first()->image_path);
    } elseif ($imagePath) {
        $imageUrl = asset('storage/'.$imagePath);
    } elseif ($product->images->first()?->image_path) {
        $imageUrl = asset('storage/'.$product->images->first()->image_path);
    } else {
        $imageUrl = \App\Support\PlaceholderImage::url();
    }

    $hasDiscount = $product->compare_at_price && (float) $product->compare_at_price > (float) $product->price;
    $inStock = $product->totalStock() > 0;
@endphp

<article class="product-card">
    <a class="product-card__image" href="{{ route('product.show', $product) }}">
        @include('store.partials.product-badges', ['product' => $product])
        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy">
    </a>
    <div class="product-card__body">
        <span class="product-category">{{ $product->category->name ?? '' }}</span>
        <h3><a href="{{ route('product.show', $product) }}" title="{{ $product->name }}">{{ $product->name }}</a></h3>
        <div class="card-price">
            <strong>{{ Money::formatBn($product->price) }}</strong>
            @if ($hasDiscount)
                <del>{{ Money::formatBn($product->compare_at_price) }}</del>
            @endif
        </div>
        @if ($inStock)
            <button
                type="button"
                class="card-buy-now"
                data-open-buy-modal
                data-product-id="{{ $product->id }}"
                data-has-variants="{{ $product->hasVariants() ? 'true' : 'false' }}"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.5h11l1 12h-13l1-12ZM9 9V6a3 3 0 0 1 6 0v3"/></svg>
                <span>Buy Now</span>
            </button>
        @else
            <button type="button" class="card-buy-now" disabled>স্টক আউট</button>
        @endif
    </div>
</article>
