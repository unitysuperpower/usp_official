@extends('layouts.app-public')

@section('title', 'My Profile')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with Avatar -->
        <div class="text-center mb-12">
            <div class="relative inline-block">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-4xl font-bold shadow-2xl">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <div class="absolute bottom-2 right-2 w-8 h-8 bg-green-500 rounded-full border-4 border-white"></div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mt-6">{{ auth()->user()->name }}</h1>
            <p class="text-gray-600 mt-2">Member since {{ auth()->user()->created_at->format('F Y') }}</p>
        </div>

        <!-- Main Content -->
        <div class="space-y-8">
            <!-- Profile Information Card -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Profile Information
                    </h2>
                    <p class="text-blue-100 text-sm mt-1">Update your personal details</p>
                </div>

                <form action="{{ route('user.profile.update') }}" method="POST" class="p-8">
                    @csrf
                    @method('PATCH')

                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg animate-pulse">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-green-700 font-semibold">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Full Name
                                </span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name', auth()->user()->name) }}" 
                                   required
                                   class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-lg @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="text-red-600 text-sm mt-2 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Email Address
                                </span>
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="{{ old('email', auth()->user()->email) }}" 
                                   required
                                   class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition text-lg @error('email') border-red-500 @enderror">
                            @error('email')
                                <p class="text-red-600 text-sm mt-2 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-2xl shadow-xl hover:shadow-2xl transition transform hover:scale-105 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Card -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Change Password
                    </h2>
                    <p class="text-purple-100 text-sm mt-1">Keep your account secure with a strong password</p>
                </div>

                <form action="{{ route('user.profile.password') }}" method="POST" class="p-8">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                            <input type="password" 
                                   name="current_password" 
                                   required
                                   class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition text-lg @error('current_password') border-red-500 @enderror">
                            @error('current_password')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                            <input type="password" 
                                   name="password" 
                                   required
                                   class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition text-lg @error('password') border-red-500 @enderror">
                            @error('password')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-sm mt-2">Minimum 8 characters</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   required
                                   class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition text-lg">
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-2xl shadow-xl hover:shadow-2xl transition transform hover:scale-105 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Account Age</p>
                            <p class="text-3xl font-bold mt-1">{{ auth()->user()->created_at->diffInDays(now()) }}</p>
                            <p class="text-blue-100 text-xs mt-1">days</p>
                        </div>
                        <svg class="w-12 h-12 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Email Status</p>
                            <p class="text-2xl font-bold mt-1">Verified</p>
                            <p class="text-purple-100 text-xs mt-1">✓ Active</p>
                        </div>
                        <svg class="w-12 h-12 text-purple-200" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-pink-100 text-sm">Account Type</p>
                            <p class="text-2xl font-bold mt-1">User</p>
                            <p class="text-pink-100 text-xs mt-1">Standard</p>
                        </div>
                        <svg class="w-12 h-12 text-pink-200" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
