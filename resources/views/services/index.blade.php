@extends('layouts.app-public')

@section('title', 'Our Services')

@section('content')
<!-- Page Header -->
<section class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Services</h1>
        <p class="text-xl text-indigo-100">Discover the perfect solution for your business needs</p>
    </div>
</section>

<!-- Filters and Search -->
<section class="bg-white border-b py-6 sticky top-16 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('services.index') }}" class="flex flex-col md:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search services..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <!-- Category Filter -->
            <div class="md:w-64">
                <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Search
            </button>
        </form>
    </div>
</section>

<!-- Service Categories Showcase -->
@if(!request('search') && !request('category') && $categories->count() > 0)
<section class="py-12 bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Service Categories</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $category)
            <div class="bg-gradient-to-br from-gray-50 to-indigo-50 rounded-xl p-6 border-2 border-indigo-100 hover:shadow-xl transition-all hover:border-indigo-300">
                <div class="flex items-center mb-4">
                    @if($category->icon)
                    <span class="text-4xl mr-3">{{ $category->icon }}</span>
                    @endif
                    <h3 class="text-xl font-bold text-gray-900">{{ $category->name }}</h3>
                </div>
                
                @if($category->description)
                <p class="text-gray-600 mb-4">{{ $category->description }}</p>
                @endif
                
                @if($category->features && count($category->features) > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-indigo-700 mb-2 uppercase tracking-wide">Key Features:</h4>
                    <ul class="space-y-2">
                        @foreach(array_slice($category->features, 0, 4) as $feature)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-indigo-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-gray-700 text-sm">{{ $feature }}</span>
                        </li>
                        @endforeach
                        @if(count($category->features) > 4)
                        <li class="text-sm text-indigo-600 ml-7">+{{ count($category->features) - 4 }} more features</li>
                        @endif
                    </ul>
                </div>
                @endif
                
                <a href="{{ route('services.category', $category->slug) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-semibold mt-2">
                    View Category
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Services Grid -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($services->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($services as $service)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition transform hover:-translate-y-2">
                    @if($service->image)
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <img src="{{ Storage::url($service->image) }}" alt="{{ $service->title }}" class="w-full h-full object-cover">
                    </div>
                    @else
                    <div class="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    @endif
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                                {{ $service->category->name }}
                            </span>
                            @if($service->is_featured)
                            <span class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full">
                                ⭐ Featured
                            </span>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $service->title }}</h3>
                        
                        <p class="text-gray-600 mb-4 line-clamp-3">{{ $service->short_description }}</p>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-2xl font-bold text-gray-900">${{ number_format($service->price, 2) }}</span>
                                <span class="text-sm text-gray-500">{{ $service->price_unit }}</span>
                            </div>
                            @if($service->delivery_days)
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $service->delivery_days }} days
                            </div>
                            @endif
                        </div>
                        
                        <a href="{{ route('services.show', $service->slug) }}" class="block w-full text-center bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                            View Details
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="mt-12">
                {{ $services->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">No Services Found</h3>
                <p class="text-gray-600 mb-6">Try adjusting your search or filter criteria</p>
                <a href="{{ route('services.index') }}" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    View All Services
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
