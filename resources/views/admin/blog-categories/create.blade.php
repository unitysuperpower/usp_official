@extends('layouts.admin')

@section('page-title')
    Create Blog Category
@endsection

@section('page-subtitle')
    Add a new blog category
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.blog-categories.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Categories
        </a>
    </div>

    <form action="{{ route('admin.blog-categories.store') }}" method="POST" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
            <h2 class="text-2xl font-bold text-white">Create Blog Category</h2>
            <p class="text-indigo-100 text-sm mt-1">Fill in the category details</p>
        </div>
        
        <div class="p-8">
        @csrf

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., Web Development" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" placeholder="Brief description of this category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Color</label>
                <input type="color" name="color" value="{{ old('color', '#6366f1') }}" class="h-12 w-24 rounded-lg border border-gray-300 cursor-pointer">
                @error('color')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <span class="ml-3 text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex items-center space-x-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                    Create Category
                </button>
                <a href="{{ route('admin.blog-categories.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
