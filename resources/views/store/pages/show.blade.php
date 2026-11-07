@extends('layouts.store')

@section('title', ($page->meta_title ?: $page->title).' — '.($store['store_name'] ?? 'Foodness'))
@section('meta_description', $page->meta_description ?: $page->subtitle)

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-muted mb-4">
            <a href="{{ route('home') }}" class="hover:text-accent transition">হোম</a>
            <span>/</span>
            <span class="text-ink font-semibold truncate">{{ $page->title }}</span>
        </nav>

        {{-- Page Header Banner --}}
        <div class="rounded-2xl border border-ink/8 bg-gradient-to-r from-canvas via-white to-canvas p-6 sm:p-8 shadow-xs mb-8">
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-accent mb-1.5">পলিসি ও নির্দেশিকা</span>
            <h1 class="font-display text-2xl sm:text-4xl font-extrabold text-ink tracking-tight">{{ $page->title }}</h1>
            @if ($page->subtitle)
                <p class="mt-2 text-xs sm:text-sm text-muted max-w-2xl leading-relaxed">{{ $page->subtitle }}</p>
            @endif
            <div class="mt-4 flex items-center gap-3 text-[11px] text-muted">
                <span>সর্বশেষ আপডেট: {{ $page->updated_at?->format('d F, Y') }}</span>
                <span>·</span>
                <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> সক্রিয় নীতিমালা
                </span>
            </div>
        </div>

        {{-- Content Grid: Sidebar Links + Main Article --}}
        <div class="grid gap-8 lg:grid-cols-[1fr_300px] items-start">
            {{-- Main Article --}}
            <article class="rounded-2xl border border-ink/8 bg-white p-6 sm:p-8 shadow-xs">
                <div class="store-policy-content prose prose-slate max-w-none text-xs sm:text-sm text-ink/85 leading-relaxed space-y-4">
                    {!! \App\Support\HtmlSanitizer::clean($page->content) !!}
                </div>

                {{-- Contact Prompt in Footer of Policy --}}
                <div class="mt-10 border-t border-ink/8 pt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-mist/30 -mx-6 -mb-6 p-6 rounded-b-2xl">
                    <div>
                        <h4 class="font-display font-bold text-ink text-sm">কোনো প্রশ্ন বা জিজ্ঞাসা আছে?</h4>
                        <p class="text-xs text-muted mt-0.5">আমাদের সাপোর্ট টিম সপ্তাহের ৭ দিনই আপনার সেবায় নিয়োজিত।</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('contact.index') }}" class="rounded-xl bg-accent px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-accent-deep transition">
                            যোগাযোগ করুন
                        </a>
                        <a href="{{ route('order-track.index') }}" class="rounded-xl border border-ink/15 bg-white px-4 py-2 text-xs font-bold text-ink hover:bg-mist transition">
                            অর্ডার ট্র্যাক
                        </a>
                    </div>
                </div>
            </article>

            {{-- Sidebar Menu: Quick Navigation --}}
            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="rounded-2xl border border-ink/8 bg-white p-5 shadow-xs">
                    <h3 class="font-display text-xs font-bold uppercase tracking-wider text-muted mb-3">গুরুত্বপূর্ণ পেইজ ও পলিসি</h3>
                    <nav class="space-y-1 text-xs font-semibold">
                        @php
                            $navItems = [
                                ['slug' => 'privacy-policy', 'route' => 'page.privacy', 'title' => 'গোপনীয়তা নীতি', 'icon' => 'shield'],
                                ['slug' => 'refund-return-policy', 'route' => 'page.refund-return', 'title' => 'রিফান্ড ও রিটার্ন নীতি', 'icon' => 'refresh'],
                                ['slug' => 'terms-and-conditions', 'route' => 'page.terms', 'title' => 'শর্তাবলি ও নিয়মাবলী', 'icon' => 'document'],
                                ['slug' => 'shipping-policy', 'route' => 'page.shipping', 'title' => 'শিপিং ও ডেলিভারি নীতি', 'icon' => 'truck'],
                                ['slug' => 'payment-policy', 'route' => 'page.payment', 'title' => 'পেমেন্ট পদ্ধতি ও নীতি', 'icon' => 'credit-card'],
                                ['slug' => 'how-to-order', 'route' => 'page.how-to-order', 'title' => 'কীভাবে অর্ডার করবেন', 'icon' => 'shopping-cart'],
                                ['slug' => 'order-track', 'route' => 'order-track.index', 'title' => 'অর্ডার ট্র্যাক করুন', 'icon' => 'location'],
                                ['slug' => 'faq', 'route' => 'faq.index', 'title' => 'প্রশ্নোত্তর (FAQ)', 'icon' => 'help'],
                                ['slug' => 'contact', 'route' => 'contact.index', 'title' => 'যোগাযোগ ও সাপোর্ট', 'icon' => 'phone'],
                            ];
                        @endphp

                        @foreach ($navItems as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                @class([
                                    'flex items-center justify-between px-3 py-2.5 rounded-xl transition',
                                    'bg-accent text-white shadow-xs font-bold' => $page->slug === $item['slug'],
                                    'text-ink hover:bg-mist/60' => $page->slug !== $item['slug'],
                                ])
                            >
                                <span>{{ $item['title'] }}</span>
                                <span class="text-xs">&rarr;</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                {{-- Direct Hotline Card --}}
                @if (!empty($store['store_phone']))
                    <div class="rounded-2xl border border-primary/10 bg-primary/5 p-4 text-xs space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-primary">জরুরি হেল্পলাইন</span>
                        <p class="font-display font-extrabold text-base text-ink">{{ $store['store_phone'] }}</p>
                        <p class="text-[11px] text-muted">সরাসরি কল করে যেকোনো তথ্যের সমাধান নিন।</p>
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
