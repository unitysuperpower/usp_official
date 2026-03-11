@extends('layouts.admin')

@section('page-title')
    Create Blog
@endsection

@section('page-subtitle')
    Write a new blog post
@endsection

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.blogs.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Blogs
        </a>
    </div>

    <form action="{{ route('admin.blogs.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
            <h2 class="text-2xl font-bold text-white">Create Blog Post</h2>
            <p class="text-indigo-100 text-sm mt-1">Share your knowledge with the world</p>
        </div>
        
        <div class="p-8">
        @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                    <select name="category_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g., 10 Tips for Better Web Development" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Excerpt</label>
                <textarea name="excerpt" rows="2" placeholder="Brief summary of the blog post..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">{{ old('excerpt') }}</textarea>
                @error('excerpt')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Content *</label>
                <textarea name="content" rows="12" required placeholder="Write your blog content here..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition font-mono text-sm">{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Featured Image</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-500 transition">
                    <input type="file" name="featured_image" id="featured_image" accept="image/*" class="hidden">
                    <label for="featured_image" class="cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">Click to upload image or drag and drop</p>
                        <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                    </label>
                </div>
                @error('featured_image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title') }}" placeholder="SEO title" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    @error('meta_title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tags (comma separated)</label>
                    <input type="text" name="tags" value="{{ old('tags') }}" placeholder="laravel, php, web development" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    @error('tags')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Meta Description</label>
                <textarea name="meta_description" rows="2" placeholder="SEO description" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">{{ old('meta_description') }}</textarea>
                @error('meta_description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8 flex items-center space-x-8">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <span class="ml-3 text-sm font-medium text-gray-700">Featured Blog</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }} class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <span class="ml-3 text-sm font-medium text-gray-700">Publish Now</span>
                </label>
            </div>

            <div class="flex items-center space-x-4 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                    Create Blog Post
                </button>
                <a href="{{ route('admin.blogs.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
