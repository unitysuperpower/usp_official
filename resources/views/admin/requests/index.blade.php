@extends('layouts.admin')

@section('page-title')
    Service Requests
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($requests as $request)
            <tr>
                <td class="px-6 py-4">
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $request->name }}</div>
                        <div class="text-sm text-gray-500">{{ $request->email }}</div>
                        @if($request->phone)
                            <div class="text-sm text-gray-500">{{ $request->phone }}</div>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">{{ $request->service->title }}</div>
                    @if($request->company)
                        <div class="text-sm text-gray-500">{{ $request->company }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                        @if($request->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($request->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($request->status === 'completed') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $request->created_at->format('M d, Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.requests.show', $request) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                    No service requests yet
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $requests->links() }}
</div>
@endsection
