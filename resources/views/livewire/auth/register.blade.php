<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Create Account') }} - {{ config('app.name', 'TechSolution') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Left Side - Image/Gradient -->
        <div class="hidden lg:block relative w-0 flex-1">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-indigo-600 to-purple-800 flex items-center justify-center p-12">
                <div class="max-w-lg text-white">
                    <h2 class="text-4xl font-bold mb-6">
                        {{ __('Start Your Journey Today') }}
                    </h2>
                    <p class="text-xl text-purple-100 mb-8">
                        {{ __('Join our platform and unlock access to cutting-edge technology solutions tailored for your business needs.') }}
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-green-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-purple-100">{{ __('Free account setup') }}</p>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-green-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-purple-100">{{ __('Instant access to all services') }}</p>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-6 w-6 text-green-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-purple-100">{{ __('Expert consultation included') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <!-- Logo and Title -->
                <div>
                    <a href="{{ route('home') }}" class="flex items-center justify-center mb-2">
                        <span class="text-3xl font-bold text-indigo-600">TechSolution</span>
                    </a>
                    <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                        {{ __('Create your account') }}
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        {{ __('Get started with your free account today') }}
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

                <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-6">
                    @csrf

                    <div class="space-y-5">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                {{ __('Full Name') }}
                            </label>
                            <div class="mt-1">
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    autocomplete="name"
                                    required
                                    autofocus
                                    class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="John Doe"
                                >
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

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
                                    autocomplete="new-password"
                                    required
                                    class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="••••••••"
                                >
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                {{ __('Confirm Password') }}
                            </label>
                            <div class="mt-1">
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="••••••••"
                                >
                                @error('password_confirmation')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                id="terms"
                                name="terms"
                                type="checkbox"
                                required
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="text-gray-600 cursor-pointer">
                                {{ __('I agree to the') }}
                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">{{ __('Terms of Service') }}</a>
                                {{ __('and') }}
                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">{{ __('Privacy Policy') }}</a>
                            </label>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition transform hover:scale-105"
                        >
                            {{ __('Create account') }}
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            {{ __('Already have an account?') }}
                            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                                {{ __('Log in') }}
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
