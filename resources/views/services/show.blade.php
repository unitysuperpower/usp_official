@extends('layouts.app-public')

@section('title', $service->title)

@section('content')
<!-- Breadcrumb -->
<section class="bg-gray-100 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-indigo-600 hover:text-indigo-800">Home</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="{{ route('services.index') }}" class="text-indigo-600 hover:text-indigo-800">Services</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-700">{{ $service->title }}</span>
        </nav>
    </div>
</section>

<!-- Service Detail -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Image -->
                @if($service->image)
                <div class="mb-8 rounded-xl overflow-hidden shadow-lg">
                    <img src="{{ Storage::url($service->image) }}" alt="{{ $service->title }}" class="w-full h-96 object-cover">
                </div>
                @else
                <div class="mb-8 rounded-xl overflow-hidden shadow-lg h-96 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                @endif

                <!-- Title and Category -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm font-medium text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                            {{ $service->category->name }}
                        </span>
                        @if($service->is_featured)
                        <span class="text-sm font-medium text-yellow-600 bg-yellow-50 px-3 py-1 rounded-full">
                            ⭐ Featured
                        </span>
                        @endif
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $service->title }}</h1>
                    <p class="text-xl text-gray-600">{{ $service->short_description }}</p>
                </div>

                <!-- Description -->
                <div class="prose max-w-none mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Description</h2>
                    <div class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $service->description }}</div>
                </div>

                <!-- Category Features -->
                @if($service->category->features && count($service->category->features) > 0)
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 mb-8 border-2 border-indigo-100">
                    <div class="flex items-center mb-4">
                        @if($service->category->icon)
                        <span class="text-3xl mr-3">{{ $service->category->icon }}</span>
                        @endif
                        <h2 class="text-2xl font-bold text-gray-900">{{ $service->category->name }} Features</h2>
                    </div>
                    @if($service->category->description)
                    <p class="text-gray-700 mb-4">{{ $service->category->description }}</p>
                    @endif
                    <ul class="space-y-3">
                        @foreach($service->category->features as $feature)
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-indigo-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-gray-800 font-medium">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Service Features -->
                @if($service->features && count($service->features) > 0)
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">What's Included</h2>
                    <ul class="space-y-3">
                        @foreach($service->features as $feature)
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Price Card -->
                <div class="bg-white border-2 border-indigo-600 rounded-xl p-6 sticky top-24 mb-8">
                    <div class="mb-6">
                        <div class="text-4xl font-bold text-gray-900 mb-1">
                            ${{ number_format($service->price, 2) }}
                        </div>
                        <div class="text-gray-600">{{ $service->price_unit }}</div>
                    </div>

                    @if($service->delivery_days)
                    <div class="flex items-center text-gray-700 mb-6 pb-6 border-b">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $service->delivery_days }} days delivery</span>
                    </div>
                    @endif

                    <!-- Contact Form -->
                    <form action="{{ route('contact.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service_id" value="{{ $service->id }}">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('email')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('phone')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                            <input type="text" name="company" value="{{ old('company') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @error('company')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea name="message" required rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                            Request Service
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Related Services -->
        @if($relatedServices->count() > 0)
        <div class="mt-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">Related Services</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($relatedServices as $relatedService)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                    @if($relatedService->image)
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <img src="{{ Storage::url($relatedService->image) }}" alt="{{ $relatedService->title }}" class="w-full h-full object-cover">
                    </div>
                    @else
                    <div class="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    @endif
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $relatedService->title }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ $relatedService->short_description }}</p>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-2xl font-bold text-gray-900">${{ number_format($relatedService->price, 2) }}</span>
                        </div>
                        <a href="{{ route('services.show', $relatedService->slug) }}" class="block w-full text-center bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                            View Details
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
