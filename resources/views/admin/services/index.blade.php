@extends('layouts.admin')

@section('page-title')
    Services
@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.services.create') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition inline-flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Service
    </a>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($services as $service)
            <tr>
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        @if($service->image)
                            <img src="{{ Storage::url($service->image) }}" alt="{{ $service->title }}" class="w-12 h-12 rounded object-cover mr-3" loading="lazy">
                        @endif
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $service->title }}</div>
                            @if($service->is_featured)
                                <span class="text-xs text-yellow-600">⭐ Featured</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ $service->category->name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ number_format($service->price, 2) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                    <a href="{{ route('admin.services.edit', $service) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                    No services yet. Create your first service!
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
