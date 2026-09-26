<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatWidgetController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServiceController;
use App\Models\Project;
use App\Support\ReactPage;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/service-category/{slug}', [ServiceController::class, 'category'])->name('services.category');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

// Contact Routes
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:3,1')->name('contact.store');

// Search discovery endpoints are live; do not place static copies in public/.
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('seo.sitemap');
Route::get('/sitemap-pages.xml', [SeoController::class, 'pages'])->name('seo.pages');
Route::get('/sitemap-{section}-{page}.xml', [SeoController::class, 'section'])
    ->where('section', 'services|blogs|service-categories|blog-categories')
    ->where('page', '[1-9][0-9]{0,4}')->name('seo.section');

// User Chat Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('user.chat');
    Route::redirect('/chat/test', '/chat')->name('chat.test');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/chat/{conversationId}/messages', [ChatController::class, 'getMessages'])->name('chat.messages');
});

// Blog Routes
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');
Route::get('/blog-category/{slug}', [BlogController::class, 'category'])->name('blogs.category');
Route::post('/blogs/{blog}/like', [BlogController::class, 'like'])->name('blogs.like');
Route::post('/blogs/{blog}/comment', [BlogController::class, 'comment'])->name('blogs.comment');

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/seo', [AnalyticsController::class, 'seo'])->name('analytics.seo');

    Route::resource('categories', ServiceCategoryController::class)->except(['show']);
    Route::resource('services', AdminServiceController::class)->except(['show']);
    Route::get('projects', [App\Http\Controllers\Admin\ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project}', [App\Http\Controllers\Admin\ProjectController::class, 'show'])->name('projects.show');
    Route::patch('projects/{project}', [App\Http\Controllers\Admin\ProjectController::class, 'update'])->name('projects.update');
    Route::post('projects/{project}/milestones', [App\Http\Controllers\Admin\ProjectController::class, 'storeMilestone'])->name('projects.milestones.store');
    Route::patch('projects/{project}/milestones/{milestone}', [App\Http\Controllers\Admin\ProjectController::class, 'updateMilestone'])->name('projects.milestones.update');
    Route::patch('requests/{serviceRequest}/lead', [AdminServiceRequestController::class, 'updateLead'])->name('requests.lead');
    Route::post('requests/{serviceRequest}/project', [AdminServiceRequestController::class, 'convert'])->name('requests.convert');
    Route::get('/requests', [AdminServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{request}', [AdminServiceRequestController::class, 'show'])->name('requests.show');
    Route::patch('/requests/{serviceRequest}/status', [AdminServiceRequestController::class, 'updateStatus'])->name('requests.updateStatus');

    // Blog Management
    Route::resource('blog-categories', BlogCategoryController::class)->except(['show']);
    Route::resource('blogs', AdminBlogController::class)->except(['show']);

    // Contact Messages
    Route::resource('contact-messages', ContactMessageController::class)->only(['index', 'show', 'update', 'destroy'])->parameters([
        'contact-messages' => 'message',
    ]);
    Route::post('contact-messages/{message}/reply', [ContactMessageController::class, 'reply'])->name('contact-messages.reply');

    // Live Chat
    Route::get('chat', [App\Http\Controllers\Admin\ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{id}', [App\Http\Controllers\Admin\ChatController::class, 'show'])->name('chat.show');
    Route::post('chat/send', [App\Http\Controllers\Admin\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('chat/{id}/messages', [App\Http\Controllers\Admin\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::patch('chat/{id}/status', [App\Http\Controllers\Admin\ChatController::class, 'updateStatus'])->name('chat.status');

    // Admin Profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

});

// User Dashboard
Route::get('dashboard', function () {
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    return ReactPage::render('dashboard', [
        'requests' => auth()->user()->serviceRequests()->select(['id', 'user_id', 'service_id', 'status', 'message', 'created_at'])->with('service.category')->latest()->paginate(15),
        'stats' => [
            'projects' => Project::where('customer_id', auth()->id())->count(),
            'total_requests' => auth()->user()->serviceRequests()->count(),
            'pending_requests' => auth()->user()->serviceRequests()->where('status', 'pending')->count(),
            'completed_requests' => auth()->user()->serviceRequests()->where('status', 'completed')->count(),
        ],
    ]);
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::delete('profile', [App\Http\Controllers\User\ProfileController::class, 'destroy'])->name('user.profile.destroy');

    // User Profile
    Route::get('profile', [App\Http\Controllers\User\ProfileController::class, 'edit'])->name('user.profile.edit');
    Route::patch('profile', [App\Http\Controllers\User\ProfileController::class, 'update'])->name('user.profile.update');
    Route::patch('profile/password', [App\Http\Controllers\User\ProfileController::class, 'updatePassword'])->name('user.profile.password');

    // Keep existing settings URLs available after the React migration.
    Route::redirect('settings', 'profile');
    Route::get('settings/profile', fn () => ReactPage::render('user.profile.edit'))->name('settings.profile');
    Route::redirect('settings/password', '/profile')->name('user-password.edit');
    Route::get('settings/appearance', fn () => ReactPage::render('settings.appearance'))->name('appearance.edit');

    Route::get('settings/two-factor', function () {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), 403);

        return ReactPage::render('settings.two-factor', [
            'enabled' => auth()->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    })
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Floating chat and browser notification subscriptions.
Route::middleware(['auth'])->group(function () {
    Route::get('/chat/widget', [ChatWidgetController::class, 'index']);
    Route::post('/chat/widget/start', [ChatWidgetController::class, 'start'])->middleware('throttle:20,1');
    Route::post('/chat/widget/read', [ChatWidgetController::class, 'read']);
    Route::get('/notifications/push', [PushSubscriptionController::class, 'config']);
    Route::post('/notifications/push', [PushSubscriptionController::class, 'store'])->middleware('throttle:20,1');
    Route::delete('/notifications/push', [PushSubscriptionController::class, 'destroy']);
});

// Authenticated project collaboration; every action checks project ownership.
Route::middleware('auth')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('projects/{project}/milestones/{milestone}/approval', [ProjectController::class, 'approval'])->name('projects.approval');
    Route::post('projects/{project}/updates', [ProjectController::class, 'storeUpdate'])->middleware('throttle:30,1')->name('projects.updates');
    Route::post('projects/{project}/files', [ProjectController::class, 'upload'])->middleware('throttle:10,1')->name('projects.files.upload');
    Route::get('projects/{project}/files/{file}', [ProjectController::class, 'download'])->name('projects.files.download');
    Route::delete('projects/{project}/files/{file}', [ProjectController::class, 'deleteFile'])->name('projects.files.delete');
});
