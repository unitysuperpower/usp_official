@extends('layouts.app-public')

@section('content')
<style>
    @keyframes fade-in {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse-slow {

        0%,
        100% {
            opacity: 0.6;
        }

        50% {
            opacity: 1;
        }
    }

    @keyframes spin-slow {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.8s ease-in-out;
    }

    .animate-fade-in-up {
        animation: fade-in-up 0.8s ease-in-out;
    }

    .animate-pulse-slow {
        animation: pulse-slow 3s ease-in-out infinite;
    }

    .animate-spin-slow {
        animation: spin-slow 8s linear infinite;
    }

    /* ensure each slide = full visible width */
    .carousel-slide {
        flex: 0 0 100%;
        max-width: 100%;
    }
</style>

<!-- Hero Section -->
<div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 py-20 overflow-hidden">
    <div class="absolute inset-0 bg-black opacity-10"></div>
    <div class="absolute inset-0"
         style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-block mb-6">
            <span class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                    </path>
                </svg>
                Knowledge Hub
            </span>
        </div>
        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 animate-fade-in">
            Discover Our Blog
        </h1>
        <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto mb-8">
            Explore insights, tutorials, and stories from our experts. Stay updated with the latest trends and tips.
        </p>
        <div class="flex justify-center gap-4">
            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-3 text-white">
                <div class="text-3xl font-bold">{{ $blogs->total() }}</div>
                <div class="text-sm opacity-90">Articles</div>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-3 text-white">
                <div class="text-3xl font-bold">{{ $categories->count() }}</div>
                <div class="text-sm opacity-90">Categories</div>
            </div>
        </div>
    </div>
</div>

<div class="bg-gradient-to-br from-gray-50 to-gray-100 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Featured Blogs Carousel -->
        @if($featuredBlogs->count() > 0)
            <div class="mb-16 relative">

                <!-- Decorative Background Elements -->
                <div class="absolute -top-20 -right-20 w-72 h-72 bg-indigo-100 rounded-full blur-3xl opacity-30"></div>
                <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-purple-100 rounded-full blur-3xl opacity-30"></div>

                <div class="relative flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-1.5 h-12 bg-gradient-to-b from-indigo-600 to-purple-600 rounded-full"></div>
                        <div>
                            <h2 class="text-4xl font-bold bg-gradient-to-r from-gray-900 via-indigo-900 to-purple-900 bg-clip-text text-transparent mb-2">
                                Featured Articles
                            </h2>
                            <p class="text-gray-600 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                    </path>
                                </svg>
                                Handpicked stories from our best authors
                            </p>
                        </div>
                    </div>

                    @if($featuredBlogs->count() > 1)
                        <div class="hidden md:flex gap-3">
                            <button id="prevBtn" onclick="prevSlide()"
                                    class="group relative p-3 rounded-full bg-white border-2 border-gray-200 hover:border-indigo-600 hover:bg-gradient-to-br hover:from-indigo-50 hover:to-purple-50 transition-all shadow-lg hover:shadow-indigo-200">
                                <div
                                    class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-10 transition">
                                </div>
                                <svg class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 relative z-10" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <button id="nextBtn" onclick="nextSlide()"
                                    class="group relative p-3 rounded-full bg-white border-2 border-gray-200 hover:border-indigo-600 hover:bg-gradient-to-br hover:from-indigo-50 hover:to-purple-50 transition-all shadow-lg hover:shadow-indigo-200">
                                <div
                                    class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-10 transition">
                                </div>
                                <svg class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 relative z-10" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>

                <div class="relative overflow-hidden rounded-2xl shadow-2xl ring-1 ring-black/5">
                    <div
                        class="absolute -inset-0.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl blur opacity-20">
                    </div>

                    <!-- Track -->
                    <div id="carousel"
                         class="relative flex transition-transform duration-700 ease-in-out bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl">

                        @foreach($featuredBlogs as $featured)
                            <!-- Slide -->
                            <div class="min-w-full carousel-slide relative">
                                <div
                                    class="relative h-[280px] md:h-[420px] lg:h-[480px] bg-gradient-to-br from-gray-900 to-gray-800 overflow-hidden group">

                                    <div
                                        class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition duration-1000">
                                    </div>

                                    @if($featured->featured_image)
                                        <img src="{{ asset('storage/' . $featured->featured_image) }}"
                                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                             alt="{{ $featured->title }}">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent"></div>
                                    @else
                                        <div
                                            class="w-full h-full bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 relative">
                                            <div class="absolute inset-0 opacity-20"
                                                 style="background-image:url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'white\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/svg%3E');">
                                            </div>
                                        </div>
                                        <div class="absolute inset-0 bg-black/30"></div>
                                    @endif

                                    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>

                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full lg:w-2/3 p-6 md:p-8 lg:p-12 space-y-3">

                                            <div class="inline-flex items-center gap-2 animate-fade-in">
                                                <span
                                                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-white backdrop-blur border border-white/20 relative"
                                                    style="background-color: {{ $featured->category->color }};">
                                                    {{ $featured->category->name }}
                                                </span>

                                                @if($loop->first)
                                                    <span
                                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-amber-500 to-orange-500 text-white flex items-center gap-1 animate-pulse-slow border border-amber-300/30">
                                                        <svg class="w-3 h-3 animate-spin-slow" fill="currentColor"
                                                             viewBox="0 0 20 20">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                                            </path>
                                                        </svg>
                                                        Featured
                                                    </span>
                                                @endif
                                            </div>

                                            <h3
                                                class="text-xl md:text-3xl lg:text-4xl font-bold text-white line-clamp-2 drop-shadow-xl">
                                                {{ $featured->title }}
                                            </h3>

                                            @if($featured->excerpt)
                                                <p
                                                    class="hidden md:block text-white/90 text-sm md:text-base line-clamp-2 drop-shadow-lg">
                                                    {{ $featured->excerpt }}
                                                </p>
                                            @endif

                                            <div class="flex flex-wrap items-center gap-3 md:gap-4 text-white/90">

                                                <!-- Author -->
                                                <div
                                                    class="flex items-center gap-2 bg-white/10 backdrop-blur rounded-full px-3 py-1.5 border border-white/20">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 via-purple-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                                        {{ strtoupper(substr($featured->user->name, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-white text-xs font-semibold">
                                                            {{ $featured->user->name }}
                                                        </p>
                                                        <p class="text-white/70 text-xs">
                                                            {{ $featured->published_at->format('M d, Y') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Stats -->
                                                <div
                                                    class="flex items-center gap-3 text-xs bg-white/10 backdrop-blur rounded-full px-3 py-1.5 border border-white/20">

                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" stroke="currentColor" fill="none"
                                                             viewBox="0 0 24 24">
                                                            <path stroke-width="2" stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-width="2" stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7-4.03 8-9.542 8S3.732 16.057 2.458 12z"/>
                                                        </svg>
                                                        {{ number_format($featured->views_count) }}
                                                    </span>

                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" stroke="currentColor" fill="none"
                                                             viewBox="0 0 24 24">
                                                            <path stroke-width="2" stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636 10.682 6.318a4.5 4.5 0 00-6.364 0z"/>
                                                        </svg>
                                                        {{ number_format($featured->likes_count) }}
                                                    </span>

                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" stroke="currentColor" fill="none"
                                                             viewBox="0 0 24 24">
                                                            <path stroke-width="2" stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                        </svg>
                                                        {{ number_format($featured->comments_count) }}
                                                    </span>

                                                    <span class="hidden md:flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                             viewBox="0 0 24 24">
                                                            <path stroke-width="2" stroke-linecap="round"
                                                                  stroke-linejoin="round"
                                                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        {{ ceil(str_word_count($featured->content) / 200) }} min
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Read Button -->
                                            <a href="{{ route('blogs.show', $featured->slug) }}"
                                               class="group relative inline-flex items-center gap-2 px-6 py-2.5 bg-white text-gray-900 font-bold rounded-lg shadow-xl hover:scale-105 transition">
                                                <span class="relative z-10">Read Article</span>
                                                <svg class="w-4 h-4 relative z-10 group-hover:translate-x-1 transition"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-width="2.5" stroke-linecap="round"
                                                          stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                                </svg>
                                                <div
                                                    class="absolute inset-0 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-20 transition">
                                                </div>
                                            </a>

                                        </div>
                                    </div>

                                    <!-- Slide Indicators -->
                                    @if($featuredBlogs->count() > 1)
                                        <div
                                            class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 bg-black/40 backdrop-blur-xl rounded-full px-4 py-2.5 border border-white/20">
                                            @foreach($featuredBlogs as $index => $item)
                                                <button
                                                    onclick="goToSlide({{ $index }})"
                                                    class="slide-indicator transition-all duration-300 {{ $loop->first ? 'w-8 h-2.5 bg-gradient-to-r from-indigo-500 to-purple-500' : 'w-2.5 h-2.5 bg-white/50' }} rounded-full hover:scale-110"
                                                    data-index="{{ $index }}">
                                                </button>
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

        <!-- Categories Filter -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">All Articles</h2>
            <div class="flex flex-wrap gap-3 items-center">
                <span class="text-sm font-semibold text-gray-700">Filter by:</span>
                <a href="{{ route('blogs.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium {{ !request('category') ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200' }} transition-all">
                    All Posts
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('blogs.category', $category->slug) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-all">
                        <span class="inline-block w-2 h-2 rounded-full mr-1.5"
                              style="background-color: {{ $category->color }}"></span>
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Blog Grid -->
        @if($blogs->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($blogs as $blog)
                    <article
                        class="group bg-white rounded-xl shadow-sm hover:shadow-lg border border-gray-200 overflow-hidden transition-all duration-300 flex flex-col">
                        <!-- Featured Image -->
                        <a href="{{ route('blogs.show', $blog->slug) }}"
                           class="relative block h-56 overflow-hidden bg-gradient-to-br from-gray-900 to-gray-800">
                            @if($blog->featured_image)
                                <img src="{{ asset('storage/' . $blog->featured_image) }}" alt="{{ $blog->title }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            @else
                                <div
                                    class="w-full h-full flex items-center justify-center text-white text-4xl font-bold">
                                    {{ strtoupper(substr($blog->title, 0, 1)) }}
                                </div>
                            @endif

                            <!-- Category Badge on Image -->
                            <div class="absolute bottom-3 left-3">
                                <span
                                    class="inline-block px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg"
                                    style="background-color: {{ $blog->category->color }}">
                                    {{ $blog->category->name }}
                                </span>
                            </div>

                            @if($blog->is_featured)
                                <div class="absolute top-3 left-3">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-white text-gray-900 text-xs font-bold rounded-lg shadow-lg">
                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        BONUS
                                    </span>
                                </div>
                            @endif
                        </a>

                        <!-- Content -->
                        <div class="p-4 flex flex-col flex-grow bg-white">
                            <!-- Title -->
                            <h3
                                class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 leading-tight min-h-[3.5rem]">
                                <a href="{{ route('blogs.show', $blog->slug) }}"
                                   class="hover:text-indigo-600 transition-colors">
                                    {{ $blog->title }}
                                </a>
                            </h3>

                            <!-- Excerpt -->
                            @if($blog->excerpt)
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed">
                                    {{ $blog->excerpt }}
                                </p>
                            @else
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($blog->content), 100) }}
                                </p>
                            @endif

                            <!-- Meta -->
                            <div class="mt-auto">
                                <!-- Author -->
                                <div class="flex items-center gap-2 mb-3">
                                    <div
                                        class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                        {{ strtoupper(substr($blog->user->name, 0, 2)) }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900">{{ $blog->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $blog->published_at->format('M d, Y') }}</p>
                                    </div>
                                </div>

                                <!-- Stats -->
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            {{ $blog->views_count }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                            {{ $blog->likes_count }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        {{ $blog->comments_count }}
                                    </span>
                                </div>
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
                <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                    </path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No blog posts yet</h3>
                <p class="text-gray-600">Check back later for exciting content!</p>
            </div>
        @endif
    </div>
</div>

@if($featuredBlogs->count() > 0)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let currentSlide = 0;
        const carousel = document.getElementById('carousel');
        const indicators = document.querySelectorAll('.slide-indicator');
        const totalSlides = {{ $featuredBlogs->count() }};
        let autoPlayInterval;
        let isTransitioning = false;

        const carouselContainer = carousel.parentElement;
        let slideWidth = carouselContainer.offsetWidth;

        // Recalculate slide width on resize
        window.addEventListener('resize', function () {
            slideWidth = carouselContainer.offsetWidth;
            updateSlide(false); // reposition without locking
        });

        function updateSlide(lock = true) {
            if (isTransitioning && lock) return;
            isTransitioning = true;

            const offset = -currentSlide * slideWidth;
            carousel.style.transform = `translateX(${offset}px)`;

            indicators.forEach((indicator, index) => {
                if (index === currentSlide) {
                    indicator.classList.remove('w-2.5', 'h-2.5', 'bg-white/50');
                    indicator.classList.add('w-8', 'h-2.5', 'bg-gradient-to-r', 'from-indigo-500', 'to-purple-500', 'shadow-lg');
                } else {
                    indicator.classList.remove('w-8', 'h-2.5', 'bg-gradient-to-r', 'from-indigo-500', 'to-purple-500', 'shadow-lg');
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

        window.goToSlide = function (index) {
            currentSlide = index;
            updateSlide();
            resetAutoPlay();
        };

        window.nextSlide = function () {
            nextSlide();
            resetAutoPlay();
        };

        window.prevSlide = function () {
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

        // Hover pause + touch swipe
        if (carouselContainer) {
            carouselContainer.addEventListener('mouseenter', stopAutoPlay);
            carouselContainer.addEventListener('mouseleave', startAutoPlay);

            let touchStartX = 0;
            let touchEndX = 0;

            carouselContainer.addEventListener('touchstart', function (e) {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoPlay();
            });

            carouselContainer.addEventListener('touchend', function (e) {
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
        }

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                resetAutoPlay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                resetAutoPlay();
            }
        });

        // Initial position
        updateSlide(false);
        startAutoPlay();
    });
</script>
@endif

@endsection
