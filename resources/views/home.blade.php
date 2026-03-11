@extends('layouts.app-public')

@section('title', 'Home - Transform Your Business')

@section('content')
<!-- Hero Section -->
<section id="hero" role="region" aria-labelledby="hero-heading" class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-900 via-purple-900 to-black pt-20 pb-10 lg:pt-32 lg:pb-0">
    <!-- Animated Background -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-0 left-1/2 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-1/3 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Left Content -->
            <div class="text-center lg:text-left space-y-6 lg:space-y-8">
                <div class="inline-block">
                    <span class="px-4 py-2 bg-indigo-500/20 border border-indigo-400/50 text-indigo-300 text-sm font-semibold rounded-full backdrop-blur-sm">
                        ✨ Innovative Tech Solutions
                    </span>
                </div>
                <h1 id="hero-heading" class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight">
                    Transform Your Business with <span class="bg-gradient-to-r from-yellow-400 to-pink-500 bg-clip-text text-transparent">Cutting-Edge Technology</span>
                </h1>
                <p class="text-lg sm:text-xl text-gray-300 max-w-2xl">
                    We deliver innovative tech solutions that drive growth, efficiency, and success. Let's build the future together.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold rounded-xl shadow-2xl transition transform hover:scale-105 duration-300">
                        <span>Explore Services</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white/10 border-2 border-white/30 hover:border-white/50 hover:bg-white/20 text-white font-bold rounded-xl backdrop-blur-sm transition duration-300">
                        <span>Get in Touch</span>
                    </a>
                </div>
            </div>

            <!-- Right Illustration -->
            <div class="hidden lg:flex items-center justify-center">
                <div class="relative w-full max-w-md h-96">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-3xl blur-2xl opacity-30"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" class="relative">
                        <!-- Simplified tech illustration -->
                        <rect x="50" y="50" width="300" height="300" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" stroke-width="2" rx="20"/>
                        <circle cx="200" cy="200" r="80" fill="none" stroke="rgba(167,139,250,0.3)" stroke-width="2"/>
                        <circle cx="200" cy="200" r="60" fill="none" stroke="rgba(147,112,219,0.5)" stroke-width="2"/>
                        <circle cx="200" cy="200" r="40" fill="rgba(139,92,246,0.2)" stroke="rgba(167,139,250,1)" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Wave Divider -->
    <div class="absolute bottom-0 left-0 right-0 h-20">
        <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
            <path d="M0 50L60 45C120 40 240 30 360 35C480 40 600 60 720 65C840 70 960 55 1080 50C1200 45 1320 55 1380 60L1440 65V100H1380C1320 100 1200 100 1080 100C960 100 840 100 720 100C600 100 480 100 360 100C240 100 120 100 60 100H0Z" fill="rgb(249, 250, 251)"/>
        </svg>
    </div>
</section>

