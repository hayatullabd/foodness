@extends('layouts.store')

@php
    use App\Support\Money;
@endphp

@section('title', 'মাই অ্যাকাউন্ট — '.($store['store_name'] ?? 'Foodness'))

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Unified Customer Account Navigation --}}
        @include('store.account._nav')

        {{-- Customer Order Statistics Summary --}}
        @if (isset($stats))
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-ink/8 bg-white p-4 shadow-xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-muted">মোট অর্ডার</span>
                    <p class="mt-1 font-display text-xl sm:text-2xl font-extrabold text-ink">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200/60 bg-amber-50/50 p-4 shadow-xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800">পেন্ডিং / প্রসেসিং</span>
                    <p class="mt-1 font-display text-xl sm:text-2xl font-extrabold text-amber-900">{{ $stats['pending'] }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200/60 bg-emerald-50/50 p-4 shadow-xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">সফল ডেলিভারি</span>
                    <p class="mt-1 font-display text-xl sm:text-2xl font-extrabold text-emerald-900">{{ $stats['delivered'] }}</p>
                </div>
                <div class="rounded-2xl border border-ink/8 bg-white p-4 shadow-xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-muted">মোট কেনাকাটা</span>
                    <p class="mt-1 font-display text-xl sm:text-2xl font-extrabold text-accent">{{ Money::format($stats['total_spent'] ?? 0) }}</p>
                </div>
            </div>
        @endif

        {{-- Mobile Cards View (< sm) --}}
        <div class="mt-6 space-y-3 sm:hidden">
            <h2 class="font-display text-base font-bold text-ink">অর্ডার হিস্ট্রি</h2>
            @forelse ($orders as $order)
                <div class="rounded-2xl border border-ink/8 bg-white p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-display font-bold text-ink">#{{ $order->invoice_no }}</span>
                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold capitalize text-emerald-700 ring-1 ring-emerald-600/20">
                            {{ $order->status }}
                        </span>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between text-xs text-muted">
                        <span>{{ $order->created_at->format('d M Y') }} · {{ $order->items_count }} items</span>
                        <span class="font-display font-extrabold text-ink text-sm">{{ Money::format($order->total) }}</span>
                    </div>

                    <div class="mt-3 border-t border-ink/5 pt-2.5 text-right">
                        <a href="{{ route('account.orders.show', $order) }}" class="text-xs font-bold text-accent hover:underline">
                            অর্ডারের বিস্তারিত দেখুন &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-ink/20 bg-white p-8 text-center text-xs text-muted">
                    আপনার কোনো অর্ডার রেকর্ড পাওয়া যায়নি। <a href="{{ route('shop.index') }}" class="font-bold text-accent hover:underline">কেনাকাটা শুরু করুন</a>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table View (>= sm) --}}
        <div class="mt-8 hidden sm:block overflow-hidden rounded-2xl border border-ink/8 bg-white shadow-xs">
            <div class="px-5 py-3.5 border-b border-ink/8 bg-canvas/60 flex items-center justify-between">
                <h2 class="font-display text-sm font-bold text-ink">পূর্বের সকল অর্ডার</h2>
                <span class="text-xs text-muted">সর্বমোট {{ $orders->total() }} টি অর্ডার</span>
            </div>
            <table class="min-w-full text-left text-xs">
                <thead class="border-b border-ink/8 bg-canvas text-muted">
                    <tr>
                        <th class="px-5 py-3.5 font-bold uppercase tracking-wider">ইনভয়েস</th>
                        <th class="px-5 py-3.5 font-bold uppercase tracking-wider">অর্ডারের তারিখ</th>
                        <th class="px-5 py-3.5 font-bold uppercase tracking-wider">পণ্য সংখ্যা</th>
                        <th class="px-5 py-3.5 font-bold uppercase tracking-wider">মোট মূল্য</th>
                        <th class="px-5 py-3.5 font-bold uppercase tracking-wider">স্ট্যাটাস</th>
                        <th class="px-5 py-3.5 text-right font-bold uppercase tracking-wider">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse ($orders as $order)
                        <tr class="transition hover:bg-mist/30">
                            <td class="px-5 py-3.5 font-bold text-ink">#{{ $order->invoice_no }}</td>
                            <td class="px-5 py-3.5 text-muted">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3.5 text-muted">{{ $order->items_count }} টি</td>
                            <td class="px-5 py-3.5 font-extrabold text-ink">{{ Money::format($order->total) }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold capitalize text-emerald-700 ring-1 ring-emerald-600/20">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('account.orders.show', $order) }}" class="font-bold text-accent hover:underline">
                                    বিস্তারিত দেখুন &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-muted">
                                আপনি এখনও কোনো অর্ডার করেননি। <a href="{{ route('shop.index') }}" class="font-bold text-accent hover:underline">প্রোডাক্ট কালেকশন দেখুন</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @endif
    </section>
@endsection
