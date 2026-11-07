@php
    $stories = $stories ?? collect();
    $storiesCount = $stories->count();
    $showStories = ($store['home_show_stories'] ?? '1') === '1';
    $storeName = $store['store_name'] ?? 'Foodness';
    $storeLogo = !empty($store['store_logo']) && file_exists(public_path('storage/'.$store['store_logo']))
        ? asset('storage/'.$store['store_logo'])
        : \App\Support\PlaceholderImage::url();

    $storiesJson = $stories->map(fn ($s) => [
        'id' => $s->id,
        'title' => $s->title,
        'tag' => $s->tag ?: 'JUST LANDED',
        'media_type' => $s->media_type,
        'is_youtube' => $s->isYoutube(),
        'youtube_id' => $s->youtubeId(),
        'media_url' => $s->mediaUrl(),
        'thumbnail_url' => $s->thumbnailUrl(),
        'link_url' => $s->link_url ?: route('shop.index'),
        'link_text' => $s->link_text ?: 'SHOP NOW',
        'duration' => (int) ($s->duration ?: ($s->isVideo() ? 15 : 6)),
    ])->values();
@endphp

@if ($showStories && $storiesCount > 0)
    <div class="fn-stories">
        <div class="fn-stories-head">
            <h2>{{ $store['stories_section_title'] ?? 'Stories' }}</h2>
        </div>

        <div class="fn-stories-rail">
            <div
                id="story-cards-carousel"
                class="fn-stories-track"
                role="list"
                aria-label="Story Cards"
            >
                @foreach ($stories as $index => $story)
                    <div
                        class="story-card-item fn-story-card"
                        data-story-index="{{ $index }}"
                        role="button"
                        tabindex="0"
                        aria-label="Story: {{ $story->title }}"
                    >
                        @if ($story->hasImageThumbnail())
                            <img src="{{ $story->thumbnailUrl() }}" alt="" class="fn-story-card__media" loading="lazy">
                        @elseif ($story->isVideo() && ! $story->isYoutube())
                            <video src="{{ $story->mediaUrl() }}#t=0.5" preload="metadata" muted playsinline class="fn-story-card__media"></video>
                        @else
                            <img src="{{ $story->thumbnailUrl() }}" alt="" class="fn-story-card__media" loading="lazy">
                        @endif
                        <span class="fn-story-card__avatar">
                            <img src="{{ $storeLogo }}" alt="">
                        </span>
                        <span class="fn-story-card__name">{{ $story->title }}</span>
                    </div>
                @endforeach
            </div>

            <button type="button" id="story-carousel-prev" class="fn-stories-arrow fn-stories-arrow--prev" aria-label="Previous Stories">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" /></svg>
            </button>
            <button type="button" id="story-carousel-next" class="fn-stories-arrow fn-stories-arrow--next" aria-label="Next Stories">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" /></svg>
            </button>
        </div>
    </div>

    {{-- Story Modal Viewer (Matching Screenshot 2) --}}
    <div
        id="story-modal"
        class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md hidden flex items-center justify-center p-0 sm:p-4 select-none"
        role="dialog"
        aria-modal="true"
        aria-label="Story Viewer"
    >
        {{-- Floating Prev Arrow Button on Left (Matching Screenshot 2) --}}
        <button
            type="button"
            id="story-btn-prev"
            class="hidden sm:flex absolute left-4 lg:left-12 xl:left-24 top-1/2 -translate-y-1/2 z-40 h-11 w-11 lg:h-12 lg:w-12 items-center justify-center rounded-full bg-white text-slate-900 shadow-2xl hover:scale-110 active:scale-95 transition cursor-pointer"
            aria-label="Previous story"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </button>

        {{-- Floating Next Arrow Button on Right (Matching Screenshot 2) --}}
        <button
            type="button"
            id="story-btn-next"
            class="hidden sm:flex absolute right-4 lg:right-12 xl:right-24 top-1/2 -translate-y-1/2 z-40 h-11 w-11 lg:h-12 lg:w-12 items-center justify-center rounded-full bg-white text-slate-900 shadow-2xl hover:scale-110 active:scale-95 transition cursor-pointer"
            aria-label="Next story"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </button>

        {{-- Central Story Card: Fixed Portrait 9:16 Dimension (Matching Screenshot 2) --}}
        <div id="story-container" class="relative w-full h-full sm:h-[92vh] sm:max-h-[760px] sm:max-w-[420px] sm:rounded-2xl overflow-hidden bg-black shadow-2xl flex flex-col justify-between">
            {{-- Media Viewport --}}
            <div class="relative w-full h-full flex items-center justify-center overflow-hidden" id="story-media-box">
                <img id="story-image" src="" alt="Story Media" class="hidden w-full h-full object-cover pointer-events-none">
                <video id="story-video" src="" playsinline webkit-playsinline class="hidden w-full h-full object-cover pointer-events-none" preload="metadata"></video>
                <iframe id="story-youtube" src="" class="hidden w-full h-full border-0 pointer-events-auto" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>

            {{-- Top Overlay: Progress Bars + Header (Matching Screenshot 2) --}}
            <div class="absolute top-0 inset-x-0 z-30 p-3 sm:p-4 bg-gradient-to-b from-black/85 via-black/40 to-transparent">
                {{-- Segmented Progress Bars --}}
                <div class="flex items-center gap-1 w-full mb-3" id="story-progress-bars">
                    @foreach ($stories as $index => $story)
                        <div class="flex-1 h-1 bg-white/35 rounded-full overflow-hidden backdrop-blur-xs">
                            <div class="story-progress-bar h-full bg-white rounded-full transition-all ease-linear" data-bar-index="{{ $index }}" style="width: 0%;"></div>
                        </div>
                    @endforeach
                </div>

                {{-- Header: Circular Logo + Title (NEW IN) + Close Button (X) --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="h-7 w-7 sm:h-8 sm:w-8 rounded-full overflow-hidden bg-white p-0.5 shrink-0 ring-1 ring-white/60">
                            <img id="story-header-avatar" src="{{ $storeLogo }}" alt="Logo" class="h-full w-full rounded-full object-cover">
                        </div>
                        <span id="story-header-title" class="text-xs sm:text-sm font-black text-white tracking-widest uppercase truncate drop-shadow"></span>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        {{-- Sound Toggle for Video --}}
                        <button
                            type="button"
                            id="story-sound-toggle"
                            class="hidden h-7 w-7 items-center justify-center rounded-full bg-black/40 hover:bg-black/60 text-white backdrop-blur-xs transition"
                            aria-label="Toggle Sound"
                        >
                            <svg id="icon-muted" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-3l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72H4.5a2.25 2.25 0 01-2.25-2.25v-3a2.25 2.25 0 012.25-2.25h2.25z" />
                            </svg>
                            <svg id="icon-unmuted" class="hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72H4.5a2.25 2.25 0 01-2.25-2.25v-3a2.25 2.25 0 012.25-2.25h2.25z" />
                            </svg>
                        </button>

                        {{-- Close (X) Icon (Matching Screenshot 2) --}}
                        <button
                            type="button"
                            id="story-close-btn"
                            class="h-7 w-7 flex items-center justify-center text-white/90 hover:text-white transition cursor-pointer"
                            aria-label="Close Story"
                        >
                            <svg class="h-6 w-6 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tap Touch Zones (Left 35% = Prev, Right 65% = Next) --}}
            <div class="absolute inset-0 z-20 flex" id="story-tap-zones">
                <div class="w-1/3 h-full cursor-pointer" id="zone-prev" aria-label="Tap to go to previous story"></div>
                <div class="w-2/3 h-full cursor-pointer" id="zone-next" aria-label="Tap to go to next story"></div>
            </div>

            {{-- Bottom Overlay (Matching Screenshot 2): Tag + Headline + Wide White Button --}}
            <div class="absolute bottom-0 inset-x-0 z-30 p-5 sm:p-6 bg-gradient-to-t from-black/95 via-black/45 to-transparent flex flex-col items-start pointer-events-none">
                {{-- Tag (e.g. JUST LANDED) --}}
                <span id="story-tag" class="text-[#FDE8EA] bg-[#A5000B] px-2 py-0.5 rounded-sm font-extrabold text-[10px] sm:text-[11px] tracking-widest uppercase mb-1 drop-shadow">
                    JUST LANDED
                </span>

                {{-- Big Headline (e.g. THE NEW COMFORT EDIT) --}}
                <h3 id="story-headline" class="text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-wide uppercase leading-tight drop-shadow mb-4">
                    THE NEW COMFORT EDIT
                </h3>

                {{-- Wide Solid Button (Matching Brand Palette) --}}
                <a
                    id="story-cta-btn"
                    href="#"
                    class="pointer-events-auto w-full bg-white hover:bg-[#FDF8F4] text-[#013F1E] font-black text-xs sm:text-sm py-3.5 px-6 rounded-xl shadow-2xl text-center tracking-widest uppercase transition block transform active:scale-98"
                >
                    <span id="story-cta-text">SHOP NOW</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Story Carousel & Viewer JavaScript Engine --}}
    <script>
    (function() {
        const storiesData = @json($storiesJson);
        if (!storiesData || storiesData.length === 0) return;

        // Carousel Horizontal Scroll Controls with Infinite Loop & Drag Support
        const carousel = document.getElementById('story-cards-carousel');
        const carouselPrev = document.getElementById('story-carousel-prev');
        const carouselNext = document.getElementById('story-carousel-next');
        const scrollStep = 320;

        if (carouselPrev && carousel) {
            carouselPrev.addEventListener('click', function(e) {
                e.stopPropagation();
                if (carousel.scrollLeft <= 15) {
                    carousel.scrollTo({ left: carousel.scrollWidth, behavior: 'smooth' });
                } else {
                    carousel.scrollBy({ left: -scrollStep, behavior: 'smooth' });
                }
            });
        }

        if (carouselNext && carousel) {
            carouselNext.addEventListener('click', function(e) {
                e.stopPropagation();
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;
                if (carousel.scrollLeft >= maxScroll - 15) {
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    carousel.scrollBy({ left: scrollStep, behavior: 'smooth' });
                }
            });
        }

        function updateStoryArrows() {
            if (!carousel) return;
            const maxScroll = carousel.scrollWidth - carousel.clientWidth;
            const overflow = maxScroll > 8;
            if (carouselPrev) carouselPrev.hidden = !overflow || carousel.scrollLeft <= 8;
            if (carouselNext) carouselNext.hidden = !overflow || carousel.scrollLeft >= maxScroll - 8;
        }

        if (carousel) {
            carousel.addEventListener('scroll', updateStoryArrows, { passive: true });
            window.addEventListener('resize', updateStoryArrows);
            updateStoryArrows();
        }

        // Desktop Mouse Drag to Scroll (without native scrollbar)
        let isDown = false;
        let startX = 0;
        let scrollStartLeft = 0;
        let hasDragged = false;

        if (carousel) {
            carousel.addEventListener('mousedown', function(e) {
                isDown = true;
                hasDragged = false;
                startX = e.pageX - carousel.offsetLeft;
                scrollStartLeft = carousel.scrollLeft;
            });

            window.addEventListener('mouseup', function() {
                isDown = false;
            });

            carousel.addEventListener('mousemove', function(e) {
                if (!isDown) return;
                const x = e.pageX - carousel.offsetLeft;
                const walk = (x - startX) * 1.5;
                if (Math.abs(walk) > 5) {
                    hasDragged = true;
                    e.preventDefault();
                    carousel.scrollLeft = scrollStartLeft - walk;
                }
            });
        }

        // Story Modal Controls
        let currentIndex = 0;
        let isPaused = false;
        let startTime = 0;
        let elapsedBeforePause = 0;
        let currentDuration = 6000;
        let timerFrameId = null;

        const modal = document.getElementById('story-modal');
        const imgEl = document.getElementById('story-image');
        const videoEl = document.getElementById('story-video');
        const ytEl = document.getElementById('story-youtube');
        const headerAvatar = document.getElementById('story-header-avatar');
        const headerTitle = document.getElementById('story-header-title');
        const tagEl = document.getElementById('story-tag');
        const headlineEl = document.getElementById('story-headline');
        const soundToggle = document.getElementById('story-sound-toggle');
        const iconMuted = document.getElementById('icon-muted');
        const iconUnmuted = document.getElementById('icon-unmuted');
        const closeBtn = document.getElementById('story-close-btn');
        const prevBtn = document.getElementById('story-btn-prev');
        const nextBtn = document.getElementById('story-btn-next');
        const zonePrev = document.getElementById('zone-prev');
        const zoneNext = document.getElementById('zone-next');
        const ctaBtn = document.getElementById('story-cta-btn');
        const ctaText = document.getElementById('story-cta-text');
        const progressBars = document.querySelectorAll('.story-progress-bar');
        const cardItems = document.querySelectorAll('.story-card-item');

        // Open Story on Card Click (ignoring clicks during drag)
        cardItems.forEach(card => {
            card.addEventListener('click', function(e) {
                if (hasDragged) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                const idx = parseInt(this.getAttribute('data-story-index'), 10);
                openStory(idx);
            });
        });

        function openStory(index) {
            currentIndex = Math.max(0, Math.min(index, storiesData.length - 1));
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            playStory(currentIndex);
        }

        function closeStory() {
            clearTimer();
            if (!videoEl.paused) {
                videoEl.pause();
            }
            videoEl.src = '';
            imgEl.src = '';
            if (ytEl) {
                ytEl.src = '';
                ytEl.classList.add('hidden');
            }
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function playStory(index) {
            clearTimer();
            currentIndex = index;
            const story = storiesData[index];
            if (!story) return;

            // Update Progress Bars State
            progressBars.forEach((bar, i) => {
                bar.style.transition = 'none';
                if (i < currentIndex) {
                    bar.style.width = '100%';
                } else {
                    bar.style.width = '0%';
                }
            });

            // Update Header & Bottom Details (Matching Screenshot 2)
            headerAvatar.src = story.thumbnail_url;
            headerTitle.textContent = story.title;
            tagEl.textContent = story.tag || 'JUST LANDED';
            headlineEl.textContent = story.title;
            ctaBtn.href = story.link_url;
            ctaText.textContent = story.link_text || 'SHOP NOW';

            isPaused = false;
            elapsedBeforePause = 0;

            if (story.is_youtube && story.youtube_id) {
                imgEl.classList.add('hidden');
                videoEl.classList.add('hidden');
                if (!videoEl.paused) videoEl.pause();
                videoEl.src = '';
                soundToggle.classList.add('hidden');
                soundToggle.classList.remove('flex');

                if (ytEl) {
                    ytEl.classList.remove('hidden');
                    ytEl.src = 'https://www.youtube.com/embed/' + story.youtube_id + '?autoplay=1&mute=1&enablejsapi=1&playsinline=1&rel=0';
                }

                currentDuration = (story.duration || 15) * 1000;
                startTimer();
            } else if (story.media_type === 'video') {
                if (ytEl) {
                    ytEl.classList.add('hidden');
                    ytEl.src = '';
                }
                imgEl.classList.add('hidden');
                videoEl.classList.remove('hidden');
                soundToggle.classList.remove('hidden');
                soundToggle.classList.add('flex');

                videoEl.muted = true;
                updateSoundIcon();

                videoEl.src = story.media_url;
                videoEl.load();

                videoEl.onloadedmetadata = function() {
                    const vidDur = videoEl.duration && !isNaN(videoEl.duration) ? videoEl.duration * 1000 : story.duration * 1000;
                    currentDuration = Math.max(3000, vidDur);
                    videoEl.play().catch(() => {});
                    startTimer();
                };

                videoEl.onerror = function() {
                    currentDuration = (story.duration || 15) * 1000;
                    startTimer();
                };

                videoEl.onended = function() {
                    goToNext();
                };
            } else {
                if (ytEl) {
                    ytEl.classList.add('hidden');
                    ytEl.src = '';
                }
                videoEl.classList.add('hidden');
                if (!videoEl.paused) videoEl.pause();
                soundToggle.classList.add('hidden');
                soundToggle.classList.remove('flex');

                imgEl.classList.remove('hidden', 'opacity-0');
                imgEl.src = story.media_url;

                currentDuration = (story.duration || 6) * 1000;
                startTimer();
            }
        }

        function startTimer() {
            startTime = performance.now() - elapsedBeforePause;
            const currentBar = progressBars[currentIndex];

            function step(now) {
                if (isPaused) return;

                const elapsed = now - startTime;
                const progress = Math.min(1, elapsed / currentDuration);

                if (currentBar) {
                    currentBar.style.width = (progress * 100) + '%';
                }

                if (progress >= 1) {
                    goToNext();
                } else {
                    timerFrameId = requestAnimationFrame(step);
                }
            }

            timerFrameId = requestAnimationFrame(step);
        }

        function clearTimer() {
            if (timerFrameId) {
                cancelAnimationFrame(timerFrameId);
                timerFrameId = null;
            }
        }

        function pauseStory() {
            if (isPaused) return;
            isPaused = true;
            clearTimer();
            elapsedBeforePause = performance.now() - startTime;
            if (storiesData[currentIndex]?.media_type === 'video' && !videoEl.paused) {
                videoEl.pause();
            }
        }

        function resumeStory() {
            if (!isPaused) return;
            isPaused = false;
            if (storiesData[currentIndex]?.media_type === 'video') {
                videoEl.play().catch(() => {});
            }
            startTimer();
        }

        function goToPrev() {
            if (!storiesData || storiesData.length === 0) return;
            const prevIndex = (currentIndex - 1 + storiesData.length) % storiesData.length;
            playStory(prevIndex);
        }

        function goToNext() {
            if (!storiesData || storiesData.length === 0) return;
            const nextIndex = (currentIndex + 1) % storiesData.length;
            playStory(nextIndex);
        }

        function updateSoundIcon() {
            if (videoEl.muted) {
                iconMuted.classList.remove('hidden');
                iconUnmuted.classList.add('hidden');
            } else {
                iconMuted.classList.add('hidden');
                iconUnmuted.classList.remove('hidden');
            }
        }

        // Tap Left / Right Zones
        let holdTimeout = null;
        let isHolding = false;

        function attachHoldAndTap(element, onClick) {
            let startX = 0;
            let startY = 0;
            let hasMoved = false;

            const handleDown = (e) => {
                hasMoved = false;
                if (e.touches && e.touches[0]) {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                }
                holdTimeout = setTimeout(() => {
                    isHolding = true;
                    pauseStory();
                }, 180);
            };

            const handleMove = (e) => {
                if (e.touches && e.touches[0]) {
                    if (Math.abs(e.touches[0].clientX - startX) > 10 || Math.abs(e.touches[0].clientY - startY) > 10) {
                        hasMoved = true;
                        clearTimeout(holdTimeout);
                    }
                }
            };

            const handleUp = (e) => {
                clearTimeout(holdTimeout);
                if (isHolding) {
                    isHolding = false;
                    resumeStory();
                } else if (!hasMoved) {
                    onClick();
                }
            };

            element.addEventListener('mousedown', handleDown);
            element.addEventListener('mouseup', handleUp);
            element.addEventListener('touchstart', handleDown, { passive: true });
            element.addEventListener('touchmove', handleMove, { passive: true });
            element.addEventListener('touchend', handleUp);
        }

        attachHoldAndTap(zonePrev, goToPrev);
        attachHoldAndTap(zoneNext, goToNext);

        // Navigation Buttons
        if (prevBtn) prevBtn.addEventListener('click', (e) => { e.stopPropagation(); goToPrev(); });
        if (nextBtn) nextBtn.addEventListener('click', (e) => { e.stopPropagation(); goToNext(); });
        if (closeBtn) closeBtn.addEventListener('click', closeStory);

        soundToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            videoEl.muted = !videoEl.muted;
            updateSoundIcon();
        });

        // Close on Click Outside Container
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeStory();
            }
        });

        // Keyboard Navigation
        window.addEventListener('keydown', function(e) {
            if (modal.classList.contains('hidden')) return;

            if (e.key === 'Escape') {
                closeStory();
            } else if (e.key === 'ArrowRight') {
                goToNext();
            } else if (e.key === 'ArrowLeft') {
                goToPrev();
            } else if (e.key === ' ') {
                e.preventDefault();
                if (isPaused) {
                    resumeStory();
                } else {
                    pauseStory();
                }
            }
        });
    })();
    </script>
@endif
