@extends('layouts.app-public')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Featured Blogs Carousel -->
        @if($featuredBlogs->count() > 0)
            <div class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-2">Featured Articles</h2>
                        <p class="text-gray-600">Handpicked stories from our best authors</p>
                    </div>
                    @if($featuredBlogs->count() > 1)
                        <div class="hidden md:flex gap-3">
                            <button id="prevBtn" onclick="prevSlide()" class="group p-3 rounded-full bg-white border-2 border-gray-200 hover:border-indigo-600 hover:bg-indigo-50 transition-all shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button id="nextBtn" onclick="nextSlide()" class="group p-3 rounded-full bg-white border-2 border-gray-200 hover:border-indigo-600 hover:bg-indigo-50 transition-all shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
                
                <div class="relative overflow-hidden rounded-2xl shadow-2xl">
                    <div id="carousel" class="flex transition-transform duration-700 ease-in-out">
                        @foreach($featuredBlogs as $featured)
                            <div class="min-w-full flex-shrink-0 carousel-slide">
                                <div class="relative h-[280px] md:h-[420px] lg:h-[480px] bg-gradient-to-br from-gray-900 to-gray-800 overflow-hidden group">
                                    @if($featured->featured_image)
                                        <img src="{{ asset('storage/' . $featured->featured_image) }}" alt="{{ $featured->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent"></div>
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600">
                                            <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                                        </div>
                                        <div class="absolute inset-0 bg-black/30"></div>
                                    @endif
                                    
                                    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
                                    
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full lg:w-2/3 p-6 md:p-8 lg:p-12 space-y-3">
                                            <div class="inline-flex items-center gap-2 flex-wrap">
                                                <span class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg backdrop-blur-sm" style="background-color: {{ $featured->category->color }}">
                                                    {{ $featured->category->name }}
                                                </span>
                                                @if($loop->first)
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500 text-white shadow-lg flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                        Featured
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <h3 class="text-xl md:text-3xl lg:text-4xl font-bold text-white line-clamp-2 leading-tight">
                                                {{ $featured->title }}
                                            </h3>
                                            
                                            @if($featured->excerpt)
                                                <p class="text-sm md:text-base text-white/90 line-clamp-2 max-w-2xl hidden md:block">{{ $featured->excerpt }}</p>
                                            @endif
                                            
                                            <div class="flex flex-wrap items-center gap-3 md:gap-4">
                                                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md rounded-full px-3 py-1.5">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shadow-lg">
                                                        {{ strtoupper(substr($featured->user->name, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-white font-semibold text-xs">{{ $featured->user->name }}</p>
                                                        <p class="text-white/70 text-xs">{{ $featured->published_at->format('M d, Y') }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 md:gap-3 text-white/90 text-xs bg-white/10 backdrop-blur-md rounded-full px-3 py-1.5">
                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        {{ number_format($featured->views_count) }}
                                                    </span>
                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                        </svg>
                                                        {{ number_format($featured->likes_count) }}
                                                    </span>
                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                        </svg>
                                                        {{ number_format($featured->comments_count) }}
                                                    </span>
                                                    <span class="hidden md:inline-flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        {{ ceil(str_word_count($featured->content) / 200) }} min
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <a href="{{ route('blogs.show', $featured->slug) }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-white text-gray-900 font-bold rounded-lg hover:bg-gray-50 hover:scale-105 transition-all shadow-xl text-sm">
                                                Read Article
                                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Slide Indicators -->
                                    @if($featuredBlogs->count() > 1)
                                        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 bg-black/30 backdrop-blur-sm rounded-full px-3 py-2">
                                            @foreach($featuredBlogs as $index => $item)
                                                <button onclick="goToSlide({{ $index }})" class="slide-indicator transition-all duration-300 {{ $loop->first ? 'w-8 h-2.5 bg-white' : 'w-2.5 h-2.5 bg-white/50 hover:bg-white/75' }} rounded-full" data-index="{{ $index }}"></button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Category Header -->
        <div class="text-center mb-12">
            <div class="inline-block px-6 py-3 rounded-full text-white font-bold text-lg mb-4 shadow-lg" style="background-color: {{ $category->color }}">
                {{ $category->name }}
            </div>
            @if($category->description)
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    {{ $category->description }}
                </p>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Categories</h3>
                    <div class="space-y-2">
                        <a href="{{ route('blogs.index') }}" class="block px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                            All Posts
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ route('blogs.category', $cat->slug) }}" class="block px-4 py-2 rounded-lg {{ $cat->id == $category->id ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white' : 'text-gray-700 hover:bg-gray-100' }} transition">
                                <span class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: {{ $cat->color }}"></span>
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Blog Grid -->
            <div class="lg:col-span-3">
                @if($blogs->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($blogs as $blog)
                            <article class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300">
                                <!-- Featured Image -->
                                <div class="relative h-48 bg-gradient-to-br from-indigo-400 to-purple-500 overflow-hidden">
                                    @if($blog->featured_image)
                                        <img src="{{ asset('storage/' . $blog->featured_image) }}" alt="{{ $blog->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($blog->is_featured)
                                        <span class="absolute top-4 right-4 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full">
                                            Featured
                                        </span>
                                    @endif
                                </div>

                                <div class="p-6">
                                    <!-- Category Badge -->
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white mb-3" style="background-color: {{ $blog->category->color }}">
                                        {{ $blog->category->name }}
                                    </span>

                                    <!-- Title -->
                                    <h2 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2 hover:text-indigo-600 transition">
                                        <a href="{{ route('blogs.show', $blog->slug) }}">{{ $blog->title }}</a>
                                    </h2>

                                    <!-- Excerpt -->
                                    @if($blog->excerpt)
                                        <p class="text-gray-600 mb-4 line-clamp-3">{{ $blog->excerpt }}</p>
                                    @endif

                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold text-xs">
                                                {{ strtoupper(substr($blog->user->name, 0, 2)) }}
                                            </div>
                                            <span>{{ $blog->user->name }}</span>
                                        </div>
                                        <span>{{ $blog->published_at->format('M d, Y') }}</span>
                                    </div>

                                    <!-- Stats -->
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                {{ $blog->views_count }}
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                </svg>
                                                {{ $blog->likes_count }}
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                </svg>
                                                {{ $blog->comments_count }}
                                            </span>
                                        </div>
                                        <a href="{{ route('blogs.show', $blog->slug) }}" class="text-indigo-600 font-semibold hover:text-indigo-800 transition">
                                            Read More →
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-12">
                        {{ $blogs->links() }}
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                        <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No posts in this category yet</h3>
                        <p class="text-gray-600 mb-6">Check back later for exciting content!</p>
                        <a href="{{ route('blogs.index') }}" class="inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            Browse All Posts
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($featuredBlogs->count() > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentSlide = 0;
        const carousel = document.getElementById('carousel');
        const indicators = document.querySelectorAll('.slide-indicator');
        const totalSlides = {{ $featuredBlogs->count() }};
        let autoPlayInterval;
        let isTransitioning = false;
        
        function updateSlide() {
            if (isTransitioning) return;
            isTransitioning = true;
            
            carousel.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            indicators.forEach((indicator, index) => {
                if (index === currentSlide) {
                    indicator.classList.remove('w-2.5', 'h-2.5', 'bg-white/50');
                    indicator.classList.add('w-8', 'h-2.5', 'bg-white');
                } else {
                    indicator.classList.remove('w-8', 'h-2.5', 'bg-white');
                    indicator.classList.add('w-2.5', 'h-2.5', 'bg-white/50');
                }
            });
            
            setTimeout(() => {
                isTransitioning = false;
            }, 700);
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlide();
        }
        
        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateSlide();
        }
        
        window.goToSlide = function(index) {
            currentSlide = index;
            updateSlide();
            resetAutoPlay();
        };
        
        window.nextSlide = function() {
            nextSlide();
            resetAutoPlay();
        };
        
        window.prevSlide = function() {
            prevSlide();
            resetAutoPlay();
        };
        
        function startAutoPlay() {
            if (totalSlides > 1) {
                autoPlayInterval = setInterval(nextSlide, 6000);
            }
        }
        
        function stopAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
            }
        }
        
        function resetAutoPlay() {
            stopAutoPlay();
            startAutoPlay();
        }
        
        // Start auto-play
        startAutoPlay();
        
        // Pause on hover
        const carouselContainer = carousel.parentElement;
        carouselContainer.addEventListener('mouseenter', stopAutoPlay);
        carouselContainer.addEventListener('mouseleave', startAutoPlay);
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                resetAutoPlay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                resetAutoPlay();
            }
        });
        
        // Touch swipe support
        let touchStartX = 0;
        let touchEndX = 0;
        
        carouselContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoPlay();
        });
        
        carouselContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoPlay();
        });
        
        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                nextSlide();
            }
            if (touchEndX > touchStartX + 50) {
                prevSlide();
            }
        }
    });
</script>
@endif

@endsection
