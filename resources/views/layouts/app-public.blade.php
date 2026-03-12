<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! SEO::generate() !!}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <span class="text-2xl font-bold text-indigo-600">USP Technology Company</span>
                    </a>
                    <div class="hidden md:ml-10 md:flex md:space-x-8">
                        <a href="{{ route('home') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('home') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Home
                        </a>
                        <a href="{{ route('services.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('services.*') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Services
                        </a>
                        <a href="{{ route('blogs.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('blogs.*') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Blog
                        </a>
                        <a href="{{ route('contact') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('contact') ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Contact
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="hidden md:block text-sm font-medium text-gray-700 hover:text-indigo-600">
                                Admin Panel
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="hidden md:block text-sm font-medium text-gray-700 hover:text-indigo-600">
                                Dashboard
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-gray-700 hover:text-indigo-600">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-2xl font-bold text-indigo-400">USP Technology Company</h3>
                    <p class="mt-4 text-gray-400">
                        Professional technology solutions for businesses of all sizes. We deliver excellence in every project.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition">Home</a></li>
                        <li><a href="{{ route('services.index') }}" class="text-gray-400 hover:text-white transition">Services</a></li>
                        <li><a href="{{ route('blogs.index') }}" class="text-gray-400 hover:text-white transition">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Email: info@usp.com.pk</li>
                        <li>Phone: +1 (202) 960-7524</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} USP Technology Company. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating Chat Button -->
    @auth
        <a href="{{ route('user.chat') }}" class="fixed bottom-6 right-6 w-16 h-16 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 hover:scale-110 z-50 group">
            <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
            @php
                $userUnreadChat = auth()->check() ? \App\Models\ChatMessage::whereHas('conversation', function($q) {
                    $q->where('user_id', auth()->id());
                })->where('is_admin', true)->where('is_read', false)->count() : 0;
            @endphp
            @if($userUnreadChat > 0)
                <span class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">{{ $userUnreadChat }}</span>
            @endif
        </a>
    @endauth

    @livewireScripts
</body>
</html>
