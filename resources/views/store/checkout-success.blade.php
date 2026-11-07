@extends('layouts.store')

@php
    use App\Support\Money;
@endphp

@section('title', 'Order Confirmed — '.($store['store_name'] ?? config('app.name', 'Store')))

@section('content')
    <section class="store-container py-12 sm:py-16">
        <div class="mx-auto max-w-2xl rounded-2xl border border-ink/8 bg-white p-6 sm:p-10 shadow-sm">
            {{-- Success Icon & Header --}}
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shadow-xs">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="mt-4 inline-block text-xs font-bold uppercase tracking-widest text-emerald-700">Order Placed Successfully</span>
                <h1 class="mt-1 font-display text-3xl sm:text-4xl font-extrabold text-ink">Thank You for Your Order!</h1>
                <p class="mt-2 text-xs sm:text-sm text-muted">
                    Your Cash on Delivery order <span class="font-bold text-ink">#{{ $order->invoice_no }}</span> has been received. Our team will contact you shortly to confirm delivery.
                </p>
            </div>

            {{-- Summary Details Grid --}}
            <div class="mt-8 rounded-xl bg-canvas p-4 sm:p-5 text-xs">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-muted font-medium">Invoice Number</dt>
                        <dd class="mt-0.5 text-sm font-bold text-ink">{{ $order->invoice_no }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted font-medium">Payment Method</dt>
                        <dd class="mt-0.5 text-sm font-bold text-ink uppercase">{{ $order->payment_method === 'cod' ? 'Cash On Delivery' : $order->payment_method }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted font-medium">Total Payable Amount</dt>
                        <dd class="mt-0.5 text-base font-extrabold text-accent">{{ Money::format($order->total) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted font-medium">Delivery Status</dt>
                        <dd class="mt-0.5 text-sm font-bold text-emerald-600">Processing / Preparing</dd>
                    </div>
                    <div class="sm:col-span-2 border-t border-ink/8 pt-3">
                        <dt class="text-muted font-medium">Deliver To</dt>
                        <dd class="mt-1 text-ink leading-relaxed">
                            <strong class="font-bold">{{ $order->customer_name }}</strong> · {{ $order->customer_phone }}<br>
                            {{ $order->customer_address }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Order Items List --}}
            <div class="mt-8 border-t border-ink/8 pt-6">
                <h3 class="font-display text-sm font-bold uppercase tracking-wider text-muted">Ordered Items</h3>
                <ul class="mt-4 divide-y divide-ink/5 text-xs">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <div>
                                <span class="font-bold text-ink">{{ $item->product_name }}</span>
                                <span class="text-muted"> &times; {{ $item->qty }}</span>
                                @if (! empty($item->variation_name))
                                    <p class="text-[11px] text-muted">{{ $item->variation_name }}</p>
                                @elseif (! empty($item->color) || ! empty($item->size))
                                    <p class="text-[11px] text-muted">{{ implode(' / ', array_filter([$item->color, $item->size])) }}</p>
                                @endif
                            </div>
                            <span class="font-bold text-ink shrink-0">{{ Money::format(bcmul((string) $item->price, (string) $item->qty, 2)) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Actions --}}
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 border-t border-ink/8 pt-6">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent px-6 py-3 text-xs sm:text-sm font-bold text-white shadow-md hover:bg-accent-deep transition">
                    <span>Continue Shopping</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                @auth
                    <a href="{{ route('account.orders.show', $order) }}" class="inline-flex items-center gap-2 rounded-xl border border-ink/15 bg-white px-5 py-3 text-xs sm:text-sm font-semibold text-ink hover:bg-mist transition">
                        View Order in Account
                    </a>
                @endauth
            </div>
        </div>
    </section>

    @php
        $orderProductIds = $order->items->pluck('product_id')->map(fn($id) => (string) $id)->unique()->values()->all();
        $orderEcomItems = $order->items->map(fn($item) => [
            'item_id' => (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->price,
            'quantity' => (int) $item->qty,
        ])->values()->all();
        $landingPagePixelId = $order->landingPage?->fb_pixel_id ? trim($order->landingPage->fb_pixel_id) : null;
    @endphp

    @push('scripts')
        <script>
            (() => {
                const totalValue = {{ (float) $order->total }};
                const shippingValue = {{ (float) $order->shipping_charge }};
                const invoiceNo = @json($order->invoice_no);
                const eventId = @json('order_'.$order->id);
                const contentIds = @json($orderProductIds);
                const items = @json($orderEcomItems);
                const numItems = {{ $order->items->sum('qty') }};

                // Prevent duplicate purchase tracking across page reloads/refreshes
                const storageKey = 'tracked_purchase_' + eventId;
                try {
                    if (sessionStorage.getItem(storageKey) || localStorage.getItem(storageKey)) {
                        return;
                    }
                    sessionStorage.setItem(storageKey, '1');
                    localStorage.setItem(storageKey, '1');
                } catch (e) {}

                // Prevent duplicate purchase tracking on client
                window.__tracked_events = window.__tracked_events || {};
                if (window.__tracked_events[eventId]) {
                    return;
                }
                window.__tracked_events[eventId] = true;

                // Initialize custom landing page pixel if set and distinct from global pixel
                @if (filled($landingPagePixelId) && $landingPagePixelId !== ($tracking['facebook']['pixel_id'] ?? null))
                    if (typeof fbq === 'function') {
                        fbq('init', @js($landingPagePixelId));
                    }
                @endif

                // Meta Pixel Purchase with eventID for CAPI deduplication
                if (typeof fbq === 'function') {
                    fbq('track', 'Purchase', {
                        content_ids: contentIds,
                        content_type: 'product',
                        value: totalValue,
                        currency: 'BDT',
                        num_items: numItems
                    }, {
                        eventID: eventId
                    });
                }

                // TikTok Pixel CompletePayment with event_id for Events API deduplication
                if (typeof ttq === 'object' && typeof ttq.track === 'function') {
                    ttq.track('CompletePayment', {
                        contents: items,
                        value: totalValue,
                        currency: 'BDT'
                    }, {
                        event_id: eventId
                    });
                }

                // Google dataLayer purchase event
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: 'purchase',
                    event_id: eventId,
                    ecommerce: {
                        transaction_id: invoiceNo,
                        value: totalValue,
                        tax: 0,
                        shipping: shippingValue,
                        currency: 'BDT',
                        items: items
                    }
                });
            })();
        </script>
    @endpush
@endsection
