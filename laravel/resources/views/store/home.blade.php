@extends('layouts.store')

@php
    use App\Support\Money;

    $heroProduct = $featuredProducts->first();
    $customHeroImg = !empty($store['hero_custom_image']) && file_exists(public_path('storage/'.$store['hero_custom_image']))
        ? asset('storage/'.$store['hero_custom_image'])
        : null;
    $chatAvatar = (! empty($store['chat_avatar']) && (\Illuminate\Support\Facades\Storage::disk('public')->exists($store['chat_avatar']) || file_exists(public_path('storage/'.$store['chat_avatar']))))
        ? asset('storage/'.$store['chat_avatar'])
        : ((! empty($store['site_favicon']) && (\Illuminate\Support\Facades\Storage::disk('public')->exists($store['site_favicon']) || file_exists(public_path('storage/'.$store['site_favicon']))))
            ? asset('storage/'.$store['site_favicon'])
            : (file_exists(public_path('favicon.ico')) ? asset('favicon.ico') : null));

    $faqItems = [];
    for ($i = 1; $i <= 6; $i++) {
        $q = $store["faq_q{$i}"] ?? null;
        $a = $store["faq_a{$i}"] ?? null;
        if ($q && $a) {
            $faqItems[] = ['q' => $q, 'a' => $a];
        }
    }
    if (empty($faqItems)) {
        $faqItems = [
            ['q' => 'পণ্য ডেলিভারি পেতে কতদিন সময় লাগে?', 'a' => 'ঢাকার ভেতরে ২৪–৪৮ ঘণ্টার মধ্যে এবং ঢাকার বাইরে ২–৩ দিনের মধ্যে হোম ডেলিভারি পৌঁছে দেওয়া হয়।'],
            ['q' => 'ডেলিভারি চার্জ কত এবং টাকা পরিশোধ কীভাবে করব?', 'a' => '১০০% ক্যাশ অন ডেলিভারি (COD)। পণ্য হাতে পেয়ে যাচাই করে ডেলিভারি ম্যানের কাছে মূল্য পরিশোধ করতে পারবেন।'],
            ['q' => 'পণ্য খাঁটি হওয়ার নিশ্চয়তা কী?', 'a' => 'আমরা সরাসরি নিজস্ব তত্ত্বাবধানে এবং গ্রামীণ ঐতিহ্যবাহী কারিগরদের মাধ্যমে কোনো রাসায়নিক বা ভেজাল ছাড়াই শতভাগ প্রাকৃতিক খাবার সরবরাহ করি।'],
            ['q' => 'পণ্য গ্রহণের সময় দেখে নেওয়ার সুযোগ আছে কি?', 'a' => 'হ্যাঁ, অবশ্যই! ডেলিভারি ম্যানের সামনে পার্সেল খুলে গুণগত মান নিশ্চিত হয়ে মূল্য পরিশোধ করতে পারবেন।'],
            ['q' => 'গুড় কীভাবে সংরক্ষণ করব?', 'a' => 'পাটালি ও বীজ গুড় শুকনো ঠান্ডা স্থানে বায়ুরোধী পাত্রে রাখলে বহুদিন ভালো থাকে। ঝোলা গুড় ফ্রিজে রাখলে দীর্ঘ সময় তরল ও সুস্বাদু থাকে।'],
            ['q' => 'পাইকারি বা বেশি পরিমাণে নিতে চাইলে যোগাযোগ করব কীভাবে?', 'a' => 'পাইকারি বা পারিবারিক বড় অর্ডারের জন্য সরাসরি আমাদের দেওয়া নম্বরে কল করুন অথবা নিচের হোয়াটসঅ্যাপে বার্তা দিন।'],
        ];
    }

    // Dynamic Homepage Sections Order (Configurable via Admin Drag-and-Drop Reorder Manager)
    $allHomeSections = ['hero', 'stories', 'categories', 'gur_showcase', 'dynamic_sections', 'promo_banner', 'featured', 'story_reel', 'promises', 'faq'];
    $rawOrder = $store['home_sections_order'] ?? null;
    $sectionsOrder = [];
    if (!empty($rawOrder)) {
        $decoded = is_array($rawOrder) ? $rawOrder : json_decode($rawOrder, true);
        if (is_array($decoded)) {
            foreach ($decoded as $k) {
                if (in_array($k, $allHomeSections, true) && !in_array($k, $sectionsOrder, true)) {
                    $sectionsOrder[] = $k;
                }
            }
        }
    }
    foreach ($allHomeSections as $k) {
        if (!in_array($k, $sectionsOrder, true)) {
            $sectionsOrder[] = $k;
        }
    }
@endphp

@section('title', $seo['site_title'] ?? (($store['store_name'] ?? config('app.name', 'Store')).' — ১০০% খাঁটি ও গুণগত পণ্য'))