<!-- Services Categories Section -->
<section id="categories" role="region" aria-labelledby="categories-heading" class="py-12 sm:py-16 lg:py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 lg:mb-16">
            <h2 id="categories-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Service Categories
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Explore our comprehensive range of tech solutions designed for your business
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            @foreach($categories as $category)
            <a href="{{ route('services.index', ['category' => $category->slug]) }}" class="group relative bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition duration-300 transform hover:-translate-y-2 overflow-hidden">
                <!-- Background Gradient -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/0 to-purple-600/0 group-hover:from-indigo-500/10 group-hover:to-purple-600/10 transition duration-300"></div>
                
                <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-100 to-purple-100 group-hover:from-indigo-200 group-hover:to-purple-200 rounded-2xl flex items-center justify-center transition duration-300">
                        @if($category->icon)
                            <span class="text-3xl">{{ $category->icon }}</span>
                        @else
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        @endif
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition">{{ $category->name }}</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $category->description }}</p>
                    <div class="pt-3 inline-block px-4 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full group-hover:bg-indigo-100 transition">
                        {{ $category->services_count }} {{ Str::plural('Service', $category->services_count) }}
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Featured Services Section -->
<section id="featured-services" role="region" aria-labelledby="featured-services-heading" class="py-12 sm:py-16 lg:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Featured Services
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Our most popular and highly-rated solutions
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($featuredServices as $service)
            <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition duration-300 overflow-hidden">
                <!-- Image Container -->
                <div class="relative h-56 overflow-hidden bg-gray-200">
                    @if($service->image)
                        <img src="{{ Storage::url($service->image) }}" alt="{{ $service->title }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    @endif
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    <!-- Badge -->
                    <div class="absolute top-4 right-4 z-10">
                        <span class="inline-block px-4 py-2 bg-white/95 backdrop-blur-sm text-indigo-600 text-xs font-bold rounded-full">
                            {{ $service->category->name }}
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 lg:p-7 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition mb-2">{{ $service->title }}</h3>
                            <p class="text-gray-600 text-sm line-clamp-2">{{ $service->short_description }}</p>
                        </div>
                        @if($service->is_featured)
                            <svg class="w-6 h-6 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endif
                    </div>

                    <!-- Price and Details -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Starting from</p>
                            <p class="text-3xl font-bold text-indigo-600">${{ number_format($service->price, 2) }}</p>
                        </div>
                        @if($service->delivery_days)
                            <div class="flex flex-col items-end text-right">
                                <p class="text-sm text-gray-500 mb-1">Delivery</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $service->delivery_days }}d</p>
                            </div>
                        @endif
                    </div>

                    <!-- CTA Button -->
                    <a href="{{ route('services.show', $service->slug) }}" class="w-full block py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl transition duration-300 transform hover:scale-105 text-center">
                        View Details
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-xl transition transform hover:scale-105 duration-300">
                View All Services
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-12 sm:py-16 lg:py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Why Choose Us
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                We're committed to delivering excellence and driving your success
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @php
                $features = [
                    [
                        'icon' => 'M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z',
                        'title' => 'Quality Assured',
                        'description' => 'Rigorous testing and quality checks ensure excellence in every deliverable.'
                    ],
                    [
                        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                        'title' => 'On-Time Delivery',
                        'description' => 'We respect your timeline and deliver projects exactly when promised.'
                    ],
                    [
                        'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
                        'title' => 'Expert Support',
                        'description' => 'Our dedicated team provides 24/7 support to ensure your success.'
                    ]
                ];
            @endphp

            @foreach($features as $feature)
            <div class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                <div class="flex items-start space-y-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-14 w-14 bg-gradient-to-br from-indigo-500 to-purple-600 group-hover:scale-110 transition duration-300 rounded-xl">
                            <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ $feature['description'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Latest Blogs Section -->
<section class="py-12 sm:py-16 lg:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-12 lg:mb-16 gap-8">
            <div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Latest Insights
                </h2>
                <p class="text-lg text-gray-600">
                    Stay updated with expert tips and industry trends
                </p>
            </div>
            <div class="flex gap-3">
                <button id="blog-prev" class="p-3 bg-gray-100 hover:bg-indigo-600 hover:text-white text-gray-600 rounded-xl transition transform hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button id="blog-next" class="p-3 bg-gray-100 hover:bg-indigo-600 hover:text-white text-gray-600 rounded-xl transition transform hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        @if($latestBlogs->isNotEmpty())
        <div class="relative overflow-hidden">
            <div id="blog-container" class="flex gap-6 overflow-x-auto scroll-smooth pb-4" style="scrollbar-width: none; -ms-overflow-style: none;">
                @foreach($latestBlogs as $blog)
                    <a href="{{ route('blogs.show', $blog->slug) }}" class="flex-none w-80 group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden">
                        <!-- Image -->
                        <div class="relative h-48 overflow-hidden">
                            @if($blog->featured_image)
                                <img src="{{ Storage::url($blog->featured_image) }}" alt="{{ $blog->title }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                    <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                    </svg>
                                </div>
                            @endif
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 bg-white/95 backdrop-blur-sm text-indigo-600 text-xs font-bold rounded-full">
                                    {{ $blog->category->name }}
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6 space-y-3">
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $blog->published_at->format('M d, Y') }}
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition line-clamp-2">
                                {{ $blog->title }}
                            </h3>

                            <p class="text-gray-600 text-sm line-clamp-2">
                                {{ Str::limit(strip_tags($blog->content), 80) }}
                            </p>

                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-indigo-600 font-semibold text-sm flex items-center gap-2 group-hover:gap-3 transition-all">
                                    Read More
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @else
        <div class="text-center py-16 bg-gray-50 rounded-2xl">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
            </svg>
            <p class="text-gray-600 text-lg">No blogs available yet. Check back soon!</p>
        </div>
        @endif

        <div class="mt-12 text-center">
            <a href="{{ route('blogs.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg transition transform hover:scale-105">
                Explore All Articles
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 sm:py-20 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full mix-blend-multiply filter blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full mix-blend-multiply filter blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6">
            Ready to Transform Your Business?
        </h2>
        <p class="text-lg sm:text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
            Join thousands of satisfied clients who trust us with their digital transformation journey
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-indigo-600 hover:bg-gray-100 font-bold rounded-xl shadow-2xl transition transform hover:scale-105">
                Explore Services
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white/20 border-2 border-white hover:bg-white/30 text-white font-bold rounded-xl backdrop-blur-sm transition transform hover:scale-105">
                Contact Us
            </a>
        </div>
    </div>
