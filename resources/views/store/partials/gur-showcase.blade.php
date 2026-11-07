@php
    use Illuminate\Support\Facades\Storage;

    $badgeText = $store['gur_badge_text'] ?? 'Foodness™ অরিজিনাল';
    $title = $store['gur_title'] ?? 'খেজুরের';
    $titleHighlight = $store['gur_title_highlight'] ?? 'খাঁটি গুড়';
    $subtitle = $store['gur_subtitle'] ?? 'শীতের ভোরের রস, মাটির চুলায় জ্বাল।';
    $btnText = $store['gur_btn_text'] ?? 'অর্ডার করুন';
    $btnUrl = $store['gur_btn_url'] ?? route('shop.index');
    $autoplay = ($store['gur_autoplay'] ?? '1') === '1';
    $cardDuration = (float) ($store['gur_card_duration'] ?? '4.5');

    // Card 1
    $c1Time = $store['gur_card_1_time'] ?? 'ভোর ৬টা';
    $c1Title = $store['gur_card_1_title'] ?? 'রস নামানো';
    $c1Desc = $store['gur_card_1_desc'] ?? 'শীতের কুয়াশায়, গাছের ডগা থেকে রস নামানো হয় সূর্য ওঠার আগেই। ভোরের হিমেল হাওয়ায় সংগৃহীত এই কাঁচা রসই খাঁটি গুড়ের মূল রহস্য।';
    $c1RawVideo = $store['gur_card_1_video'] ?? null;
    $c1Video = !empty($c1RawVideo) && (Storage::disk('public')->exists($c1RawVideo) || file_exists(public_path('storage/'.$c1RawVideo)))
        ? asset('storage/'.$c1RawVideo)
        : ($store['gur_card_1_video_url'] ?? 'https://assets.mixkit.co/videos/preview/mixkit-morning-fog-in-a-forest-of-pines-42742-large.mp4');
    $c1RawImage = $store['gur_card_1_image'] ?? null;
    $c1Poster = !empty($c1RawImage) && (Storage::disk('public')->exists($c1RawImage) || file_exists(public_path('storage/'.$c1RawImage)))
        ? asset('storage/'.$c1RawImage)
        : \App\Support\PlaceholderImage::bannerUrl();
    $c1BtnUrl = $btnUrl;

    // Dynamic Product Resolution (Card 2, 3, 4)
    $c2Pid = $store['gur_card_2_product_id'] ?? null;
    $c3Pid = $store['gur_card_3_product_id'] ?? null;
    $c4Pid = $store['gur_card_4_product_id'] ?? null;

    $linkedIds = array_values(array_filter([$c2Pid, $c3Pid, $c4Pid]));
    $linkedProducts = !empty($linkedIds)
        ? \App\Models\Product::with(['images', 'variants'])->whereIn('id', $linkedIds)->get()->keyBy('id')
        : collect();

    $p2 = $c2Pid ? $linkedProducts->get($c2Pid) : null;
    $p3 = $c3Pid ? $linkedProducts->get($c3Pid) : null;
    $p4 = $c4Pid ? $linkedProducts->get($c4Pid) : null;

    // Card 2 (Jhola Gur)
    $c2Time = $store['gur_card_2_time'] ?? 'সকাল ৮টা';
    $c2Title = !empty($store['gur_card_2_title']) ? $store['gur_card_2_title'] : ($p2?->name ?? 'ঝোলা গুড়');
    $c2Desc = !empty($store['gur_card_2_desc']) ? $store['gur_card_2_desc'] : ($p2?->short_description ?: ($p2 ? \Illuminate\Support\Str::limit(strip_tags($p2->description), 90) : 'রুটি, মুড়ি আর পায়েসে এক চামচ।'));
    $c2Price = !empty($store['gur_card_2_price']) ? $store['gur_card_2_price'] : ($p2 ? \App\Support\Money::formatBn($p2->price) : '৳৩৮০');
    $c2BtnText = !empty($store['gur_card_2_btn_text']) ? $store['gur_card_2_btn_text'] : ($p2 ? $p2->name.' দেখুন' : 'খেজুরের ঝোলা গুড় দেখুন');
    $c2BtnUrl = !empty($store['gur_card_2_btn_url']) ? $store['gur_card_2_btn_url'] : ($p2 ? route('product.show', $p2) : route('shop.index'));
    $c2RawImage = $store['gur_card_2_image'] ?? null;
    $c2Image = !empty($c2RawImage) && (Storage::disk('public')->exists($c2RawImage) || file_exists(public_path('storage/'.$c2RawImage)))
        ? asset('storage/'.$c2RawImage)
        : ($p2?->primaryImagePath() && (Storage::disk('public')->exists($p2->primaryImagePath()) || file_exists(public_path('storage/'.$p2->primaryImagePath())))
            ? asset('storage/'.$p2->primaryImagePath())
            : \App\Support\PlaceholderImage::url());

    // Card 3 (Bij Gur)
    $c3Time = $store['gur_card_3_time'] ?? 'সকাল ১০টা';
    $c3Title = !empty($store['gur_card_3_title']) ? $store['gur_card_3_title'] : ($p3?->name ?? 'বীজ গুড়');
    $c3Desc = !empty($store['gur_card_3_desc']) ? $store['gur_card_3_desc'] : ($p3?->short_description ?: ($p3 ? \Illuminate\Support\Str::limit(strip_tags($p3->description), 90) : 'দানা দানা, চকলেটের মতো ঘন।'));
    $c3Price = !empty($store['gur_card_3_price']) ? $store['gur_card_3_price'] : ($p3 ? \App\Support\Money::formatBn($p3->price) : '৳৫৫০');
    $c3BtnText = !empty($store['gur_card_3_btn_text']) ? $store['gur_card_3_btn_text'] : ($p3 ? $p3->name.' দেখুন' : 'চকলেট বীজ গুড় দেখুন');
    $c3BtnUrl = !empty($store['gur_card_3_btn_url']) ? $store['gur_card_3_btn_url'] : ($p3 ? route('product.show', $p3) : route('shop.index'));
    $c3RawImage = $store['gur_card_3_image'] ?? null;
    $c3Image = !empty($c3RawImage) && (Storage::disk('public')->exists($c3RawImage) || file_exists(public_path('storage/'.$c3RawImage)))
        ? asset('storage/'.$c3RawImage)
        : ($p3?->primaryImagePath() && (Storage::disk('public')->exists($p3->primaryImagePath()) || file_exists(public_path('storage/'.$p3->primaryImagePath())))
            ? asset('storage/'.$p3->primaryImagePath())
            : \App\Support\PlaceholderImage::url());

    // Card 4 (Patali Gur)
    $c4Time = $store['gur_card_4_time'] ?? 'দুপুর ১২টা';
    $c4Title = !empty($store['gur_card_4_title']) ? $store['gur_card_4_title'] : ($p4?->name ?? 'পাটালি');
    $c4Desc = !empty($store['gur_card_4_desc']) ? $store['gur_card_4_desc'] : ($p4?->short_description ?: ($p4 ? \Illuminate\Support\Str::limit(strip_tags($p4->description), 90) : 'জমে শক্ত, ভাঙলে ঘ্রাণে ভরপুর।'));
    $c4Price = !empty($store['gur_card_4_price']) ? $store['gur_card_4_price'] : ($p4 ? \App\Support\Money::formatBn($p4->price) : '৳৩৫০');
    $c4BtnText = !empty($store['gur_card_4_btn_text']) ? $store['gur_card_4_btn_text'] : ($p4 ? $p4->name.' দেখুন' : 'খেজুরের পাটালি গুড় দেখুন');
    $c4BtnUrl = !empty($store['gur_card_4_btn_url']) ? $store['gur_card_4_btn_url'] : ($p4 ? route('product.show', $p4) : route('shop.index'));
    $c4RawImage = $store['gur_card_4_image'] ?? null;
    $c4Image = !empty($c4RawImage) && (Storage::disk('public')->exists($c4RawImage) || file_exists(public_path('storage/'.$c4RawImage)))
        ? asset('storage/'.$c4RawImage)
        : ($p4?->primaryImagePath() && (Storage::disk('public')->exists($p4->primaryImagePath()) || file_exists(public_path('storage/'.$p4->primaryImagePath())))
            ? asset('storage/'.$p4->primaryImagePath())
            : \App\Support\PlaceholderImage::url());
