<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Log in') }} - {{ config('app.name', 'TechSolution') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Left Side - Login Form -->
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <!-- Logo and Back Link -->
                <div>
                    <a href="{{ route('home') }}" class="flex items-center justify-center mb-2">
                        <span class="text-3xl font-bold text-indigo-600">TechSolution</span>
                    </a>
                    <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                        {{ __('Welcome back') }}
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        {{ __('Log in to your account to continue') }}
                    </p>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-6">
                    @csrf

                    <div class="space-y-5">
                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                {{ __('Email address') }}
                            </label>
                            <div class="mt-1">
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="you@example.com"
                                >
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                {{ __('Password') }}
                            </label>
                            <div class="mt-1">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="••••••••"
                                >
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                {{ old('remember') ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                            >
                            <label for="remember" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                                {{ __('Remember me') }}
                            </label>
                        </div>

                        @if (Route::has('password.request'))
                            <div class="text-sm">
                                <a href="{{ route('password.request') }}" class="font-medium text-indigo-600 hover:text-indigo-500 transition">
                                    {{ __('Forgot password?') }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition transform hover:scale-105"
                        >
                            {{ __('Log in') }}
                        </button>
                    </div>

                    @if (Route::has('register'))
                        <div class="text-center">
                            <p class="text-sm text-gray-600">
                                {{ __("Don't have an account?") }}
                                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                                    {{ __('Sign up') }}
                                </a>
                            </p>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Right Side - Image/Gradient -->
        <div class="hidden lg:block relative w-0 flex-1">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 flex items-center justify-center p-12">
                <div class="max-w-lg text-white">
                    <h2 class="text-4xl font-bold mb-6">
                        {{ __('Welcome to TechSolution') }}
                    </h2>
                    <p class="text-xl text-indigo-100 mb-8">
                        {{ __('Transform your business with innovative technology solutions. Join thousands of satisfied clients.') }}
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-green-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-indigo-100">{{ __('Access premium tech services') }}</p>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-green-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-indigo-100">{{ __('24/7 dedicated support team') }}</p>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-green-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-indigo-100">{{ __('Real-time project tracking') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