</section>

<!-- Custom Animations -->
<style>
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }

    .animate-blob {
        animation: blob 7s infinite;
    }

    .animation-delay-2000 {
        animation-delay: 2s;
    }

    .animation-delay-4000 {
        animation-delay: 4s;
    }

    #blog-container::-webkit-scrollbar {
        display: none;
    }

    @media (max-width: 768px) {
        .line-clamp-2 {
            -webkit-line-clamp: 2;
        }
        
        .line-clamp-3 {
            -webkit-line-clamp: 3;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('blog-container');
    const prevBtn = document.getElementById('blog-prev');
    const nextBtn = document.getElementById('blog-next');
    
    if (!container || !prevBtn || !nextBtn) return;
    
    const cardWidth = 320 + 24; // 320px width + 24px gap
    const scrollAmount = cardWidth;
    
    function updateButtons() {
        prevBtn.disabled = container.scrollLeft <= 0;
        nextBtn.disabled = container.scrollLeft >= container.scrollWidth - container.clientWidth - 10;
    }
    
    prevBtn.addEventListener('click', () => {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        setTimeout(updateButtons, 300);
    });
    
    nextBtn.addEventListener('click', () => {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        setTimeout(updateButtons, 300);
    });
    
    container.addEventListener('scroll', updateButtons);
    updateButtons();
});
</script>

                       

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('blog-container');
    const prevBtn = document.getElementById('blog-prev');
    const nextBtn = document.getElementById('blog-next');
    
    if (!container || !prevBtn || !nextBtn) return;
    
    const cardWidth = 320 + 24; // 320px width + 24px gap
    const visibleCards = 4;
    const scrollAmount = cardWidth * visibleCards;
    
    function updateButtons() {
        prevBtn.disabled = container.scrollLeft <= 0;
        nextBtn.disabled = container.scrollLeft >= container.scrollWidth - container.clientWidth - 10;
    }
    
    prevBtn.addEventListener('click', () => {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        setTimeout(updateButtons, 300);
    });
    
    nextBtn.addEventListener('click', () => {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        setTimeout(updateButtons, 300);
    });
    
    container.addEventListener('scroll', updateButtons);
    updateButtons();
});
</script>

<style>
#blog-container::-webkit-scrollbar {
    display: none;
}
</style>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Get Started?</h2>
        <p class="text-xl mb-8 text-indigo-100">Let's discuss how we can help transform your business</p>
        <a href="{{ route('services.index') }}" class="inline-block bg-white text-indigo-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition transform hover:scale-105">
            Explore Our Services
        </a>
    </div>
</section>
@endsection
