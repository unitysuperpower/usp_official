<?php

// Permission catalog shown in Admin → Users & roles. Keys are checked with Gate/`can:` middleware.
return [
    'groups' => [
        'Workspace' => [
            'dashboard.view' => 'View the admin overview',
            'analytics.view' => 'View analytics and SEO reports',
        ],
        'Content' => [
            'services.manage' => 'Manage services and service categories',
            'blogs.manage' => 'Manage journal posts and blog categories',
        ],
        'Customer care' => [
            'requests.manage' => 'Manage leads and service requests',
            'projects.manage' => 'Manage client projects and milestones',
            'contact.manage' => 'Read and reply to contact messages',
            'chat.manage' => 'Answer support conversations',
        ],
        'Product store' => [
            'store.products' => 'Manage products, plans and releases',
            'store.orders' => 'Review orders and payments',
            'store.licenses' => 'Manage licenses and activations',
        ],
        'Education' => [
            'education.courses' => 'Create and edit courses, batches and lessons',
            'education.teach' => 'Run classrooms: sessions, attendance, assignments, quizzes, grading, announcements',
            'education.enrollments' => 'Review students and applications',
            'education.payments' => 'Verify course payments',
            'education.settings' => 'Change education payment settings',
        ],
        'Administration' => [
            'settings.email' => 'Change email delivery settings',
            'users.manage' => 'Manage user accounts and assign roles',
            'roles.manage' => 'Create and edit roles and their permissions',
        ],
    ],

    // Seeded once by the roles migration; editable afterwards (except super-admin).
    'defaults' => [
        'super-admin' => ['name' => 'Super Admin', 'description' => 'Full access, including roles and permissions. Cannot be edited.', 'permissions' => ['*']],
        'admin' => ['name' => 'Admin', 'description' => 'Manages the whole site except roles.', 'permissions' => ['*', '!roles.manage']],
        'instructor' => ['name' => 'Instructor', 'description' => 'Teaches classes and grades students.', 'permissions' => ['dashboard.view', 'education.teach']],
        'editor' => ['name' => 'Editor', 'description' => 'Writes and edits services and journal content.', 'permissions' => ['dashboard.view', 'services.manage', 'blogs.manage']],
        'support' => ['name' => 'Support / Sales', 'description' => 'Handles leads, customers, chats and orders.', 'permissions' => ['dashboard.view', 'requests.manage', 'projects.manage', 'contact.manage', 'chat.manage', 'store.orders', 'store.licenses', 'education.enrollments']],
        'learner' => ['name' => 'Learner / Customer', 'description' => 'Default role for registered accounts. No admin access.', 'permissions' => []],
    ],

    'default_role' => 'learner',
];
