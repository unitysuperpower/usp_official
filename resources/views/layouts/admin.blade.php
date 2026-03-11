<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Panel - {{ config('app.name', 'TechSolution') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Modern Sidebar -->
        <aside class="w-72 bg-gray-900 text-white flex-shrink-0 shadow-2xl border-r border-gray-800 relative">
            <!-- Logo Section with Animated Gradient -->
            <div class="p-6 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 relative overflow-hidden">
                <div class="absolute inset-0 bg-black opacity-20"></div>
                <div class="relative flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg transform hover:rotate-12 transition-transform duration-300">
                        <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white tracking-tight">TechSolution</h2>
                        <p class="text-xs text-indigo-200 font-medium">Admin Dashboard</p>
                    </div>
                </div>
            </div>

            <!-- Navigation with Modern Style -->
            <div class="px-4 py-2">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4 mt-6 mb-3">Main Menu</p>
            </div>
            <nav class="px-3 space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.dashboard') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Dashboard</span>
                </a>

                <!-- Categories -->
                <a href="{{ route('admin.categories.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.categories.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.categories.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Categories</span>
                </a>

                <!-- Services -->
                <a href="{{ route('admin.services.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.services.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.services.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Services</span>
                </a>

                <!-- Requests -->
                <a href="{{ route('admin.requests.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 relative {{ request()->routeIs('admin.requests.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.requests.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Requests</span>
                </a>
            </nav>

            <!-- Analytics Section -->
            <div class="px-4 py-2">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4 mt-6 mb-3">Analytics & SEO</p>
            </div>
            <nav class="px-3 space-y-1">
                <!-- Analytics -->
                <a href="{{ route('admin.analytics.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.analytics.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.analytics.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Analytics</span>
                </a>
            </nav>

            <!-- Blog Section -->
            <div class="px-4 py-2">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4 mt-6 mb-3">Blog Management</p>
            </div>
            <nav class="px-3 space-y-1">
                <!-- Blog Categories -->
                <a href="{{ route('admin.blog-categories.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.blog-categories.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.blog-categories.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Blog Categories</span>
                </a>

                <!-- Blogs -->
                <a href="{{ route('admin.blogs.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.blogs.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.blogs.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Blogs</span>
                </a>
            </nav>

            <!-- Communication Section -->
            <div class="px-4 py-2">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4 mt-6 mb-3">Communication</p>
            </div>
            <nav class="px-3 space-y-1">
                <!-- Live Chat -->
                <a href="{{ route('admin.chat.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 relative {{ request()->routeIs('admin.chat.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.chat.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Live Chat</span>
                    @php
                        $chatUnread = \App\Models\ChatMessage::fromUser()->unread()->count();
                    @endphp
                    @if($chatUnread > 0)
                        <span class="ml-auto px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full">{{ $chatUnread }}</span>
                    @endif
                </a>
                
                <!-- Contact Messages -->
                <a href="{{ route('admin.contact-messages.index') }}" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 relative {{ request()->routeIs('admin.contact-messages.*') ? 'bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.contact-messages.*') ? 'bg-white/20' : 'bg-gray-800 group-hover:bg-gray-700' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">Contact Messages</span>
                    @php
                        $unreadCount = \App\Models\ContactMessage::unread()->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span class="ml-auto px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full">{{ $unreadCount }}</span>
                    @endif
                </a>
            </nav>

            <!-- Quick Actions Section -->
            <div class="px-4 py-2">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider px-4 mt-6 mb-3">Quick Actions</p>
            </div>
            <nav class="px-3 space-y-1">
                <!-- View Site -->
                <a href="{{ route('home') }}" target="_blank" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-300 text-gray-400 hover:bg-gray-800 hover:text-white">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-800 group-hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </div>
                    <span class="ml-3 font-semibold">View Website</span>
                </a>
            </nav>

            <!-- Modern User Profile Card at Bottom -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-800 border-t border-gray-700">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center ring-4 ring-white/20">
                                <span class="text-indigo-600 font-bold text-base">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-gray-800"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-indigo-200 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <button class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Enhanced Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-8 py-5">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-sm text-gray-500 mt-1">@yield('page-subtitle', 'Welcome back! Here\'s what\'s happening.')</p>
                    </div>
                    <div class="flex items-center space-x-6">
                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <!-- Settings -->
                        <a href="{{ route('admin.profile.edit') }}" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </a>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="flex items-center space-x-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8 bg-gray-50">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-4 rounded-r-lg shadow-sm flex items-start">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="font-semibold">Success!</p>
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-4 rounded-r-lg shadow-sm flex items-start">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="font-semibold">Error!</p>
                            <p class="text-sm">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
