@extends('layouts.admin')

@section('page-title')
    Request Details
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $request->service->title }}</h2>
                <p class="text-gray-600">Request #{{ $request->id }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-semibold rounded-full 
                @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                @elseif($request->status === 'in_progress') bg-blue-100 text-blue-800
                @elseif($request->status === 'completed') bg-green-100 text-green-800
                @else bg-red-100 text-red-800
                @endif">
                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Customer Information</h3>
                <p class="text-gray-900">{{ $request->name }}</p>
                <p class="text-gray-600">{{ $request->email }}</p>
                @if($request->phone)
                    <p class="text-gray-600">{{ $request->phone }}</p>
                @endif
                @if($request->company)
                    <p class="text-gray-600">{{ $request->company }}</p>
                @endif
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Request Date</h3>
                <p class="text-gray-900">{{ $request->created_at->format('F d, Y') }}</p>
                <p class="text-gray-600">{{ $request->created_at->diffForHumans() }}</p>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Message</h3>
            <p class="text-gray-900 whitespace-pre-line">{{ $request->message }}</p>
        </div>

        @if($request->admin_notes)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Admin Notes</h3>
            <p class="text-gray-900 whitespace-pre-line">{{ $request->admin_notes }}</p>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-md p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Update Status</h3>
        <form action="{{ route('admin.requests.updateStatus', $request->id) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="pending" {{ $request->status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $request->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ $request->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $request->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                <textarea name="admin_notes" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('admin_notes', $request->admin_notes) }}</textarea>
            </div>

            <div class="flex space-x-4">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Update Request
                </button>
                <a href="{{ route('admin.requests.index') }}" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-400 transition">
                    Back to List
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