@endphp

<style>
    #gur-accordion-container {
        height: 480px;
        max-height: 480px;
        overflow: hidden;
    }
    @media (min-width: 640px) {
        #gur-accordion-container {
            height: 500px;
            max-height: 500px;
        }
    }
    @media (min-width: 1024px) {
        #gur-accordion-container {
            height: 500px;
            max-height: 500px;
        }
    }
    /* Smooth Flex Transition for Accordion Effect */
    .gur-accordion-card {
        transition: flex 0.8s cubic-bezier(0.25, 1, 0.5, 1), background-color 0.5s ease;
        height: 100% !important;
        max-height: 100% !important;
        min-height: 0 !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
    }
    .gur-expanded-details {
        transition: opacity 0.4s ease 0.1s, transform 0.4s ease 0.1s, max-height 0.4s ease;
    }
    .gur-product-img-wrapper {
        transition: all 0.7s cubic-bezier(0.25, 1, 0.5, 1);
        min-height: 0 !important;
    }
    @keyframes gurFadeInMobile {
        from { opacity: 0.4; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .gur-mobile-card-fade {
        animation: gurFadeInMobile 0.4s ease-out forwards;
    }
</style>

<section id="foodness-gur-section" class="store-container pt-2 sm:pt-3 pb-8 sm:pb-12" data-autoplay="{{ $autoplay ? '1' : '0' }}" data-duration="{{ $cardDuration }}">
    <div class="w-full px-4 sm:px-7 md:px-9 pt-4 sm:pt-5 pb-5 sm:pb-8 relative bg-[#F9FBF9] shadow-xs rounded-xl overflow-hidden">
        
        {{-- Top Brand Badge --}}
        @if (!empty($badgeText))
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#013F1E]/5 border border-[#013F1E]/15 mb-4">
                <span class="w-2 h-2 rounded-full bg-[#A5000B] animate-pulse"></span>
                <span class="text-xs font-bold tracking-wider uppercase text-[#013F1E]">
                    {{ $badgeText }}
                </span>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 lg:mb-8 gap-5">
            <div>
                <h2 class="font-serif text-3xl sm:text-5xl md:text-6xl font-black text-[#013F1E] leading-[1.15] tracking-tight">
                    {{ $title }}<br><span class="text-[#A5000B]">{{ $titleHighlight }}</span>
                </h2>
                @if (!empty($subtitle))
                    <p class="text-[#3A5F47] text-sm sm:text-base mt-3 font-medium">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
            
            {{-- Order Button --}}
            <div>
                @if (!empty($btnUrl) && $btnUrl !== '#')
                    <a
                        href="{{ $btnUrl }}"
                        class="bg-[#A5000B] hover:bg-[#013F1E] text-white px-6 py-3.5 rounded-xl text-sm sm:text-base font-semibold inline-flex items-center gap-3 transition-all duration-300 shadow-sm hover:shadow-md active:scale-95 group"
                    >
                        <span>{{ $btnText }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                @else
                    <button
                        type="button"
                        onclick="showGurOrderModal()"
                        class="bg-[#A5000B] hover:bg-[#013F1E] text-white px-6 py-3.5 rounded-xl text-sm sm:text-base font-semibold inline-flex items-center gap-3 transition-all duration-300 shadow-sm hover:shadow-md active:scale-95 group cursor-pointer"
                    >
                        <span>{{ $btnText }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- Accordion Container --}}
        <div id="gur-accordion-container" class="flex flex-col lg:flex-row gap-3.5 w-full h-[480px] sm:h-[500px] lg:h-[500px] max-h-[480px] sm:max-h-[500px] lg:max-h-[500px] overflow-hidden select-none">

            {{-- CARD 1: Video / Story Card (রস নামানো) --}}
            <div class="gur-accordion-card relative overflow-hidden rounded-none cursor-pointer group flex flex-col justify-between h-full max-h-full min-h-0 box-border select-none" 
                 style="background-color: #FDF8F4;"
                 data-index="0" 
                 data-url="{{ $c1BtnUrl }}"
                 onmouseenter="hoverGurCard(0)"
                 onclick="handleGurCardClick(event, 0)">
                
                {{-- Background Video / Morning Visual --}}
                <video id="gur-story-video" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none" 
                       autoplay muted loop playsinline poster="{{ $c1Poster }}">
                    <source src="{{ $c1Video }}" type="video/mp4">
                </video>
                
                {{-- Soft Dark Gradient Overlay at the Bottom for Text Legibility --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent pointer-events-none"></div>

                {{-- Top Segmented Progress Bars (White Bars over Video) --}}
                <div class="gur-progress-wrapper absolute top-3.5 left-4 right-4 flex gap-2 z-20 transition-opacity duration-300">
                    <div onclick="event.stopPropagation(); selectGurCard(0);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(1);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(2);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(3);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                </div>

                {{-- Spacer to push content down --}}
                <div class="flex-1 pointer-events-none"></div>

                {{-- Bottom Text Content (Overlaid on Video Bottom, exactly as shown in reference) --}}
                <div class="relative z-20 p-5 sm:p-6 text-white flex flex-col justify-end">
                    <span class="text-xs sm:text-sm text-white/90 font-medium tracking-wide drop-shadow-xs">{{ $c1Time }}</span>
                    <h3 class="text-base sm:text-lg lg:text-xl font-bold text-white mt-1 drop-shadow-sm">{{ $c1Title }}</h3>
                    <div class="gur-expanded-details overflow-hidden">
                        <p class="text-xs sm:text-sm text-white/90 mt-1.5 max-w-lg font-normal leading-relaxed drop-shadow-xs line-clamp-3">
                            {{ $c1Desc }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- CARD 2: ঝোলা গুড় (Jhola Gur) --}}
            <div class="gur-accordion-card relative overflow-hidden rounded-none cursor-pointer group flex flex-col justify-between h-full max-h-full min-h-0 box-border select-none" 
                 style="background-color: #1a1412;"
                 data-index="1" 
                 data-url="{{ $c2BtnUrl }}"
                 onmouseenter="hoverGurCard(1)"
                 onclick="handleGurCardClick(event, 1)">
                
                {{-- Full Background Cover Image --}}
                @if ($c2Image)
                    <img src="{{ $c2Image }}" alt="{{ $c2Title }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none">
                @endif
                {{-- Dark Gradient Overlay for Text Readability --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent pointer-events-none"></div>

                {{-- Top Segmented Progress Bars --}}
                <div class="gur-progress-wrapper absolute top-3.5 left-4 right-4 flex gap-2 z-20 opacity-0 transition-opacity duration-300">
                    <div onclick="event.stopPropagation(); selectGurCard(0);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(1);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(2);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(3);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                </div>

                {{-- Spacer to push content down --}}
                <div class="flex-1 pointer-events-none"></div>

                {{-- Bottom Info --}}
                <div class="relative z-20 p-5 sm:p-6 text-white flex flex-col justify-end">
                    <span class="text-xs sm:text-sm text-white/90 font-medium tracking-wide drop-shadow-xs">{{ $c2Time }}</span>
                    <h3 class="text-base sm:text-lg lg:text-xl font-bold text-white mt-1 drop-shadow-sm">{{ $c2Title }}</h3>
                    
                    {{-- Details shown only when active --}}
                    <div class="gur-expanded-details overflow-hidden">
                        <p class="text-xs sm:text-sm text-white/90 mt-1.5 line-clamp-2 drop-shadow-xs">
                            {{ $c2Desc }}
                        </p>
                        <div class="mt-3 pt-2.5 border-t border-white/20 flex flex-wrap items-center gap-4">
                            @if (!empty($c2Price))
                                <span class="text-lg sm:text-xl font-bold text-amber-300 drop-shadow-sm">{{ $c2Price }}</span>
                            @endif
                            <a
                                href="{{ $c2BtnUrl }}"
                                onclick="event.stopPropagation();"
                                class="text-xs sm:text-sm font-semibold text-white underline decoration-amber-400 underline-offset-4 hover:text-amber-300 flex items-center gap-1 transition-colors group/btn"
                            >
                                <span>{{ $c2BtnText }}</span>
                                <span class="transition-transform group-hover/btn:translate-x-1">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 3: বীজ গুড় (Bij Gur) --}}
            <div class="gur-accordion-card relative overflow-hidden rounded-none cursor-pointer group flex flex-col justify-between h-full max-h-full min-h-0 box-border select-none" 
                 style="background-color: #1a1412;"
                 data-index="2" 
                 data-url="{{ $c3BtnUrl }}"
                 onmouseenter="hoverGurCard(2)"
                 onclick="handleGurCardClick(event, 2)">
                
                {{-- Full Background Cover Image --}}
                @if ($c3Image)
                    <img src="{{ $c3Image }}" alt="{{ $c3Title }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none">
                @endif
                {{-- Dark Gradient Overlay for Text Readability --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent pointer-events-none"></div>

                {{-- Top Segmented Progress Bars --}}
                <div class="gur-progress-wrapper absolute top-3.5 left-4 right-4 flex gap-2 z-20 opacity-0 transition-opacity duration-300">
                    <div onclick="event.stopPropagation(); selectGurCard(0);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(1);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(2);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(3);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                </div>

                {{-- Spacer to push content down --}}
                <div class="flex-1 pointer-events-none"></div>

                {{-- Bottom Info --}}
                <div class="relative z-20 p-5 sm:p-6 text-white flex flex-col justify-end">
                    <span class="text-xs sm:text-sm text-white/90 font-medium tracking-wide drop-shadow-xs">{{ $c3Time }}</span>
                    <h3 class="text-base sm:text-lg lg:text-xl font-bold text-white mt-1 drop-shadow-sm">{{ $c3Title }}</h3>
                    
                    <div class="gur-expanded-details overflow-hidden">
                        <p class="text-xs sm:text-sm text-white/90 mt-1.5 line-clamp-2 drop-shadow-xs">
                            {{ $c3Desc }}
                        </p>
                        <div class="mt-3 pt-2.5 border-t border-white/20 flex flex-wrap items-center gap-4">
                            @if (!empty($c3Price))
                                <span class="text-lg sm:text-xl font-bold text-amber-300 drop-shadow-sm">{{ $c3Price }}</span>
                            @endif
                            <a
                                href="{{ $c3BtnUrl }}"
                                onclick="event.stopPropagation();"
                                class="text-xs sm:text-sm font-semibold text-white underline decoration-amber-400 underline-offset-4 hover:text-amber-300 flex items-center gap-1 transition-colors group/btn"
                            >
                                <span>{{ $c3BtnText }}</span>
                                <span class="transition-transform group-hover/btn:translate-x-1">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 4: পাটালি (Patali Gur) --}}
            <div class="gur-accordion-card relative overflow-hidden rounded-none cursor-pointer group flex flex-col justify-between h-full max-h-full min-h-0 box-border select-none" 
                 style="background-color: #1a1412;"
                 data-index="3" 
                 data-url="{{ $c4BtnUrl }}"
                 onmouseenter="hoverGurCard(3)"
                 onclick="handleGurCardClick(event, 3)">
                
                {{-- Full Background Cover Image --}}
                @if ($c4Image)
                    <img src="{{ $c4Image }}" alt="{{ $c4Title }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none">
                @endif
                {{-- Dark Gradient Overlay for Text Readability --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent pointer-events-none"></div>

                {{-- Top Segmented Progress Bars --}}
                <div class="gur-progress-wrapper absolute top-3.5 left-4 right-4 flex gap-2 z-20 opacity-0 transition-opacity duration-300">
                    <div onclick="event.stopPropagation(); selectGurCard(0);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(1);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(2);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                    <div onclick="event.stopPropagation(); selectGurCard(3);" class="flex-1 overflow-hidden rounded-full cursor-pointer" style="height: 4px; background-color: rgba(255,255,255,0.4);">
                        <div class="gur-bar-fill h-full w-0 rounded-full" style="background-color: #ffffff;"></div>
                    </div>
                </div>

                {{-- Spacer to push content down --}}
                <div class="flex-1 pointer-events-none"></div>

                {{-- Bottom Info --}}
                <div class="relative z-20 p-5 sm:p-6 text-white flex flex-col justify-end">
                    <span class="text-xs sm:text-sm text-white/90 font-medium tracking-wide drop-shadow-xs">{{ $c4Time }}</span>
                    <h3 class="text-base sm:text-lg lg:text-xl font-bold text-white mt-1 drop-shadow-sm">{{ $c4Title }}</h3>
                    
                    <div class="gur-expanded-details overflow-hidden">
                        <p class="text-xs sm:text-sm text-white/90 mt-1.5 line-clamp-2 drop-shadow-xs">
                            {{ $c4Desc }}
                        </p>
                        <div class="mt-3 pt-2.5 border-t border-white/20 flex flex-wrap items-center gap-4">
                            @if (!empty($c4Price))
                                <span class="text-lg sm:text-xl font-bold text-amber-300 drop-shadow-sm">{{ $c4Price }}</span>
                            @endif
                            <a
                                href="{{ $c4BtnUrl }}"
                                onclick="event.stopPropagation();"
                                class="text-xs sm:text-sm font-semibold text-white underline decoration-amber-400 underline-offset-4 hover:text-amber-300 flex items-center gap-1 transition-colors group/btn"
                            >
                                <span>{{ $c4BtnText }}</span>
                                <span class="transition-transform group-hover/btn:translate-x-1">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Custom Modal for Gur Order Preview --}}
<div id="gur-order-modal" class="fixed inset-0 bg-[#013F1E]/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-opacity duration-300">
    <div class="bg-[#F9FBF9] border border-[#C5D6CB] rounded-2xl max-w-md w-full p-6 sm:p-7 shadow-2xl transform transition-all">
        <div class="flex items-center justify-between mb-3 border-b border-[#013F1E]/10 pb-3">
            <div class="text-xs font-black tracking-tight text-[#013F1E]">
                authenti<span class="text-[#A5000B]">co</span>™ অরিজিনাল
            </div>
            <button type="button" onclick="closeGurModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer leading-none">&times;</button>
        </div>
        <h3 id="gur-modal-title" class="font-serif text-2xl font-black text-[#013F1E]">
            {{ $title }} {{ $titleHighlight }} অর্ডার করুন
        </h3>
        <p id="gur-modal-desc" class="text-xs sm:text-sm text-[#3A5F47] mt-2 leading-relaxed">
            আমাদের ঝোলা গুড় ({{ $c2Price }}), বীজ গুড় ({{ $c3Price }}) এবং পাটালি গুড় ({{ $c4Price }}) এখন টাটকা স্টকে আছে।
        </p>

        {{-- Quick Product Selection List inside Modal --}}
        <div class="mt-5 space-y-2">
            <a href="{{ $c2BtnUrl }}" class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 bg-white hover:border-[#013F1E]/40 hover:bg-[#FDF8F4] transition group">
                <div class="flex items-center gap-2.5">
                    <span class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-sm">🍯</span>
                    <span class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-[#013F1E]">{{ $c2Title }}</span>
                </div>
                <span class="text-xs font-bold text-[#A5000B]">{{ $c2Price }} &rarr;</span>
            </a>
            <a href="{{ $c3BtnUrl }}" class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 bg-white hover:border-[#013F1E]/40 hover:bg-[#FDF8F4] transition group">
                <div class="flex items-center gap-2.5">
                    <span class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-sm">🟤</span>
                    <span class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-[#013F1E]">{{ $c3Title }}</span>
                </div>
                <span class="text-xs font-bold text-[#A5000B]">{{ $c3Price }} &rarr;</span>
            </a>
            <a href="{{ $c4BtnUrl }}" class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 bg-white hover:border-[#013F1E]/40 hover:bg-[#FDF8F4] transition group">
                <div class="flex items-center gap-2.5">
                    <span class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-sm">🟫</span>
                    <span class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-[#013F1E]">{{ $c4Title }}</span>
                </div>
                <span class="text-xs font-bold text-[#A5000B]">{{ $c4Price }} &rarr;</span>
            </a>
        </div>

        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-slate-200/70">
            <button type="button" onclick="closeGurModal()" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#013F1E] hover:bg-[#E4EFE8] transition cursor-pointer">
                বন্ধ করুন
            </button>
            <a href="{{ route('shop.index') }}" class="px-5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-[#A5000B] text-white hover:bg-[#013F1E] transition shadow-xs">
                সকল পণ্য দেখুন &rarr;
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        const section = document.getElementById('foodness-gur-section');
        if (!section) return;

        const container = document.getElementById('gur-accordion-container');
        const cards = section.querySelectorAll('.gur-accordion-card');
        const totalCards = cards.length;
        if (totalCards === 0) return;

        const configAutoplay = section.getAttribute('data-autoplay') === '1';
        const configDuration = parseFloat(section.getAttribute('data-duration')) || 4.5;
        const durationPerCard = configDuration * 1000;

        let activeIndex = 0;
        let elapsedInCard = 0;
        let isPlaying = configAutoplay;
        let lastFrameTime = performance.now();
        let animationFrameId = null;

        function updateProgressBars(progressRatio) {
            cards.forEach(card => {
                const fills = card.querySelectorAll('.gur-bar-fill');
                fills.forEach((fill, segIndex) => {
                    if (segIndex < activeIndex) {
                        fill.style.width = '100%';
                    } else if (segIndex === activeIndex) {
                        const currentPercent = Math.min(100, Math.max(0, progressRatio * 100));
                        fill.style.width = `${currentPercent}%`;
                    } else {
                        fill.style.width = '0%';
                    }
                });
            });
        }

        function updateAccordionUI(index) {
            const isMobile = window.innerWidth < 1024;

            cards.forEach((card, i) => {
                const isActive = (i === index);
                const details = card.querySelector('.gur-expanded-details');
                const progressWrapper = card.querySelector('.gur-progress-wrapper');
                const imgWrapper = card.querySelector('.gur-product-img-wrapper');

                if (isMobile) {
                    if (isActive) {
                        card.style.display = 'flex';
                        card.style.flex = '1 1 100%';
                        card.style.height = '100%';
                        card.style.maxHeight = '100%';
                        card.classList.add('gur-mobile-card-fade');

                        if (details) {
                            details.style.opacity = '1';
                            details.style.maxHeight = '100px';
                            details.style.transform = 'translateY(0)';
                            details.style.pointerEvents = 'auto';
                        }

                        if (progressWrapper) progressWrapper.style.opacity = '1';
                        if (imgWrapper) imgWrapper.style.transform = (i === 0) ? 'none' : 'scale(1.04)';
                    } else {
                        card.style.display = 'none';
                        card.classList.remove('gur-mobile-card-fade');
                        if (progressWrapper) progressWrapper.style.opacity = '0';
                    }
                } else {
                    card.style.display = 'flex';
                    card.classList.remove('gur-mobile-card-fade');
                    card.style.height = '100%';
                    card.style.maxHeight = '100%';

                    if (isActive) {
                        card.style.flex = '3.4 1 0%';
                        if (details) {
                            details.style.opacity = '1';
                            details.style.maxHeight = '100px';
                            details.style.transform = 'translateY(0)';
                            details.style.pointerEvents = 'auto';
                        }
                        if (progressWrapper) progressWrapper.style.opacity = '1';
                        if (imgWrapper) imgWrapper.style.transform = (i === 0) ? 'none' : 'scale(1.05)';
                    } else {
                        card.style.flex = '1 1 0%';
                        if (details) {
                            details.style.opacity = '0';
                            details.style.maxHeight = '0px';
                            details.style.transform = 'translateY(8px)';
                            details.style.pointerEvents = 'none';
                        }
                        if (progressWrapper) progressWrapper.style.opacity = '0';
                        if (imgWrapper) imgWrapper.style.transform = (i === 0) ? 'none' : 'scale(0.95)';
                    }
                }
            });

            updateProgressBars(0);
        }

        window.addEventListener('resize', () => {
            updateAccordionUI(activeIndex);
        });

        function animateLoop(now) {
            const delta = now - lastFrameTime;
            lastFrameTime = now;

            if (isPlaying) {
                elapsedInCard += delta;
                let ratio = elapsedInCard / durationPerCard;

                if (ratio >= 1) {
                    elapsedInCard = 0;
                    activeIndex = (activeIndex + 1) % totalCards;
                    updateAccordionUI(activeIndex);
                    ratio = 0;
                }

                updateProgressBars(ratio);
            }

            animationFrameId = requestAnimationFrame(animateLoop);
        }

        // Card Selection (Direct)
        window.selectGurCard = function (index) {
            activeIndex = index;
            elapsedInCard = 0;
            updateAccordionUI(activeIndex);
            updateProgressBars(0);
        };

        // Desktop Hover triggers card expansion
        window.hoverGurCard = function (index) {
            if (window.innerWidth < 1024) return;
            if (activeIndex !== index) {
                activeIndex = index;
                elapsedInCard = 0;
                updateAccordionUI(activeIndex);
                updateProgressBars(0);
            }
        };

        // Card Click Navigation: Goes to product page or modal
        window.handleGurCardClick = function (event, index) {
            // If user clicked a direct inner anchor tag, allow native navigation
            if (event && event.target && event.target.closest('a') && event.target.closest('a').getAttribute('href') !== '#') {
                return;
            }

            const card = cards[index];
            if (!card) return;
            const url = card.getAttribute('data-url');

            if (url && url !== '#' && url !== 'javascript:void(0)') {
                window.location.href = url;
            } else {
                window.showGurOrderModal();
            }
        };

        window.toggleGurAutoPlay = function () {
            isPlaying = !isPlaying;
            const btn = document.getElementById('gur-toggle-play');
            if (btn) {
                btn.textContent = isPlaying ? 'অটো-প্লে থামান (Pause)' : 'অটো-প্লে চালু করুন (Play)';
            }
        };

        window.showGurOrderModal = function () {
            const modal = document.getElementById('gur-order-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeGurModal = function () {
            const modal = document.getElementById('gur-order-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        // Initialize state
        updateAccordionUI(activeIndex);
        lastFrameTime = performance.now();
        animationFrameId = requestAnimationFrame(animateLoop);
    })();
</script>
