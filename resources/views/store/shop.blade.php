@extends('layouts.store')

@section('title', ($selectedCategory?->name ?? 'সকল পণ্য').' — '.($store['store_name'] ?? config('app.name', 'Store')))

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Catalog Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between border-b border-[#013F1E]/10 pb-6">
            <div>
                <nav class="flex items-center gap-2 text-xs text-muted mb-2">
                    <a href="{{ route('home') }}" class="hover:text-primary transition font-medium">হোম</a>
                    <span>/</span>
                    <span class="text-ink font-semibold">{{ $selectedCategory?->name ?? 'সব পণ্য' }}</span>
                </nav>
                <h1 class="font-serif text-3xl sm:text-4xl font-extrabold text-[#013F1E] tracking-tight">
                    {{ $selectedCategory?->name ?? 'সকল পণ্য ও কালেকশন' }}
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-muted">
                    মোট <strong class="text-ink font-bold">{{ \App\Support\Money::toBengali($products->total()) }}</strong> টি পণ্য পাওয়া গেছে · সারা দেশে ১০০% ক্যাশ অন ডেলিভারি
                </p>
            </div>

            {{-- Live Search Trigger & Controls --}}
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-80">
                    <button
                        type="button"
                        data-search-open
                        data-search-query="{{ request('q', '') }}"
                        class="w-full flex items-center justify-between rounded-full border border-[#013F1E]/20 bg-white py-2.5 pl-9 pr-3 text-xs text-ink/80 hover:border-[#013F1E] hover:shadow-xs transition text-left cursor-pointer"
                    >
                        <span class="truncate">{{ request('q') ? 'অনুসন্ধান: ' . request('q') : 'পণ্য বা কালেকশন খুঁজুন...' }}</span>
                        <span class="shrink-0 rounded-full bg-[#013F1E]/10 px-2 py-0.5 text-[10px] font-bold text-[#013F1E]">লাইভ সার্চ</span>
                    </button>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#013F1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                @if (request()->filled('q'))
                    <a
                        href="{{ $selectedCategory ? route('shop.category', $selectedCategory) : route('shop.index') }}"
                        class="shrink-0 rounded-full bg-[#A5000B]/10 px-3.5 py-2 text-xs font-bold text-[#A5000B] hover:bg-[#A5000B] hover:text-white transition"
                        title="রিসেট করুন"
                    >
                        রিসেট &times;
                    </a>
                @endif
            </div>
        </div>

        {{-- Filter Category Pills --}}
        <div class="mt-6 flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar">
            <a
                href="{{ route('shop.index') }}"
                class="shrink-0 rounded-full px-4 py-2 text-xs font-bold transition shadow-xs {{ ! $selectedCategory ? 'bg-[#013F1E] text-white shadow-sm' : 'border border-[#E6E1D8] bg-white text-[#2D1B13] hover:bg-[#F2F8F4]' }}"
            >
                সব পণ্য
            </a>
            @foreach ($categories as $category)
                <a
                    href="{{ route('shop.category', $category) }}"
                    class="shrink-0 rounded-full px-4 py-2 text-xs font-bold transition shadow-xs {{ $selectedCategory?->id === $category->id ? 'bg-[#013F1E] text-white shadow-sm' : 'border border-[#E6E1D8] bg-white text-[#2D1B13] hover:bg-[#F2F8F4]' }}"
                >
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        {{-- Active Filters indicator --}}
        @if (request()->filled('q'))
            <div class="mt-4 flex items-center gap-2 text-xs text-muted">
                <span>অনুসন্ধানের ফলাফল: <strong class="text-ink">"{{ request('q') }}"</strong></span>
                <a href="{{ $selectedCategory ? route('shop.category', $selectedCategory) : route('shop.index') }}" class="text-[#A5000B] underline font-semibold">মুছে ফেলুন</a>
            </div>
        @endif

        {{-- Product Grid --}}
        <div class="product-grid" style="margin-top: 28px;">
            @forelse ($products as $product)
                @include('store.partials.product-card', ['product' => $product])
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-[#013F1E]/20 bg-white p-12 text-center shadow-xs">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#F2F8F4] text-[#013F1E]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <h3 class="mt-4 font-serif text-lg font-bold text-ink">কোনো পণ্য পাওয়া যায়নি</h3>
                    <p class="mt-1 text-xs text-muted">আপনার পছন্দের সাথে মিল রেখে কোনো পণ্য খুঁজে পাওয়া যায়নি।</p>
                    <a href="{{ route('shop.index') }}" class="btn-primary mt-5 rounded-xl text-xs inline-flex items-center gap-1.5">
                        সব পণ্য দেখুন
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($products->hasPages())
            <div class="mt-12">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection
