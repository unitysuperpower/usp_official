@extends('layouts.admin')

@section('page-title')
    Analytics & SEO
@endsection

@section('page-subtitle')
    Website traffic and performance insights
@endsection

@section('content')
<!-- Period Filter -->
<div class="mb-6">
    <div class="bg-white rounded-xl shadow-md p-4">
        <form method="GET" action="{{ route('admin.analytics.index') }}" class="flex items-center gap-4">
            <label class="text-sm font-semibold text-gray-700">Time Period:</label>
            <select name="period" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 Days</option>
                <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 Days</option>
                <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last Year</option>
            </select>
        </form>
    </div>
</div>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Views -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-blue-100 text-sm font-semibold uppercase tracking-wider mb-2">Total Page Views</p>
                <p class="text-4xl font-bold">{{ number_format($totalViews) }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </div>
        </div>
        <div class="flex items-center text-sm">
            @if($viewsChange >= 0)
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <span class="font-semibold">+{{ number_format($viewsChange, 1) }}%</span>
            @else
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                </svg>
                <span class="font-semibold">{{ number_format($viewsChange, 1) }}%</span>
            @endif
            <span class="ml-1 text-blue-100">vs previous period</span>
        </div>
    </div>

    <!-- Unique Visitors -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-purple-100 text-sm font-semibold uppercase tracking-wider mb-2">Unique Visitors</p>
                <p class="text-4xl font-bold">{{ number_format($uniqueVisitors) }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
        <p class="text-sm text-purple-100">Tracked by unique IP addresses</p>
    </div>

    <!-- Avg Pages per Visit -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-green-100 text-sm font-semibold uppercase tracking-wider mb-2">Pages / Visit</p>
                <p class="text-4xl font-bold">{{ $uniqueVisitors > 0 ? number_format($totalViews / $uniqueVisitors, 1) : 0 }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
        <p class="text-sm text-green-100">Average engagement per user</p>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Page Views Over Time Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
            </svg>
            Page Views Over Time
        </h3>
        <canvas id="viewsChart" height="300"></canvas>
    </div>

    <!-- Device Breakdown Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            Device Types
        </h3>
        <canvas id="deviceChart" height="300"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Browser Stats Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
            </svg>
            Browser Distribution
        </h3>
        <canvas id="browserChart" height="300"></canvas>
    </div>

    <!-- Page Type Breakdown -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Page Types
        </h3>
        <canvas id="pageTypeChart" height="300"></canvas>
    </div>
</div>

<!-- Top Content Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Pages -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            Top Pages
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">URL</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Views</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($topPages as $page)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900 truncate max-w-xs">{{ $page->url }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">{{ number_format($page->views) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center text-gray-500">No data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Referrers -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            Top Referrers
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Source</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Visits</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($topReferrers as $referrer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900 truncate max-w-xs">{{ parse_url($referrer->referrer, PHP_URL_HOST) ?? 'Direct' }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">{{ number_format($referrer->count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center text-gray-500">No referrer data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Content -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Blogs -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
            </svg>
            Top Performing Blogs
        </h3>
        <div class="space-y-4">
            @forelse($topBlogs as $blog)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ $blog->title }}</h4>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                {{ number_format($blog->views) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                {{ number_format($blog->likes_count) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                {{ number_format($blog->comments_count) }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 py-8">No blog data available</p>
            @endforelse
        </div>
    </div>

    <!-- Top Services -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            Top Performing Services
        </h3>
        <div class="space-y-4">
            @forelse($topServices as $service)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ $service->title }}</h4>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span class="font-semibold">{{ number_format($service->views) }} views</span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 py-8">No service data available</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Page Views Over Time Chart
    const viewsCtx = document.getElementById('viewsChart');
    if (viewsCtx) {
        new Chart(viewsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($viewsByDay->pluck('date')) !!},
                datasets: [{
                    label: 'Page Views',
                    data: {!! json_encode($viewsByDay->pluck('views')) !!},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Device Chart
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($deviceStats->pluck('device_type')) !!},
                datasets: [{
                    data: {!! json_encode($deviceStats->pluck('count')) !!},
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Browser Chart
    const browserCtx = document.getElementById('browserChart');
    if (browserCtx) {
        new Chart(browserCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($browserStats->pluck('browser')) !!},
                datasets: [{
                    label: 'Users',
                    data: {!! json_encode($browserStats->pluck('count')) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Page Type Chart
    const pageTypeCtx = document.getElementById('pageTypeChart');
    if (pageTypeCtx) {
        new Chart(pageTypeCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($pageTypeStats->pluck('page_type')) !!},
                datasets: [{
                    data: {!! json_encode($pageTypeStats->pluck('count')) !!},
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>
@endpush
