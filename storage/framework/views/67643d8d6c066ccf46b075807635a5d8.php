

<?php $__env->startSection('page-title'); ?>
    Dashboard
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-subtitle'); ?>
    Welcome back! Here's your overview.
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Enhanced Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total Services Card -->
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500 hover:shadow-xl transition transform hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Total Services</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo e($stats['total_services']); ?></p>
                <p class="text-xs text-indigo-600 mt-2 font-medium">Active offerings</p>
            </div>
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl p-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Categories Card -->
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-xl transition transform hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Categories</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo e($stats['total_categories']); ?></p>
                <p class="text-xs text-purple-600 mt-2 font-medium">Service groups</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Requests Card -->
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-xl transition transform hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Total Requests</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo e($stats['total_requests']); ?></p>
                <p class="text-xs text-blue-600 mt-2 font-medium">All inquiries</p>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Pending Requests Card -->
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-xl transition transform hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Pending</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo e($stats['pending_requests']); ?></p>
                <p class="text-xs text-yellow-600 mt-2 font-medium">Awaiting action</p>
            </div>
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Users Card -->
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-xl transition transform hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Total Users</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo e($stats['total_users']); ?></p>
                <p class="text-xs text-green-600 mt-2 font-medium">Registered</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Recent Requests -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">Recent Service Requests</h2>
                <p class="text-indigo-100 text-sm mt-1">Latest inquiries from customers</p>
            </div>
            <a href="<?php echo e(route('admin.requests.index')); ?>" class="bg-white text-indigo-600 px-4 py-2 rounded-lg font-medium hover:bg-indigo-50 transition flex items-center space-x-2">
                <span>View All</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Service</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                <?php echo e(strtoupper(substr($request->name, 0, 2))); ?>

                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-semibold text-gray-900"><?php echo e($request->name); ?></div>
                                <div class="text-xs text-gray-500"><?php echo e($request->email); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo e($request->service ? $request->service->title : 'N/A'); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($request->service && $request->service->category ? $request->service->category->name : 'N/A'); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm
                            <?php if($request->status === 'pending'): ?> bg-yellow-100 text-yellow-800 border border-yellow-300
                            <?php elseif($request->status === 'in_progress'): ?> bg-blue-100 text-blue-800 border border-blue-300
                            <?php elseif($request->status === 'completed'): ?> bg-green-100 text-green-800 border border-green-300
                            <?php else: ?> bg-red-100 text-red-800 border border-red-300
                            <?php endif; ?>">
                            <?php echo e(ucfirst(str_replace('_', ' ', $request->status))); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 font-medium"><?php echo e($request->created_at->format('M d, Y')); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($request->created_at->diffForHumans()); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="<?php echo e(route('admin.requests.show', $request)); ?>" class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg font-medium transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-4 text-gray-500 font-medium">No requests yet</p>
                        <p class="text-sm text-gray-400 mt-1">Service requests will appear here</p>
                    </td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\usp_official\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>