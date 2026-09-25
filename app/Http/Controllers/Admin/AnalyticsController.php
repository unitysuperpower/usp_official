<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PageView;
use App\Models\Blog;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = (int) ($request->validate(['period' => 'nullable|integer|min:1|max:365'])['period'] ?? 30); // Default 30 days
        $startDate = Carbon::now()->subDays($period);

        // Total views
        $totalViews = PageView::where('viewed_at', '>=', $startDate)->count();
        $previousViews = PageView::where('viewed_at', '>=', Carbon::now()->subDays($period * 2))
            ->where('viewed_at', '<', $startDate)->count();
        $viewsChange = $previousViews > 0 ? (($totalViews - $previousViews) / $previousViews) * 100 : 0;

        // Unique visitors (by IP)
        $uniqueVisitors = PageView::where('viewed_at', '>=', $startDate)
            ->distinct('ip_address')->count('ip_address');

        // Page views by day (last 30 days)
        $viewsByDay = PageView::where('viewed_at', '>=', $startDate)
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('count(*) as views'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top pages
        $topPages = PageView::where('viewed_at', '>=', $startDate)
            ->select('url', DB::raw('count(*) as views'))
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        // Device breakdown
        $deviceStats = PageView::where('viewed_at', '>=', $startDate)
            ->select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->get();

        // Browser breakdown
        $browserStats = PageView::where('viewed_at', '>=', $startDate)
            ->select('browser', DB::raw('count(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->get();

        // Page type breakdown
        $pageTypeStats = PageView::where('viewed_at', '>=', $startDate)
            ->select('page_type', DB::raw('count(*) as count'))
            ->groupBy('page_type')
            ->orderByDesc('count')
            ->get();

        // Top blogs
        $topBlogs = Blog::where('is_published', true)
            ->where('published_at', '>=', $startDate)
            ->withCount(['likes', 'comments'])
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        // Top services
        $topServices = Service::where('is_active', true)
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        // Referrer stats
        $topReferrers = PageView::where('viewed_at', '>=', $startDate)
            ->whereNotNull('referrer')
            ->select('referrer', DB::raw('count(*) as count'))
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return \App\Support\ReactPage::render('admin.analytics.index', compact(
            'totalViews',
            'viewsChange',
            'uniqueVisitors',
            'viewsByDay',
            'topPages',
            'deviceStats',
            'browserStats',
            'pageTypeStats',
            'topBlogs',
            'topServices',
            'topReferrers',
            'period'
        ));
    }

    public function seo()
    {
        // SEO-specific analytics
        $totalPages = PageView::distinct('url')->count('url');
        $avgPageViews = $totalPages > 0 ? PageView::count() / $totalPages : 0;

        // Most viewed content
        $mostViewedBlogs = Blog::where('is_published', true)
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $mostViewedServices = Service::where('is_active', true)
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        // Bounce rate estimate (pages with only 1 view per session)
        $singlePageSessions = PageView::select('ip_address', DB::raw('DATE(viewed_at) as date'))
            ->groupBy('ip_address', 'date')
            ->havingRaw('count(*) = 1')
            ->get()
            ->count();

        $totalSessions = PageView::select('ip_address', DB::raw('DATE(viewed_at) as date'))
            ->groupBy('ip_address', 'date')
            ->get()
            ->count();

        $bounceRate = $totalSessions > 0 ? ($singlePageSessions / $totalSessions) * 100 : 0;

        // Average time on site (simplified)
        $avgPagesPerSession = $totalSessions > 0 ? PageView::count() / $totalSessions : 0;

        return \App\Support\ReactPage::render('admin.analytics.seo', compact(
            'totalPages',
            'avgPageViews',
            'mostViewedBlogs',
            'mostViewedServices',
            'bounceRate',
            'avgPagesPerSession'
        ));
    }
}
