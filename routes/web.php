<?php

use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
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

// Robots.txt
Route::get('/robots.txt', function () {
    $content = "User-agent: *\nAllow: /\n\nSitemap: " . url('/sitemap.xml');
    return response($content, 200, ['Content-Type' => 'text/plain']);
})->name('robots.txt');

// User Chat Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('user.chat');
    Route::get('/chat/test', function() { return view('chat.test'); })->name('chat.test');
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/chat/{conversationId}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
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
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/seo', [\App\Http\Controllers\Admin\AnalyticsController::class, 'seo'])->name('analytics.seo');
    
    Route::resource('categories', ServiceCategoryController::class)->except(['show']);
    Route::resource('services', AdminServiceController::class)->except(['show']);
    Route::get('/requests', [AdminServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{request}', [AdminServiceRequestController::class, 'show'])->name('requests.show');
    Route::patch('/requests/{request}/status', [AdminServiceRequestController::class, 'updateStatus'])->name('requests.updateStatus');
    
    // Blog Management
    Route::resource('blog-categories', BlogCategoryController::class)->except(['show']);
    Route::resource('blogs', AdminBlogController::class)->except(['show']);
    
    // Contact Messages
    Route::resource('contact-messages', ContactMessageController::class)->only(['index', 'show', 'update', 'destroy'])->parameters([
        'contact-messages' => 'message'
    ]);
    Route::post('contact-messages/{message}/reply', [ContactMessageController::class, 'reply'])->name('contact-messages.reply');
    
    // Live Chat
    Route::get('chat', [\App\Http\Controllers\Admin\ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{id}', [\App\Http\Controllers\Admin\ChatController::class, 'show'])->name('chat.show');
    Route::post('chat/send', [\App\Http\Controllers\Admin\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('chat/{id}/messages', [\App\Http\Controllers\Admin\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::patch('chat/{id}/status', [\App\Http\Controllers\Admin\ChatController::class, 'updateStatus'])->name('chat.status');
    
    // Admin Profile
    Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
    
    // Analytics & SEO
    Route::get('analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/seo', [\App\Http\Controllers\Admin\AnalyticsController::class, 'seo'])->name('analytics.seo');
});

// User Dashboard
Route::get('dashboard', function () {
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // User Profile
    Route::get('profile', [\App\Http\Controllers\User\ProfileController::class, 'edit'])->name('user.profile.edit');
    Route::patch('profile', [\App\Http\Controllers\User\ProfileController::class, 'update'])->name('user.profile.update');
    Route::patch('profile/password', [\App\Http\Controllers\User\ProfileController::class, 'updatePassword'])->name('user.profile.password');
    
    // Old settings routes (Livewire - keep for compatibility)
    Route::redirect('settings', 'profile');
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
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
