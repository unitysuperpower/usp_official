@extends('layouts.admin')

@section('page-title')
    Service Categories
@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.categories.create') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition inline-flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Category
    </a>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Features</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Services</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($categories as $category)
            <tr>
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        @if($category->icon)
                            <span class="text-2xl mr-3">{{ $category->icon }}</span>
                        @endif
                        <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-600 max-w-xs">{{ Str::limit($category->description, 100) }}</div>
                </td>
                <td class="px-6 py-4">
                    @if($category->features && count($category->features) > 0)
                        <div class="text-sm">
                            <ul class="list-disc list-inside space-y-1 text-gray-700">
                                @foreach(array_slice($category->features, 0, 3) as $feature)
                                    <li class="truncate max-w-xs">{{ $feature }}</li>
                                @endforeach
                            </ul>
                            @if(count($category->features) > 3)
                                <span class="text-xs text-indigo-600 mt-1 inline-block">+{{ count($category->features) - 3 }} more</span>
                            @endif
                        </div>
                    @else
                        <span class="text-sm text-gray-400 italic">No features</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ $category->services_count }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-indigo-600 hover:text-indigo-900 font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    No categories yet. Create your first category!
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
