@extends('layouts.store')

@php
    use App\Support\Money;
@endphp

@section('title', 'Order #'.$order->invoice_no.' — '.($store['store_name'] ?? config('app.name', 'Store')))

@section('content')
    <section class="store-container py-8 sm:py-12">
        <div class="mb-4">
            <a href="{{ route('account.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-accent hover:underline">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>আমার অর্ডার তালিকায় ফিরে যান</span>
            </a>
        </div>

        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center border-b border-ink/8 pb-6">
            <div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-ink">Order #{{ $order->invoice_no }}</h1>
                <p class="mt-1 text-xs text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold capitalize text-emerald-700 ring-1 ring-emerald-600/20">
                    {{ $order->status }}
                </span>
                <span class="inline-flex rounded-full bg-mist px-3 py-1 text-xs font-semibold uppercase text-ink">
                    {{ $order->payment_method === 'cod' ? 'Cash On Delivery' : $order->payment_method }}
                </span>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            {{-- Order Items Card --}}
            <div class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm">
                <h2 class="font-display text-base font-bold text-ink">Order Items</h2>
                <ul class="mt-4 divide-y divide-ink/5 text-xs">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <div>
                                <span class="font-bold text-ink text-sm">{{ $item->product_name }}</span>
                                <span class="text-muted font-medium"> &times; {{ $item->qty }}</span>
                                @if (! empty($item->variation_name))
                                    <p class="text-[11px] text-muted">{{ $item->variation_name }}</p>
                                @elseif (! empty($item->color) || ! empty($item->size))
                                    <p class="text-[11px] text-muted">{{ implode(' / ', array_filter([$item->color, $item->size])) }}</p>
                                @endif
                            </div>
                            <span class="font-bold text-ink text-sm">{{ Money::format(bcmul((string) $item->price, (string) $item->qty, 2)) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-6 space-y-2 border-t border-ink/8 pt-4 text-xs">
                    <div class="flex justify-between text-muted"><dt>Subtotal</dt><dd class="font-medium text-ink">{{ Money::format($order->subtotal) }}</dd></div>
                    <div class="flex justify-between text-muted"><dt>Delivery Fee</dt><dd class="font-medium text-ink">{{ Money::format($order->shipping_charge) }}</dd></div>
                    @if ((float) $order->discount > 0)
                        <div class="flex justify-between text-emerald-600"><dt>Discount</dt><dd>-{{ Money::format($order->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-ink/8 pt-3 text-sm font-extrabold text-ink"><dt>Total Amount</dt><dd class="text-base text-accent">{{ Money::format($order->total) }}</dd></div>
                </dl>
            </div>

            {{-- Shipping & Delivery Details Card --}}
            <div class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm">
                <h2 class="font-display text-base font-bold text-ink">Delivery Information</h2>
                <div class="mt-4 space-y-3 text-xs">
                    <div>
                        <span class="text-muted">Customer Name:</span>
                        <p class="font-bold text-ink">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <span class="text-muted">Phone Number:</span>
                        <p class="font-bold text-ink">{{ $order->customer_phone }}</p>
                    </div>
                    <div>
                        <span class="text-muted">Delivery Address:</span>
                        <p class="font-medium text-ink leading-relaxed">{{ $order->customer_address }}</p>
                    </div>
                    @if ($order->notes)
                        <div class="border-t border-ink/8 pt-3">
                            <span class="text-muted">Order Notes:</span>
                            <p class="font-medium text-ink italic">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 rounded-xl bg-mist/60 p-4 text-[11px] text-muted space-y-1">
                    <p class="font-bold text-ink">Courier Delivery</p>
                    <p>Orders are dispatched within 24 hours. You will receive an SMS when the courier is on the way.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