@section('content')
<div class="fn-home">
    @foreach ($sectionsOrder as $secKey)
        {{-- 1. STORIES SECTION --}}
        @if ($secKey === 'stories')
            @if ((($store['home_show_stories'] ?? '1') === '1') && isset($stories) && $stories->isNotEmpty())
                <section class="store-container pt-4 pb-2 sm:pb-4">
                    @if (view()->exists('store.partials.hero-story-slider'))
                        @include('store.partials.hero-story-slider', ['stories' => $stories])
                    @else
                        @include('store.partials.stories', ['stories' => $stories])
                    @endif
                </section>
            @endif

        {{-- 2. DYNAMIC CATEGORY SHOWCASE SECTION --}}
        @elseif ($secKey === 'categories')
            @if (($store['home_show_categories'] ?? '1') === '1' && isset($categories) && $categories->isNotEmpty())
                @php
                    $catTitle = $store['category_section_title'] ?? 'পছন্দের ক্যাটাগরি';
                @endphp

                <section id="categories" class="category-section section">
                    <div class="fn-container">
                        <div class="section-heading section-heading--row">
                            <div><h2>{{ $catTitle }}</h2></div>
                            <a href="{{ route('shop.index') }}">সবগুলো দেখুন <span>→</span></a>
                        </div>
                        <div class="category-grid">
                            @foreach ($categories as $cat)
                                @php
                                    $pCount = $cat->products_count ?? $cat->products()->count();
                                @endphp
                                <a class="category-card" href="{{ route('shop.category', $cat) }}">
                                    <span class="category-card__image"><img src="{{ $cat->imageUrl() }}" alt="{{ $cat->name }}"></span>
                                    <strong>{{ $cat->name }}</strong>
                                    <small>{{ $pCount > 0 ? \App\Support\Money::toBengali($pCount).'টি পণ্য' : 'শীঘ্রই' }}</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

        {{-- INTERACTIVE GUR SHOWCASE (Accordion & Timeline) --}}
        @elseif ($secKey === 'gur_showcase')
            @if (($store['home_show_gur_showcase'] ?? '1') === '1')
                @include('store.partials.gur-showcase')
            @endif

        {{-- 3. DYNAMIC PRODUCT SHOWCASE SECTIONS (Tabs Builder) --}}
        @elseif ($secKey === 'dynamic_sections')
            @if (!empty($dynamicSections) && count($dynamicSections) > 0)
                @foreach ($dynamicSections as $secIdx => $sec)
                    @php
                        $secId = $sec['id'] ?? ('sec_' . $secIdx);
                        $secTitle = $sec['title'] ?? 'এ সপ্তাহের জনপ্রিয়';
                        $secLinkText = $sec['view_all_text'] ?? 'সব পণ্য';
                        $secLinkUrl = $sec['view_all_url'] ?? route('shop.index');
                        $secTabs = $sec['tabs'] ?? [];
                        $secProducts = $sec['products'] ?? collect();
                        $secLimit = max(4, min(36, (int) ($sec['product_limit'] ?? 8)));
                    @endphp

                    <section
                        id="{{ $secIdx === 0 ? 'sera-bikri' : $secId }}"
                        class="section products-section dynamic-product-section fn-showcase"
                        data-section-id="{{ $secId }}"
                        data-product-limit="{{ $secLimit }}"
                    >
                        <div class="fn-container">
                        <div class="fn-showcase-head">
                            <div class="fn-showcase-title">
                                <span class="fn-showcase-kicker">কালেকশন</span>
                                <h2>{{ $secTitle }}</h2>
                            </div>

                            <div class="fn-showcase-tools">
                                @if (!empty($secTabs) && count($secTabs) > 0)
                                    <div class="fn-showcase-tabs" data-section-tabs="{{ $secId }}">
                                        @foreach ($secTabs as $tabIdx => $tab)
                                            <button
                                                type="button"
                                                class="sec-tab-btn fn-showcase-tab {{ $tabIdx === 0 ? 'active' : '' }}"
                                                data-section="{{ $secId }}"
                                                data-tab-type="{{ $tab['type'] ?? 'all' }}"
                                                data-target-id="{{ $tab['target_id'] ?? '' }}"
                                            >
                                                {{ $tab['label'] ?? 'ট্যাব' }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($secLinkText))
                                    <a class="fn-showcase-all" href="{{ $secLinkUrl }}">
                                        {{ $secLinkText }} <span>→</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Product Grid: Responsive 4 Columns on Desktop, 2 on Mobile --}}
                        <div class="product-grid" data-section-grid="{{ $secId }}">
                            @php
                                $firstTab = $secTabs[0] ?? null;
                                $firstTabType = $firstTab['type'] ?? 'all';
                                $firstTargetId = (string) ($firstTab['target_id'] ?? '');
                                $shownCount = 0;
                            @endphp
                            @forelse ($secProducts as $pIdx => $product)
                                @php
                                    $matchesFirstTab = true;
                                    if ($firstTabType === 'category' && !empty($firstTargetId)) {
                                        $matchesFirstTab = ((string) $product->category_id === $firstTargetId);
                                    } elseif ($firstTabType === 'subcategory' && !empty($firstTargetId)) {
                                        $matchesFirstTab = ((string) $product->subcategory_id === $firstTargetId);
                                    } elseif ($firstTabType === 'childcategory' && !empty($firstTargetId)) {
                                        $matchesFirstTab = ((string) $product->childcategory_id === $firstTargetId);
                                    }
                                    $isVisible = $matchesFirstTab && ($shownCount < $secLimit);
                                    if ($matchesFirstTab) {
                                        $shownCount++;
                                    }
                                @endphp
                                <div
                                    class="sec-product-item sera-product-item {{ $isVisible ? '' : 'hidden' }}"
                                    data-section="{{ $secId }}"
                                    data-category="{{ $product->category_id }}"
                                    data-subcategory="{{ $product->subcategory_id }}"
                                    data-childcategory="{{ $product->childcategory_id }}"
                                    data-index="{{ $pIdx }}"
                                >
                                    @include('store.partials.product-card', ['product' => $product])
                                </div>
                            @empty
                                <div class="fn-showcase-empty">
                                    কোনো পণ্য পাওয়া যায়নি।
                                </div>
                            @endforelse
                        </div>

                        {{-- Dynamic Empty State when filtering tabs has 0 results --}}
                        <div class="fn-showcase-empty {{ ($shownCount === 0 && $secProducts->isNotEmpty()) ? '' : 'hidden' }}" data-section-empty="{{ $secId }}">
                            <p>এই ক্যাটাগরিতে বর্তমানে কোনো পণ্য নেই।</p>
                        </div>
                        </div>
                    </section>
                @endforeach
            @endif

        {{-- 4. FEATURED / TRENDING PRODUCTS SECTION --}}
        @elseif ($secKey === 'featured')
            @if (($store['home_show_featured'] ?? '1') === '1' && isset($featuredProducts) && $featuredProducts->isNotEmpty())
                @php
                    $featMobileCols = ($store['product_cols_mobile'] ?? '2') === '1' ? 'grid-cols-1' : 'grid-cols-2';
                    $featTabletMap = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-3', '4' => 'sm:grid-cols-4'];
                    $featTabletCols = $featTabletMap[$store['product_cols_tablet'] ?? '3'] ?? 'sm:grid-cols-3';
                    $featDesktopMap = ['2' => 'lg:grid-cols-2', '3' => 'lg:grid-cols-3', '4' => 'lg:grid-cols-4', '5' => 'lg:grid-cols-5', '6' => 'lg:grid-cols-6'];
                    $featDesktopCols = $featDesktopMap[$store['product_cols_desktop'] ?? '4'] ?? 'lg:grid-cols-4';
                    $featLimit = (int) ($store['home_product_limit'] ?? 8);
                @endphp
                <section id="products" class="section products-section">
                    <div class="fn-container">
                        <div class="section-heading section-heading--row">
                            <div><h2>{{ $store['featured_section_title'] ?? 'জনপ্রিয় পণ্যসমূহ' }}</h2></div>
                            <a href="{{ route('shop.index') }}">সবগুলো দেখুন <span>→</span></a>
                        </div>
                        <div class="product-grid" id="featured-products">
                            @foreach ($featuredProducts->take($featLimit) as $product)
                                @include('store.partials.product-card', ['product' => $product])
                            @endforeach
                        </div>
                    </div>
                </section>
                @php
                    $seasonalPool = $featuredProducts->merge($trendingProducts ?? collect())->unique('id');
                    $seasonalProducts = $seasonalPool->filter(function ($p) {
                        $catName = $p->category->name ?? '';
                        $slug = mb_strtolower($p->category->slug ?? '');
                        $isAmsotto = str_contains($catName, 'আমসত্ত্ব') || str_contains($slug, 'amsatto') || str_contains($slug, 'aamshotto');
                        return ! $isAmsotto && (str_contains($catName, 'আম') || str_contains($slug, 'mango'));
                    })->take(4)->values();
                @endphp
                @if ($seasonalProducts->isNotEmpty())
                    <section class="section mango-section" id="mangoes">
                        <div class="fn-container">
                            <div class="section-heading section-heading--row">
                                <div><h2>সিজনাল কালেকশন</h2></div>
                                <a href="{{ route('shop.index') }}">সবগুলো দেখুন <span>→</span></a>
                            </div>
                            <div class="product-grid">
                                @foreach ($seasonalProducts as $product)
                                    @include('store.partials.product-card', ['product' => $product])
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            @endif

        {{-- 5. AMADER GOLPO (OUR STORY & VIDEO REEL) SECTION --}}
        @elseif ($secKey === 'story_reel')
            @if (($store['home_show_story_section'] ?? '1') === '1')
                @php
                    $storyVideoFile = !empty($store['story_reel_video']) ? asset('storage/' . $store['story_reel_video']) : null;
                    $storyVideoUrl = !empty($store['story_reel_video_url']) ? trim($store['story_reel_video_url']) : null;
                    $hasStoryPhoto = !empty($store['story_reel_image']) && file_exists(public_path('storage/' . $store['story_reel_image']));
                    $storyPoster = $hasStoryPhoto
                        ? asset('storage/' . $store['story_reel_image'])
                        : \App\Support\PlaceholderImage::bannerUrl();

                    $storyVideoSrc = $storyVideoFile ?: $storyVideoUrl;
                    $youtubeId = null;

                    if ($storyVideoUrl && preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $storyVideoUrl, $ytMatch)) {
                        $youtubeId = $ytMatch[1];
                        if (empty($store['story_reel_image'])) {
                            $storyPoster = 'https://img.youtube.com/vi/' . $youtubeId . '/hqdefault.jpg';
                        }
                    }
                @endphp
                <section class="section fn-reel" id="story">
                    <span id="amader-golpo"></span>
                    <div class="fn-container fn-reel-grid">
                        <div class="fn-reel-media">
                            @if ($youtubeId)
                                <iframe
                                    src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&controls=1&modestbranding=1&playsinline=1&rel=0"
                                    title="{{ $store['story_reel_title'] ?? 'আমাদের গল্প' }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            @elseif ($storyVideoSrc)
                                <video src="{{ $storyVideoSrc }}" poster="{{ $storyPoster }}" autoplay muted loop playsinline></video>
                            @elseif ($hasStoryPhoto)
                                <img src="{{ $storyPoster }}" alt="{{ $store['story_section_title'] ?? 'আমাদের গল্প' }}">
                            @else
                                <div class="fn-reel-empty">
                                    <span>{{ $store['store_name'] ?? 'ফুডনেস' }}</span>
                                    <strong>{{ $store['story_section_title'] ?? 'আমাদের গল্প' }}</strong>
                                    <small>খাঁটি স্বাদ, সনাতন পদ্ধতি</small>
                                </div>
                            @endif
                        </div>
                        <div class="fn-reel-copy">
                            <span class="fn-reel-kicker">আমাদের গল্প</span>
                            <h2>{{ $store['story_section_title'] ?? 'শেকড়ের স্বাদ ও বিশুদ্ধতার প্রতি দায়বদ্ধতা' }}</h2>
                            <p>{{ $store['story_section_desc'] ?? 'Foodness-এর যাত্রা শুরু হয়েছিল একটি সহজ বিশ্বাস থেকে—খাবার হবে প্রকৃতির মতোই খাঁটি। আমরা সরাসরি কৃষক ও স্থানীয় নারী উদ্যোক্তাদের কাছ থেকে পণ্য সংগ্রহ করি।' }}</p>
                            <ol class="fn-reel-steps">
                                <li><span>১</span><div><strong>{{ $store['story_step_1_title'] ?? 'বাছাই করা উপকরণ' }}</strong><small>{{ $store['story_step_1_desc'] ?? 'সেরা মানের ফল ও মসলা ব্যবহার' }}</small></div></li>
                                <li><span>২</span><div><strong>{{ $store['story_step_2_title'] ?? 'স্বাস্থ্যসম্মত প্রস্তুতি' }}</strong><small>{{ $store['story_step_2_desc'] ?? 'পরিচ্ছন্ন পরিবেশে যত্ন নিয়ে তৈরি' }}</small></div></li>
                                <li><span>৩</span><div><strong>{{ $store['story_step_3_title'] ?? 'কোনো কৃত্রিম রং নয়' }}</strong><small>{{ $store['story_step_3_desc'] ?? 'প্রিজারভেটিভ ও ক্ষতিকর রাসায়নিকমুক্ত' }}</small></div></li>
                            </ol>
                            <a class="fn-reel-cta" href="{{ !empty($store['story_btn_url']) ? $store['story_btn_url'] : route('shop.index') }}">{{ $store['story_btn_text'] ?? 'আরও জানুন' }} <span>→</span></a>
                        </div>
                    </div>
                </section>
            @endif

        {{-- 6. BRAND VALUES & PROMISES (4 CARDS) --}}
        @elseif ($secKey === 'promises')
            @if (($store['home_show_promises'] ?? '1') === '1')
                <section class="section fn-promises">
                    <div class="fn-container">
                        <div class="fn-promises-head">
                            <span>প্রতিশ্রুতি</span>
                            <h2>{{ $store['promises_title'] ?? 'শতভাগ খাঁটি ও স্বাস্থ্যসম্মত খাদ্যের নিশ্চয়তা' }}</h2>
                        </div>
                        <div class="fn-promises-grid">
                            <article>
                                <span class="fn-promises-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                                <h3>{{ $store['promise_1_title'] ?? '১০০% ভেজালমুক্ত' }}</h3>
                                <p>{{ $store['promise_1_desc'] ?? 'কোনো কৃত্রিম রঙ, ক্ষতিকর রাসায়নিক বা চিনি মেশানো হয় না।' }}</p>
                            </article>
                            <article>
                                <span class="fn-promises-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c2.5 3 4 5.5 4 8a4 4 0 11-8 0c0-2.5 1.5-5 4-8zM12 15v6"/></svg>
                                </span>
                                <h3>{{ $store['promise_2_title'] ?? 'সনাতন পদ্ধতিতে তৈরি' }}</h3>
                                <p>{{ $store['promise_2_desc'] ?? 'মাটির চুলায় কাঠের জ্বাল ও খাঁটি গ্রামীণ অভিজ্ঞ কারিগরের হাতে প্রস্তুত।' }}</p>
                            </article>
                            <article>
                                <span class="fn-promises-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </span>
                                <h3>{{ $store['promise_3_title'] ?? 'ক্যাশ অন ডেলিভারি' }}</h3>
                                <p>{{ $store['promise_3_desc'] ?? 'পণ্য হাতে পেয়ে দেখে শুনে মূল্য পরিশোধের সম্পূর্ণ নিশ্চিন্ত সুযোগ।' }}</p>
                            </article>
                            <article>
                                <span class="fn-promises-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <h3>{{ $store['promise_4_title'] ?? 'দ্রুত হোম ডেলিভারি' }}</h3>
                                <p>{{ $store['promise_4_desc'] ?? 'সারা দেশে দ্রুততম সময়ে নির্ভরযোগ্য কুরিয়ারের মাধ্যমে সুরক্ষিত ডেলিভারি।' }}</p>
                            </article>
                        </div>
                    </div>
                </section>
            @endif

        {{-- 7. FAQ LIVE CHAT SIMULATOR SECTION --}}
        @elseif ($secKey === 'faq')
            @if (($store['home_show_faq'] ?? '1') === '1')
                @php
                    $rawPhone = preg_replace('/[^0-9]/', '', $store['floating_whatsapp_number'] ?? ($store['social_whatsapp'] ?? ($store['store_phone'] ?? '01700000000')));
                    $waNumber = str_starts_with($rawPhone, '0') ? '88'.$rawPhone : ($rawPhone ?: '8801700000000');
                    $storeLogo = !empty($store['store_logo']) && file_exists(public_path('storage/'.$store['store_logo']))
                        ? asset('storage/'.$store['store_logo'])
                        : \App\Support\PlaceholderImage::url();
                @endphp
                <section id="faq" class="section fn-faq">
                    <div class="fn-container">
                    <div class="fn-faq-head">
                        <span>প্রশ্নোত্তর</span>
                        <h2>{{ $store['faq_title'] ?? 'সাধারণ জিজ্ঞাসা' }}</h2>
                    </div>

                    <div class="fn-faq-layout">
                    <div class="fn-faq-ask">
                        <p class="fn-faq-ask-label">একটি প্রশ্ন নির্বাচন করুন</p>
                        <div id="faq-chips-container">
                            @foreach ($faqItems as $idx => $item)
                                <button
                                    type="button"
                                    data-faq-index="{{ $idx }}"
                                    class="faq-chip {{ $idx === 0 ? 'active' : '' }}"
                                >
                                    {{ $item['q'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="fn-faq-card">
                        {{-- Chat Header --}}
                        <div class="fn-faq-agent">
                            <div class="relative shrink-0">
                                @if ($chatAvatar)
                                    <div class="h-11 w-11 rounded-full overflow-hidden bg-white p-1 border border-[#013F1E]/15 shadow-xs">
                                        <img src="{{ $chatAvatar }}" alt="{{ $store['store_name'] ?? config('app.name', 'Store') }} কাস্টমার কেয়ার" class="h-full w-full rounded-full object-cover">
                                    </div>
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#013F1E] text-white shadow-xs">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </div>
                                @endif
                                <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-[#013F1E] ring-2 ring-white"></span>
                            </div>
                            <div>
                                <h3 class="font-serif text-sm sm:text-base font-extrabold text-[#0C2417] leading-tight">
                                    {{ $store['store_name'] ?? config('app.name', 'Store') }} কাস্টমার কেয়ার
                                </h3>
                                <p class="text-[11px] text-[#385545] font-medium flex items-center gap-1.5">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#013F1E] animate-pulse"></span>
                                    <span>অনলাইন · তাৎক্ষণিক উত্তর দিচ্ছে</span>
                                </p>
                            </div>
                        </div>

                        {{-- Chat Conversation Thread --}}
                        <div id="faq-chat-thread" class="py-6 space-y-4 min-h-[170px]">
                            {{-- User Message Bubble (Right) --}}
                            <div class="flex flex-col items-end">
                                <div id="faq-user-bubble" class="rounded-2xl rounded-tr-xs bg-[#013F1E] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs max-w-[90%] sm:max-w-[75%] transition-all duration-300">
                                    {{ $faqItems[0]['q'] ?? 'পণ্য ডেলিভারি পেতে কতদিন সময় লাগে?' }}
                                </div>
                                <div class="mt-1 flex items-center gap-1 text-[10px] text-[#0C2417]/60 font-semibold pr-1">
                                    <span>এইমাত্র</span>
                                    <span class="font-black text-[#013F1E]">✓✓</span>
                                </div>
                            </div>

                            {{-- Typing Indicator (Initially hidden) --}}
                            <div id="faq-typing-indicator" class="hidden items-center gap-2">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full overflow-hidden bg-white border border-[#013F1E]/15 p-0.5 shadow-xs">
                                    @if ($chatAvatar)
                                        <img src="{{ $chatAvatar }}" alt="{{ $store['store_name'] ?? config('app.name', 'Store') }}" class="h-full w-full rounded-full object-cover">
                                    @else
                                        <span class="text-[10px] font-bold text-[#013F1E]">💬</span>
                                    @endif
                                </div>
                                <div class="rounded-2xl rounded-tl-xs bg-[#F5F8F5] border border-[#013F1E]/10 px-4 py-2.5 text-xs text-[#0C2417]/60 flex items-center gap-1">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#013F1E] animate-bounce"></span>
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#013F1E] animate-bounce [animation-delay:0.15s]"></span>
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#013F1E] animate-bounce [animation-delay:0.3s]"></span>
                                </div>
                            </div>

                            {{-- Support Bot Reply Bubble (Left) --}}
                            <div id="faq-support-row" class="flex items-start gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full overflow-hidden bg-white border border-[#013F1E]/15 p-0.5 mt-0.5 shadow-xs">
                                    @if ($chatAvatar)
                                        <img src="{{ $chatAvatar }}" alt="{{ $store['store_name'] ?? config('app.name', 'Store') }}" class="h-full w-full rounded-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-[#013F1E] text-white">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div id="faq-support-bubble" class="rounded-2xl rounded-tl-xs bg-[#F5F8F5] border border-[#013F1E]/10 px-4 py-3 text-xs sm:text-sm text-[#0C2417] leading-relaxed shadow-xs max-w-[90%] sm:max-w-[80%] transition-opacity duration-300">
                                    {{ $faqItems[0]['a'] ?? 'ঢাকার ভেতরে ২৪–৪৮ ঘণ্টার মধ্যে এবং ঢাকার বাইরে ২–৩ দিনের মধ্যে হোম ডেলিভারি পৌঁছে দেওয়া হয়।' }}
                                </div>
                            </div>
                        </div>

                        {{-- Bottom WhatsApp Input Bar --}}
                        @if (($store['faq_show_whatsapp'] ?? '1') === '1')
                            <div class="mt-6 pt-2">
                                <form id="faq-whatsapp-form" class="fn-faq-form">
                                    <input
                                        type="text"
                                        id="faq-custom-input"
                                        placeholder="{{ $store['faq_whatsapp_placeholder'] ?? 'অন্য কোনো প্রশ্ন আছে? হোয়াটসঅ্যাপে মেসেজ দিন...' }}"
                                        class="w-full bg-transparent text-xs sm:text-sm text-[#0C2417] placeholder:text-[#385545]/60 focus:outline-none pr-3"
                                    >
                                    <button
                                        type="submit"
                                        aria-label="Send via WhatsApp"
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#013F1E] text-white hover:bg-[#012E16] shadow-sm transition active:scale-95 cursor-pointer"
                                    >
                                        <svg class="h-4 w-4 transform rotate-45 -translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                    </div>
                    </div>
                </section>
            @endif

        {{-- HERO IMAGE SLIDER SECTION --}}
        @elseif ($secKey === 'hero')
            @if (($store['home_show_hero'] ?? '1') === '1')
                @php
                    $rawSlides = $store['hero_slides'] ?? null;
                    $slides = [];
                    if (!empty($rawSlides)) {
                        $decoded = is_array($rawSlides) ? $rawSlides : json_decode($rawSlides, true);
                        if (is_array($decoded)) {
                            foreach ($decoded as $s) {
                                if (!empty($s['image']) && ($s['is_active'] ?? true)) {
                                    $slides[] = $s;
                                }
                            }
                        }
                    }

                    // Fallback to custom hero image or placeholder if no custom slides configured
                    if (empty($slides)) {
                        if (!empty($store['hero_custom_image']) && file_exists(public_path('storage/'.$store['hero_custom_image']))) {
                            $slides[] = [
                                'image' => asset('storage/'.$store['hero_custom_image']),
                                'link_url' => route('shop.index'),
                                'title' => $store['store_name'] ?? 'Hero Banner',
                            ];
                        } else {
                            $slides[] = [
                                'image' => \App\Support\PlaceholderImage::bannerUrl(),
                                'link_url' => route('shop.index'),
                                'title' => $store['store_name'] ?? 'Banner',
                            ];
                        }
                    }

                    $autoplaySpeed = (int) ($store['hero_slider_autoplay'] ?? 5000);
                    $slidesCount = count($slides);
                    $heroWidth = $store['hero_slider_width'] ?? 'full'; // 'full', 'wide', 'boxed'
                @endphp

                @if ($slidesCount > 0)
                    @php
                        if ($heroWidth === 'wide') {
                            $heroWrapClass = 'max-w-7xl mx-auto px-4 sm:px-6 my-4 sm:my-8';
                            $heroBoxClass = 'rounded-2xl overflow-hidden shadow-lg';
                        } elseif ($heroWidth === 'boxed') {
                            $heroWrapClass = 'store-container pt-3 pb-5 sm:py-6';
                            $heroBoxClass = 'rounded-2xl sm:rounded-3xl overflow-hidden shadow-lg';
                        } else {
                            $heroWrapClass = 'w-full mb-6 sm:mb-10 overflow-hidden';
                            $heroBoxClass = 'w-full overflow-hidden';
                        }
                    @endphp

                    <section class="hero-carousel" id="hero" aria-label="বিশেষ পণ্য" data-autoplay="{{ $autoplaySpeed }}">
                        <div class="hero-slides">
                            @foreach ($slides as $idx => $slide)
                                @php
                                    $rawImg = $slide['image'] ?? '';
                                    $imgUrl = (str_starts_with($rawImg, 'http') || str_starts_with($rawImg, '/'))
                                        ? $rawImg
                                        : (file_exists(public_path('storage/'.$rawImg)) ? asset('storage/'.$rawImg) : asset($rawImg));
                                    $slideLink = ! empty($slide['link_url']) ? $slide['link_url'] : route('shop.index');
                                @endphp
                                <a class="hero-slide {{ $idx === 0 ? 'active' : '' }}" href="{{ $slideLink }}">
                                    <img src="{{ $imgUrl }}" alt="{{ $slide['title'] ?? 'Banner slide '.($idx + 1) }}" @if($idx > 0) loading="lazy" @endif>
                                </a>
                            @endforeach
                        </div>
                        @if ($slidesCount > 1)
                            <button class="carousel-arrow carousel-arrow--prev" type="button" aria-label="আগের স্লাইড">‹</button>
                            <button class="carousel-arrow carousel-arrow--next" type="button" aria-label="পরের স্লাইড">›</button>
                            <div class="carousel-dots" aria-label="স্লাইড নির্বাচন">
                                @for ($i = 0; $i < $slidesCount; $i++)
                                    <button class="{{ $i === 0 ? 'active' : '' }}" type="button" aria-label="স্লাইড {{ $i + 1 }}"></button>
                                @endfor
                            </div>
                        @endif
                    </section>
                @endif
            @endif

        {{-- PROMOTIONAL FULL BANNER SECTION --}}
        @elseif ($secKey === 'promo_banner')
            @php
                $showPromo = ($store['home_show_promo_banner'] ?? '1') === '1';
                $promoRaw = $store['promo_banner_image'] ?? null;
                $promoImg = null;
                if (!empty($promoRaw) && $promoRaw !== 'none') {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($promoRaw) || file_exists(public_path('storage/'.$promoRaw))) {
                        $promoImg = asset('storage/'.$promoRaw);
                    } elseif (file_exists(public_path($promoRaw))) {
                        $promoImg = asset($promoRaw);
                    } elseif (str_starts_with($promoRaw, 'http') || str_starts_with($promoRaw, '/')) {
                        $promoImg = $promoRaw;
                    }
                } elseif ($promoRaw === null) {
                    $promoImg = \App\Support\PlaceholderImage::bannerUrl();
                }
                $promoWidth = $store['promo_banner_width'] ?? 'full'; // 'full', 'wide', 'boxed'
            @endphp
            @if ($showPromo && !empty($promoImg))
                @php
                    if ($promoWidth === 'wide') {
                        $promoWrapClass = 'max-w-7xl mx-auto px-4 sm:px-6 my-6 sm:my-10';
                        $promoBoxClass = 'rounded-2xl overflow-hidden shadow-md';
                    } elseif ($promoWidth === 'boxed') {
                        $promoWrapClass = 'store-container py-3 sm:py-5';
                        $promoBoxClass = 'rounded-2xl sm:rounded-3xl overflow-hidden shadow-md';
                    } else {
                        $promoWrapClass = 'w-full my-6 sm:my-10 overflow-hidden';
                        $promoBoxClass = 'w-full overflow-hidden';
                    }
                @endphp
                <section id="promo-banner" class="{{ $promoWrapClass }}">
                    <div class="relative w-full {{ $promoBoxClass }} group">
                        @if (!empty($store['promo_banner_link']))
                            <a href="{{ $store['promo_banner_link'] }}" class="block w-full cursor-pointer">
                                <img
                                    src="{{ $promoImg }}"
                                    alt="{{ $store['promo_banner_title'] ?? 'Special Promotion' }}"
                                    class="w-full h-auto block object-cover max-h-[550px] transition-transform duration-500 group-hover:scale-[1.01]"
                                    loading="lazy"
                                >
                            </a>
                        @else
                            <img
                                src="{{ $promoImg }}"
                                alt="{{ $store['promo_banner_title'] ?? 'Special Promotion' }}"
                                class="w-full h-auto block object-cover max-h-[550px]"
                                loading="lazy"
                            >
                        @endif
                    </div>
                </section>
            @endif
        @endif
    @endforeach
</div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // 1. Dynamic Section Category & Subcategory Tabs Filter (Isolated per section)
                document.addEventListener('click', function (e) {
                    const tabBtn = e.target.closest('.sec-tab-btn');
                    if (!tabBtn) return;

                    const secId = tabBtn.getAttribute('data-section');
                    const tabType = tabBtn.getAttribute('data-tab-type') || 'all';
                    const targetId = tabBtn.getAttribute('data-target-id') || '';
                    const sectionEl = tabBtn.closest('.dynamic-product-section');
                    if (!sectionEl) return;

                    // Toggle active classes on tab buttons of this section
                    const allTabs = sectionEl.querySelectorAll('.sec-tab-btn');
                    allTabs.forEach(b => {
                        b.classList.remove('active', 'bg-[#013F1E]', 'text-white', 'shadow-2xs', 'font-bold');
                        b.classList.add('bg-[#E2E8E4]', 'text-[#2D1B13]', 'font-medium');
                    });
                    tabBtn.classList.add('active', 'bg-[#013F1E]', 'text-white', 'shadow-2xs', 'font-bold');
                    tabBtn.classList.remove('bg-[#E2E8E4]', 'text-[#2D1B13]', 'font-medium');

                    // Filter product items in this section
                    const productItems = sectionEl.querySelectorAll('.sec-product-item');
                    const emptyMsg = sectionEl.querySelector('[data-section-empty]');
                    const limit = parseInt(sectionEl.getAttribute('data-product-limit') || '8', 10);
                    let matchedCount = 0;

                    productItems.forEach(item => {
                        const itemCat = String(item.getAttribute('data-category') || '');
                        const itemSubcat = String(item.getAttribute('data-subcategory') || '');
                        const itemChildcat = String(item.getAttribute('data-childcategory') || '');
                        let isMatch = false;

                        if (tabType === 'all' || !targetId) {
                            isMatch = true;
                        } else if (tabType === 'category') {
                            isMatch = (itemCat === String(targetId));
                        } else if (tabType === 'subcategory') {
                            isMatch = (itemSubcat === String(targetId));
                        } else if (tabType === 'childcategory') {
                            isMatch = (itemChildcat === String(targetId));
                        }

                        if (isMatch) {
                            matchedCount++;
                            if (matchedCount <= limit) {
                                item.classList.remove('hidden');
                            } else {
                                item.classList.add('hidden');
                            }
                        } else {
                            item.classList.add('hidden');
                        }
                    });

                    if (emptyMsg) {
                        if (matchedCount === 0) {
                            emptyMsg.classList.remove('hidden');
                        } else {
                            emptyMsg.classList.add('hidden');
                        }
                    }
                });

                // Legacy Sera Bikri Category Tab Filter
                const tabBtns = document.querySelectorAll('.sera-tab-btn');
                const productItems = document.querySelectorAll('.sera-product-item');

                if (tabBtns.length > 0 && productItems.length > 0) {
                    tabBtns.forEach(btn => {
                        btn.addEventListener('click', function () {
                            const filter = this.getAttribute('data-filter');

                            tabBtns.forEach(b => {
                                b.classList.remove('active', 'bg-[#013F1E]', 'text-white', 'shadow-2xs');
                                b.classList.add('bg-[#E2E8E4]', 'text-[#2D1B13]');
                            });
                            this.classList.add('active', 'bg-[#013F1E]', 'text-white', 'shadow-2xs');
                            this.classList.remove('bg-[#E2E8E4]', 'text-[#2D1B13]');

                            productItems.forEach(item => {
                                const itemCat = item.getAttribute('data-category');
                                if (filter === 'all' || itemCat === filter) {
                                    item.classList.remove('hidden');
                                } else {
                                    item.classList.add('hidden');
                                }
                            });
                        });
                    });
                }

                // 2. FAQ Chat Simulator
                const faqList = @json($faqItems);
                const userBubble = document.getElementById('faq-user-bubble');
                const supportBubble = document.getElementById('faq-support-bubble');
                const supportRow = document.getElementById('faq-support-row');
                const typingIndicator = document.getElementById('faq-typing-indicator');
                const chips = document.querySelectorAll('.faq-chip');
                const waForm = document.getElementById('faq-whatsapp-form');
                const customInput = document.getElementById('faq-custom-input');
                const waNumber = '{{ $waNumber ?? "8801700000000" }}';

                chips.forEach(chip => {
                    chip.addEventListener('click', () => {
                        const idx = parseInt(chip.dataset.faqIndex, 10);
                        const item = faqList[idx];
                        if (!item) return;

                        chips.forEach(c => {
                            c.classList.remove('active', 'bg-[#013F1E]', 'text-white', 'font-bold');
                            c.classList.add('bg-[#F1F5F2]', 'text-[#0C2417]', 'font-semibold');
                        });
                        chip.classList.add('active', 'bg-[#013F1E]', 'text-white', 'font-bold');
                        chip.classList.remove('bg-[#F1F5F2]', 'text-[#0C2417]', 'font-semibold');

                        if (userBubble) {
                            userBubble.textContent = item.q;
                        }

                        if (supportRow && typingIndicator) {
                            supportRow.classList.add('hidden');
                            typingIndicator.classList.remove('hidden');
                            typingIndicator.classList.add('flex');

                            setTimeout(() => {
                                typingIndicator.classList.remove('flex');
                                typingIndicator.classList.add('hidden');
                                if (supportBubble) supportBubble.textContent = item.a;
                                supportRow.classList.remove('hidden');
                            }, 450);
                        }
                    });
                });

                if (waForm && customInput) {
                    waForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        const query = customInput.value.trim();
                        const prefix = @json($store['faq_whatsapp_prefix'] ?? 'আমি Foodness ওয়েবসাইট থেকে জানতে চাইছি:', JSON_UNESCAPED_UNICODE);
                        const fullMsg = query ? (prefix + ' ' + query) : prefix;
                        const msg = encodeURIComponent(fullMsg);
                        window.open(`https://wa.me/${waNumber}?text=${msg}`, '_blank');
                    });
                }

                // 3. Story Reel Video Play / Sound Controls
                const reelVid = document.getElementById('story-reel-video');
                const soundBtn = document.getElementById('story-reel-sound-toggle');
                const playBtn = document.getElementById('story-reel-play-toggle');
                const iconMuted = document.getElementById('story-reel-icon-muted');
                const iconUnmuted = document.getElementById('story-reel-icon-unmuted');
                const iconPause = document.getElementById('story-reel-icon-pause');
                const iconPlay = document.getElementById('story-reel-icon-play');

                if (reelVid && soundBtn) {
                    soundBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        reelVid.muted = !reelVid.muted;
                        if (reelVid.muted) {
                            iconMuted?.classList.remove('hidden');
                            iconUnmuted?.classList.add('hidden');
                        } else {
                            iconMuted?.classList.add('hidden');
                            iconUnmuted?.classList.remove('hidden');
                        }
                    });
                }

                if (reelVid && playBtn) {
                    playBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (reelVid.paused) {
                            reelVid.play();
                            iconPause?.classList.remove('hidden');
                            iconPlay?.classList.add('hidden');
                        } else {
                            reelVid.pause();
                            iconPause?.classList.add('hidden');
                            iconPlay?.classList.remove('hidden');
                        }
                    });
                }

                (function () {
                    const slider = document.getElementById('hero');
                    if (!slider) return;
                    const slides = [...slider.querySelectorAll('.hero-slide')];
                    const dots = [...slider.querySelectorAll('.carousel-dots button')];
                    if (slides.length <= 1) return;
                    let activeSlide = 0;
                    let carouselTimer;
                    const showSlide = (index) => {
                        activeSlide = (index + slides.length) % slides.length;
                        slides.forEach((slide, slideIndex) => slide.classList.toggle('active', slideIndex === activeSlide));
                        dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === activeSlide));
                    };
                    const startCarousel = () => {
                        clearInterval(carouselTimer);
                        const delay = parseInt(slider.getAttribute('data-autoplay'), 10) || 4500;
                        if (delay > 0) carouselTimer = setInterval(() => showSlide(activeSlide + 1), delay);
                    };
                    slider.querySelector('.carousel-arrow--prev')?.addEventListener('click', () => { showSlide(activeSlide - 1); startCarousel(); });
                    slider.querySelector('.carousel-arrow--next')?.addEventListener('click', () => { showSlide(activeSlide + 1); startCarousel(); });
                    dots.forEach((dot, index) => dot.addEventListener('click', () => { showSlide(index); startCarousel(); }));
                    let touchStartX = 0;
                    slider.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
                    slider.addEventListener('touchend', (e) => {
                        const diff = touchStartX - e.changedTouches[0].screenX;
                        if (Math.abs(diff) > 40) showSlide(activeSlide + (diff > 0 ? 1 : -1));
                        startCarousel();
                    }, { passive: true });
                    startCarousel();
                })();
            });
        </script>
    @endpush
@endsection
