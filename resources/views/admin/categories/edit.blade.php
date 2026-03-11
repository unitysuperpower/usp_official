@extends('layouts.admin')

@section('page-title')
    Edit Category
@endsection

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="bg-white rounded-xl shadow-md p-8">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description', $category->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Features/Key Points</label>
            <div id="features-container" class="space-y-3">
                @php
                    $features = old('features', $category->features ?? []);
                @endphp
                @if(count($features) > 0)
                    @foreach($features as $index => $feature)
                        <div class="flex gap-2 feature-item">
                            <input type="text" name="features[]" value="{{ $feature }}" placeholder="Enter a feature or key point" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <button type="button" onclick="removeFeature(this)" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                @else
                    <div class="flex gap-2 feature-item">
                        <input type="text" name="features[]" placeholder="Enter a feature or key point" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <button type="button" onclick="removeFeature(this)" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
            <button type="button" onclick="addFeature()" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Feature
            </button>
            <p class="text-sm text-gray-500 mt-2">Add key features or benefits of this service category (one per line)</p>
            @error('features')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Icon (Emoji or text)</label>
            <input type="text" name="icon" value="{{ old('icon', $category->icon) }}" placeholder="🚀" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            @error('icon')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                Update Category
            </button>
            <a href="{{ route('admin.categories.index') }}" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-400 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2 feature-item';
    div.innerHTML = `
        <input type="text" name="features[]" placeholder="Enter a feature or key point" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        <button type="button" onclick="removeFeature(this)" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    container.appendChild(div);
}

function removeFeature(button) {
    const container = document.getElementById('features-container');
    const items = container.querySelectorAll('.feature-item');
    if (items.length > 1) {
        button.closest('.feature-item').remove();
    } else {
        alert('At least one feature field is required');
    }
}
</script>
@endsection
