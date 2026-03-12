

<?php $__env->startSection('page-title'); ?>
    Request Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl">
    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900"><?php echo e($request->service->title); ?></h2>
                <p class="text-gray-600">Request #<?php echo e($request->id); ?></p>
            </div>
            <span class="px-4 py-2 text-sm font-semibold rounded-full 
                <?php if($request->status === 'pending'): ?> bg-yellow-100 text-yellow-800
                <?php elseif($request->status === 'in_progress'): ?> bg-blue-100 text-blue-800
                <?php elseif($request->status === 'completed'): ?> bg-green-100 text-green-800
                <?php else: ?> bg-red-100 text-red-800
                <?php endif; ?>">
                <?php echo e(ucfirst(str_replace('_', ' ', $request->status))); ?>

            </span>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Customer Information</h3>
                <p class="text-gray-900"><?php echo e($request->name); ?></p>
                <p class="text-gray-600"><?php echo e($request->email); ?></p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($request->phone): ?>
                    <p class="text-gray-600"><?php echo e($request->phone); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($request->company): ?>
                    <p class="text-gray-600"><?php echo e($request->company); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Request Date</h3>
                <p class="text-gray-900"><?php echo e($request->created_at->format('F d, Y')); ?></p>
                <p class="text-gray-600"><?php echo e($request->created_at->diffForHumans()); ?></p>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Message</h3>
            <p class="text-gray-900 whitespace-pre-line"><?php echo e($request->message); ?></p>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($request->admin_notes): ?>
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Admin Notes</h3>
            <p class="text-gray-900 whitespace-pre-line"><?php echo e($request->admin_notes); ?></p>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-md p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Update Status</h3>
        <form action="<?php echo e(route('admin.requests.updateStatus', $request->id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="pending" <?php echo e($request->status === 'pending' ? 'selected' : ''); ?>>Pending</option>
                    <option value="in_progress" <?php echo e($request->status === 'in_progress' ? 'selected' : ''); ?>>In Progress</option>
                    <option value="completed" <?php echo e($request->status === 'completed' ? 'selected' : ''); ?>>Completed</option>
                    <option value="cancelled" <?php echo e($request->status === 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                <textarea name="admin_notes" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"><?php echo e(old('admin_notes', $request->admin_notes)); ?></textarea>
            </div>

            <div class="flex space-x-4">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Update Request
                </button>
                <a href="<?php echo e(route('admin.requests.index')); ?>" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-400 transition">
                    Back to List
                </a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\usp_official\resources\views\admin\requests\show.blade.php ENDPATH**/ ?>