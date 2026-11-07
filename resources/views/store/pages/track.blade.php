@extends('layouts.store')

@php
    use App\Support\Money;
@endphp

@section('title', ($page->meta_title ?? 'অর্ডার ট্র্যাক').' — '.($store['store_name'] ?? 'Foodness'))
@section('meta_description', $page->meta_description ?? 'ইনভয়েস বা মোবাইল নম্বর দিয়ে আপনার অর্ডারের বর্তমান অবস্থা জানুন।')

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-muted mb-4">
            <a href="{{ route('home') }}" class="hover:text-accent transition">হোম</a>
            <span>/</span>
            <span class="text-ink font-semibold">অর্ডার ট্র্যাকিং</span>
        </nav>

        {{-- Page Header Banner --}}
        <div class="rounded-2xl border border-ink/8 bg-gradient-to-r from-canvas via-white to-canvas p-6 sm:p-8 shadow-xs text-center max-w-3xl mx-auto mb-8">
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-accent mb-1.5">রিয়েল-টাইম ট্র্যাকিং</span>
            <h1 class="font-display text-2xl sm:text-4xl font-extrabold text-ink tracking-tight">{{ $page->title ?? 'লাইভ অর্ডার ট্র্যাকিং' }}</h1>
            <p class="mt-2 text-xs sm:text-sm text-muted max-w-xl mx-auto leading-relaxed">
                {{ $page->subtitle ?? 'আপনার ইনভয়েস নম্বর (যেমন: #INV-10025) অথবা অর্ডারে ব্যবহৃত মোবাইল নম্বর দিয়ে সার্চ করুন।' }}
            </p>

            {{-- Search Input Form --}}
            <form method="GET" action="{{ route('order-track.index') }}" class="mt-6 max-w-lg mx-auto flex items-center gap-2">
                <div class="relative flex-1">
                    <input
                        type="text"
                        name="query"
                        value="{{ $query }}"
                        required
                        placeholder="ইনভয়েস নম্বর বা মোবাইল নম্বর..."
                        class="w-full rounded-xl border border-ink/15 bg-white px-4 py-3 text-sm text-ink placeholder:text-muted/60 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent shadow-xs"
                    >
                </div>
                <button
                    type="submit"
                    class="rounded-xl bg-accent px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-accent-deep flex items-center gap-1.5 shrink-0 cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>ট্র্যাক করুন</span>
                </button>
            </form>
        </div>

        {{-- Search Results --}}
        @if ($query !== '')
            <div class="max-w-4xl mx-auto space-y-6">
                @forelse ($orders as $order)
                    @php
                        $statusName = strtolower($order->status);
                        $step = match($statusName) {
                            'pending' => 1,
                            'confirmed', 'courier_pending' => 2,
                            'processing' => 3,
                            'in_transit', 'shipped', 'out_for_delivery' => 4,
                            'delivered', 'completed' => 5,
                            'cancelled', 'returned' => -1,
                            default => 2,
                        };
                    @endphp

                    <div class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm space-y-6">
                        {{-- Order Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-ink/8 pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-extrabold text-lg text-ink">#{{ $order->invoice_no }}</span>
                                    <span @class([
                                        'rounded-full px-2.5 py-0.5 text-xs font-bold capitalize',
                                        'bg-amber-50 text-amber-800 ring-1 ring-amber-600/20' => in_array($statusName, ['pending', 'processing']),
                                        'bg-blue-50 text-blue-800 ring-1 ring-blue-600/20' => in_array($statusName, ['confirmed', 'courier_pending']),
                                        'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-600/20' => in_array($statusName, ['in_transit', 'shipped', 'out_for_delivery']),
                                        'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20' => in_array($statusName, ['delivered', 'completed']),
                                        'bg-rose-50 text-rose-800 ring-1 ring-rose-600/20' => in_array($statusName, ['cancelled', 'returned']),
                                    ])>
                                        {{ $order->statusLabel() }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted mt-1">অর্ডার তৈরির তারিখ: {{ $order->created_at->format('d M, Y — h:i A') }}</p>
                            </div>

                            <div class="text-left sm:text-right">
                                <span class="text-xs text-muted block">মোট প্রদেয় মূল্য:</span>
                                <span class="font-display font-extrabold text-lg text-accent">{{ Money::format($order->total) }}</span>
                                <span class="text-[11px] text-muted block">({{ $order->payment_method === 'cod' ? 'ক্যাশ অন ডেলিভারি' : strtoupper($order->payment_method) }})</span>
                            </div>
                        </div>

                        {{-- Visual Timeline (unless cancelled) --}}
                        @if ($step > 0)
                            <div class="py-2">
                                <div class="grid grid-cols-5 text-center text-xs gap-1 relative">
                                    {{-- Line behind steps --}}
                                    <div class="absolute top-4 left-6 right-6 h-0.5 bg-ink/10 -z-0"></div>
                                    <div class="absolute top-4 left-6 h-0.5 bg-emerald-500 -z-0 transition-all duration-500" style="width: {{ min(100, max(0, ($step - 1) * 25)) }}%;"></div>

                                    {{-- Step 1 --}}
                                    <div class="flex flex-col items-center z-10">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 1 ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-600' }}">1</div>
                                        <span class="mt-2 text-[11px] font-bold text-ink">অর্ডার গৃহীত</span>
                                    </div>

                                    {{-- Step 2 --}}
                                    <div class="flex flex-col items-center z-10">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 2 ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-600' }}">2</div>
                                        <span class="mt-2 text-[11px] font-bold text-ink">কনফার্মড</span>
                                    </div>

                                    {{-- Step 3 --}}
                                    <div class="flex flex-col items-center z-10">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 3 ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-600' }}">3</div>
                                        <span class="mt-2 text-[11px] font-bold text-ink">প্যাকিং</span>
                                    </div>

                                    {{-- Step 4 --}}
                                    <div class="flex flex-col items-center z-10">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 4 ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-600' }}">4</div>
                                        <span class="mt-2 text-[11px] font-bold text-ink">অন দ্য ওয়ে</span>
                                    </div>

                                    {{-- Step 5 --}}
                                    <div class="flex flex-col items-center z-10">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 5 ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-600' }}">5</div>
                                        <span class="mt-2 text-[11px] font-bold text-ink">ডেলিভার্ড</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
                                এই অর্ডারটি বাতিল বা ফেরত সম্পন্ন হয়েছে। বিস্তারিত জানতে কাস্টমার সাপোর্টে যোগাযোগ করুন।
                            </div>
                        @endif

                        {{-- Courier Tracking & Recipient Info --}}
                        <div class="grid gap-4 sm:grid-cols-2 rounded-xl bg-mist/30 p-4 text-xs">
                            <div>
                                <span class="font-bold uppercase tracking-wider text-muted block mb-1">গ্রহীতা:</span>
                                <p class="font-bold text-ink text-sm">{{ $order->customer_name }}</p>
                                <p class="text-muted mt-0.5">{{ \Illuminate\Support\Str::mask((string) $order->customer_phone, '*', 2, -3) }}</p>
                            </div>
                            <div class="sm:text-right">
                                <span class="font-bold uppercase tracking-wider text-muted block mb-1">কুরিয়ার ইনফরমেশন:</span>
                                @if (!empty($order->tracking_number))
                                    <p class="font-mono font-bold text-ink text-sm">ট্র্যাকিং কোড: {{ $order->tracking_number }}</p>
                                    @if ($order->tracking_url)
                                        <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition">
                                            <span>কুরিয়ার লাইভ ট্র্যাকিং</span> &rarr;
                                        </a>
                                    @endif
                                @else
                                    <p class="text-muted">প্যাকেজিং সম্পন্ন হলে ট্র্যাকিং কোড এসএমএসে পাঠানো হবে।</p>
                                @endif
                            </div>
                        </div>

                        {{-- Ordered Items Summary --}}
                        <div>
                            <span class="font-bold uppercase tracking-wider text-muted text-xs block mb-2">অর্ডারকৃত পণ্যসমূহ:</span>
                            <ul class="divide-y divide-ink/5 text-xs">
                                @foreach ($order->items as $item)
                                    <li class="py-2.5 flex items-center justify-between">
                                        <div>
                                            <span class="font-bold text-ink text-sm">{{ $item->product_name }}</span>
                                            <span class="text-muted font-medium"> &times; {{ $item->qty }}</span>
                                            @if ($item->variation_name)
                                                <p class="text-[11px] text-muted">{{ $item->variation_name }}</p>
                                            @endif
                                        </div>
                                        <span class="font-bold text-ink text-sm">{{ Money::format(bcmul((string) $item->price, (string) $item->qty, 2)) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-ink/20 bg-white p-12 text-center text-muted">
                        <svg class="h-10 w-10 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-bold text-ink text-base">কোনো অর্ডার খুঁজে পাওয়া যায়নি</p>
                        <p class="text-xs text-muted mt-1">প্রদত্ত ইনভয়েস নম্বর বা মোবাইল নম্বরটি সঠিক কিনা যাচাই করে পুনরায় চেষ্টা করুন।</p>
                    </div>
                @endforelse
            </div>
        @endif
    </section>
@endsection
