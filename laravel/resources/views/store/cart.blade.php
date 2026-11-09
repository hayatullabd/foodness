@extends('layouts.store')

@php
    use App\Support\Money;
@endphp

@section('title', 'Shopping Bag — '.($store['store_name'] ?? config('app.name', 'Store')))

@section('content')
    <section class="store-container py-8 sm:py-12">
        <div class="flex items-center justify-between border-b border-ink/8 pb-4">
            <div>
                <h1 class="font-display text-2xl sm:text-4xl font-extrabold text-ink">Your Shopping Bag</h1>
                <p class="mt-1 text-xs sm:text-sm text-muted">Review items before proceeding to cash on delivery checkout.</p>
            </div>
            <a href="{{ route('shop.index') }}" class="hidden sm:inline-flex text-xs sm:text-sm font-semibold text-accent hover:underline">
                &larr; Continue Shopping
            </a>
        </div>

        @if ($lines->isEmpty())
            <div class="mt-10 rounded-2xl border border-dashed border-ink/20 bg-white p-12 text-center shadow-xs">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mist text-muted">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <h2 class="mt-4 font-display text-xl font-bold text-ink">Your bag is currently empty</h2>
                <p class="mt-1 text-xs sm:text-sm text-muted">Explore our latest arrivals and trendy collection.</p>
                <div class="mt-6">
                    <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent px-6 py-3.5 text-xs sm:text-sm font-bold text-white shadow-md hover:bg-accent-deep transition">
                        <span>Start Shopping</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_380px] lg:items-start">
                {{-- Cart Items List --}}
                <div class="space-y-4">
                    @foreach ($lines as $line)
                        @php
                            $product = $line['product'];
                            $variant = $line['variant'] ?? null;
                            $imagePath = $variant?->primaryImagePath() ?: $product->primaryImagePath();
                            $imageUrl = $imagePath && file_exists(public_path('storage/'.$imagePath))
                                ? asset('storage/'.$imagePath)
                                : null;
                            $maxStock = $variant ? $variant->stock : $product->stock;
                        @endphp

                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-ink/8 bg-white p-4 shadow-xs transition hover:shadow-sm">
                            <div class="flex items-center gap-4">
                                <a href="{{ route('product.show', $product) }}" class="block h-20 w-16 shrink-0 overflow-hidden rounded-xl bg-mist">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center font-display text-xl font-bold text-ink/20">
                                            {{ strtoupper(substr($product->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>

                                <div>
                                    <a href="{{ route('product.show', $product) }}" class="font-display text-sm sm:text-base font-bold text-ink hover:text-accent transition">
                                        {{ $product->name }}
                                    </a>

                                    @if (! empty($line['variation_name']))
                                        <p class="mt-0.5 text-xs text-muted">
                                            Option: <strong class="font-semibold text-ink">{{ $line['variation_name'] }}</strong>
                                        </p>
                                    @elseif (! empty($line['color']) || ! empty($line['size']))
                                        <p class="mt-0.5 text-xs text-muted">
                                            @if (! empty($line['color']))<span>Color: <strong class="font-semibold text-ink">{{ $line['color'] }}</strong></span>@endif
                                            @if (! empty($line['color']) && ! empty($line['size'])) · @endif
                                            @if (! empty($line['size']))<span>Size: <strong class="font-semibold text-ink">{{ $line['size'] }}</strong></span>@endif
                                        </p>
                                    @endif

                                    <p class="mt-1 text-xs text-muted">
                                        {{ Money::format($line['price']) }} each
                                    </p>
                                </div>
                            </div>

                            {{-- Quantity & Subtotal Controls --}}
                            <div class="flex w-full sm:w-auto items-center justify-between sm:justify-end gap-5 border-t sm:border-t-0 border-ink/5 pt-3 sm:pt-0">
                                <form method="POST" action="{{ route('cart.update', $product) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    @if (! empty($line['variant_id']))
                                        <input type="hidden" name="variant_id" value="{{ $line['variant_id'] }}">
                                    @endif
                                    <div class="flex items-center rounded-lg border border-ink/15 overflow-hidden">
                                        <input
                                            type="number"
                                            name="qty"
                                            value="{{ $line['qty'] }}"
                                            min="0"
                                            max="{{ max(1, $maxStock) }}"
                                            class="w-14 py-1.5 text-center text-xs font-bold text-ink focus:outline-none"
                                        >
                                    </div>
                                    <button type="submit" class="rounded-lg border border-ink/15 px-2.5 py-1.5 text-xs font-semibold text-ink hover:bg-mist transition">
                                        Update
                                    </button>
                                </form>

                                <div class="text-right">
                                    <p class="font-display text-sm sm:text-base font-extrabold text-ink">
                                        {{ Money::format($line['line_total']) }}
                                    </p>
                                    <form method="POST" action="{{ route('cart.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        @if (! empty($line['variant_id']))
                                            <input type="hidden" name="variant_id" value="{{ $line['variant_id'] }}">
                                        @endif
                                        <button type="submit" class="text-xs text-accent hover:underline mt-0.5">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary Card --}}
                <div class="rounded-2xl border border-ink/10 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg font-bold text-ink">Order Summary</h3>

                    <div class="mt-4 space-y-3 border-t border-ink/8 pt-4 text-xs">
                        <div class="flex justify-between text-muted">
                            <span>Subtotal</span>
                            <span class="font-bold text-ink text-sm">{{ Money::format($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-muted">
                            <span>Shipping / Delivery</span>
                            <span class="font-medium text-emerald-600">Calculated at checkout</span>
                        </div>
                        <div class="flex justify-between border-t border-ink/8 pt-3 text-sm font-extrabold text-ink">
                            <span>Estimated Total</span>
                            <span class="text-base text-accent">{{ Money::format($subtotal) }}</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a
                            href="{{ route('checkout.create') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-6 py-3.5 text-sm font-bold text-white shadow-lg transition hover:bg-accent-deep"
                        >
                            <span>Proceed to COD Checkout</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>

                    {{-- Mini Trust Bullets --}}
                    <div class="mt-6 space-y-2 border-t border-ink/8 pt-4 text-[11px] text-muted">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>100% Cash on Delivery across Bangladesh</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Inspect package before paying</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>7-Day Easy Exchange Policy</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
