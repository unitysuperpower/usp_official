@extends('layouts.app-public')

@section('title', $category->name . ' - Service Category')

@section('content')
<!-- Breadcrumb -->
<section class="bg-gray-100 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-indigo-600 hover:text-indigo-800">Home</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="{{ route('services.index') }}" class="text-indigo-600 hover:text-indigo-800">Services</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-700">{{ $category->name }}</span>
        </nav>
    </div>
</section>

<!-- Category Hero Section -->
<section class="py-16 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto">
            @if($category->icon)
            <div class="text-6xl mb-6">{{ $category->icon }}</div>
            @endif
            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $category->name }}</h1>
            @if($category->description)
            <p class="text-xl text-indigo-100 mb-8">{{ $category->description }}</p>
            @endif
        </div>
    </div>
</section>

<!-- Category Features -->
@if($category->features && count($category->features) > 0)
<section class="py-12 bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Key Features & Benefits</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($category->features as $feature)
            <div class="flex items-start bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border-2 border-indigo-100 hover:border-indigo-300 transition-all">
                <svg class="w-6 h-6 text-indigo-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-gray-800 font-medium">{{ $feature }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Other Categories -->
@if($allCategories->count() > 1)
<section class="py-8 bg-gray-50 border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Explore Other Categories</h3>
        <div class="flex flex-wrap gap-3">
            @foreach($allCategories as $cat)
                @if($cat->id !== $category->id)
                <a href="{{ route('services.category', $cat->slug) }}" class="inline-flex items-center bg-white hover:bg-indigo-50 border-2 border-gray-200 hover:border-indigo-300 text-gray-700 hover:text-indigo-700 px-4 py-2 rounded-lg transition-all font-medium">
                    @if($cat->icon)
                    <span class="text-xl mr-2">{{ $cat->icon }}</span>
                    @endif
                    {{ $cat->name }}
                </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Services Grid -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Available Services</h2>
        
        @if($services->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                @foreach($services as $service)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                    @if($service->image)
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <img src="{{ Storage::url($service->image) }}" alt="{{ $service->title }}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                    </div>
                    @else
                    <div class="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    @endif
                    <div class="p-6">
                        @if($service->is_featured)
                        <span class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full mb-2 inline-block">
                            ⭐ Featured
                        </span>
                        @endif
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $service->title }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ $service->short_description }}</p>
                        <div class="flex items-center justify-between mb-4 pb-4 border-b">
                            <span class="text-2xl font-bold text-gray-900">${{ number_format($service->price, 2) }}</span>
                            @if($service->delivery_days)
                            <span class="text-sm text-gray-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $service->delivery_days }} days
                            </span>
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
            <div class="mt-8">
                {{ $services->links() }}
            </div>
        @else
            <div class="text-center py-12 bg-white rounded-xl shadow">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-gray-600 text-lg">No services available in this category yet.</p>
                <a href="{{ route('services.index') }}" class="inline-block mt-4 text-indigo-600 hover:text-indigo-800 font-medium">
                    View All Services →
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
