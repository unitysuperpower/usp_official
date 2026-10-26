<?php

namespace App\Support;

use App\Models\User;

class StaffHome
{
    // First admin page each permission opens, in sidebar order.
    private const PAGES = [
        'dashboard.view' => '/admin/dashboard',
        'education.teach' => '/admin/education/overview',
        'education.courses' => '/admin/education/courses',
        'education.enrollments' => '/admin/education/enrollments',
        'education.payments' => '/admin/education/payments',
        'store.products' => '/admin/store/products',
        'store.orders' => '/admin/store/orders',
        'store.licenses' => '/admin/store/licenses',
        'requests.manage' => '/admin/requests',
        'projects.manage' => '/admin/projects',
        'contact.manage' => '/admin/contact-messages',
        'chat.manage' => '/admin/chat',
        'services.manage' => '/admin/services',
        'blogs.manage' => '/admin/blogs',
        'analytics.view' => '/admin/analytics',
        'users.manage' => '/admin/users',
        'roles.manage' => '/admin/roles',
        'settings.email' => '/admin/settings/email',
        'education.settings' => '/admin/education/settings',
    ];

    public static function url(User $user): string
    {
        foreach (self::PAGES as $permission => $url) {
            if ($user->hasPermission($permission)) {
                return $url;
            }
        }

        return '/admin/profile';
    }
}
