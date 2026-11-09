@extends('layouts.store')

@section('title', ($page->meta_title ?? 'প্রশ্নোত্তর (FAQ)').' — '.($store['store_name'] ?? 'Foodness'))
@section('meta_description', $page->meta_description ?? 'সচরাচর জিজ্ঞাসিত প্রশ্ন ও সহজ উত্তর।')

@section('content')
    <section class="store-container py-8 sm:py-12">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-muted mb-4">
            <a href="{{ route('home') }}" class="hover:text-accent transition">হোম</a>
            <span>/</span>
            <span class="text-ink font-semibold">প্রশ্নোত্তর (FAQ)</span>
        </nav>

        {{-- Page Header Banner --}}
        <div class="rounded-2xl border border-ink/8 bg-gradient-to-r from-canvas via-white to-canvas p-6 sm:p-8 shadow-xs text-center max-w-3xl mx-auto mb-8">
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-accent mb-1.5">সচরাচর জিজ্ঞাসা</span>
            <h1 class="font-display text-2xl sm:text-4xl font-extrabold text-ink tracking-tight">{{ $page->title ?? 'প্রশ্নোত্তর (FAQ)' }}</h1>
            <p class="mt-2 text-xs sm:text-sm text-muted max-w-xl mx-auto leading-relaxed">
                {{ $page->subtitle ?? 'আমাদের সেবা, পণ্য, ডেলিভারি ও রিটার্ন সংক্রান্ত সাধারণ প্রশ্নগুলোর সহজ উত্তর নিচে দেওয়া হলো।' }}
            </p>
        </div>

        {{-- Main FAQ Content --}}
        <div class="max-w-3xl mx-auto space-y-6">
            <article class="rounded-2xl border border-ink/8 bg-white p-6 sm:p-8 shadow-xs">
                <div class="store-faq-content prose prose-slate max-w-none text-xs sm:text-sm text-ink/85 leading-relaxed space-y-4">
                    {!! \App\Support\HtmlSanitizer::clean($page->content) !!}
                </div>

                {{-- Still have questions box --}}
                <div class="mt-10 border-t border-ink/8 pt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-mist/30 -mx-6 -mb-6 p-6 rounded-b-2xl">
                    <div>
                        <h4 class="font-display font-bold text-ink text-sm">আপনার প্রশ্নের উত্তর খুঁজে পাননি?</h4>
                        <p class="text-xs text-muted mt-0.5">আমাদের সাপোর্ট টিম সপ্তাহের ৭ দিনই সরাসরি কথা বলতে প্রস্তুত।</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (!empty($store['store_phone']))
                            <a href="tel:{{ $store['store_phone'] }}" class="rounded-xl bg-accent px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-accent-deep transition">
                                সরাসরি কল করুন
                            </a>
                        @endif
                        <a href="{{ route('contact.index') }}" class="rounded-xl border border-ink/15 bg-white px-4 py-2 text-xs font-bold text-ink hover:bg-mist transition">
                            যোগাযোগ পেজ &rarr;
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection
