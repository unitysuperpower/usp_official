

<?php $__env->startSection('page-title'); ?>
    Contact Message - <?php echo e($message->subject); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="<?php echo e(route('admin.contact-messages.index')); ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Messages
        </a>
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">Contact Message</h1>
            <form action="<?php echo e(route('admin.contact-messages.destroy', $message)); ?>" method="POST" 
                  onsubmit="return confirm('Are you sure you want to delete this message?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Message
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Message Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Message Details -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white text-2xl font-bold">
                                <?php echo e(strtoupper(substr($message->name, 0, 1))); ?>

                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo e($message->name); ?></h2>
                                <p class="text-indigo-100"><?php echo e($message->email); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->phone): ?>
                                    <p class="text-indigo-100"><?php echo e($message->phone); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-500',
                                'read' => 'bg-blue-500',
                                'replied' => 'bg-green-500',
                                'archived' => 'bg-gray-500'
                            ];
                        ?>
                        <span class="px-4 py-2 <?php echo e($statusColors[$message->status]); ?> text-white text-sm font-semibold rounded-full">
                            <?php echo e(ucfirst($message->status)); ?>

                        </span>
                    </div>
                </div>

                <!-- Subject -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-xl font-bold text-gray-900"><?php echo e($message->subject); ?></h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Received on <?php echo e($message->created_at->format('F d, Y \a\t h:i A')); ?>

                    </p>
                </div>

                <!-- Message Body -->
                <div class="px-6 py-6">
                    <div class="prose max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($message->message); ?></p>
                    </div>
                </div>

                <!-- Admin Notes (if any) -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->admin_notes): ?>
                    <div class="px-6 py-4 bg-yellow-50 border-t border-yellow-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            Admin Notes
                        </h4>
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($message->admin_notes); ?></p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Reply Info -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->status === 'replied' && $message->repliedBy): ?>
                    <div class="px-6 py-4 bg-green-50 border-t border-green-200">
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-gray-700">
                                Replied by <strong><?php echo e($message->repliedBy->name); ?></strong> on <?php echo e($message->updated_at->format('F d, Y \a\t h:i A')); ?>

                            </span>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <!-- Reply Form -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        Send Reply
                    </h3>
                </div>

                <form action="<?php echo e(route('admin.contact-messages.reply', $message)); ?>" method="POST" class="p-6">
                    <?php echo csrf_field(); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('reply_success')): ?>
                        <div class="mb-4 bg-green-50 border-2 border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><?php echo e(session('reply_success')); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <!-- Quick Reply Templates -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quick Templates</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <button type="button" onclick="insertTemplate('thank_you')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition">
                                Thank You
                            </button>
                            <button type="button" onclick="insertTemplate('more_info')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition">
                                Request Info
                            </button>
                            <button type="button" onclick="insertTemplate('meeting')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition">
                                Schedule Meeting
                            </button>
                        </div>
                    </div>

                    <!-- Reply Subject -->
                    <div class="mb-4">
                        <label for="reply_subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" id="reply_subject" name="subject" value="Re: <?php echo e($message->subject); ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <!-- Reply Message -->
                    <div class="mb-4">
                        <label for="reply_message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="reply_message" name="reply_message" rows="8" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                  placeholder="Type your reply here..."><?php echo e(old('reply_message', "Hi {$message->name},\n\nThank you for contacting us.\n\n")); ?></textarea>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['reply_message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Send Options -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="mark_as_replied" value="1" checked class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm text-gray-700">Mark as replied</span>
                        </label>

                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold rounded-lg shadow-lg transition inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Status Update -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Update Status</h3>
                <form action="<?php echo e(route('admin.contact-messages.update', $message)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="pending" <?php echo e($message->status === 'pending' ? 'selected' : ''); ?>>Pending</option>
                            <option value="read" <?php echo e($message->status === 'read' ? 'selected' : ''); ?>>Read</option>
                            <option value="replied" <?php echo e($message->status === 'replied' ? 'selected' : ''); ?>>Replied</option>
                            <option value="archived" <?php echo e($message->status === 'archived' ? 'selected' : ''); ?>>Archived</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                        <textarea id="admin_notes" name="admin_notes" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Add internal notes about this message..."><?php echo e(old('admin_notes', $message->admin_notes)); ?></textarea>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                        Update Message
                    </button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="mailto:<?php echo e($message->email); ?>" class="w-full px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-lg transition inline-flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Reply via Email
                    </a>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->phone): ?>
                        <a href="tel:<?php echo e($message->phone); ?>" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition inline-flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            Call
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Message Info -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Message Info</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Received</dt>
                        <dd class="text-sm text-gray-900 mt-1"><?php echo e($message->created_at->format('M d, Y h:i A')); ?></dd>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->read_at): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">First Read</dt>
                            <dd class="text-sm text-gray-900 mt-1"><?php echo e($message->read_at->format('M d, Y h:i A')); ?></dd>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="text-sm text-gray-900 mt-1"><?php echo e($message->updated_at->format('M d, Y h:i A')); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
function insertTemplate(templateType) {
    const textarea = document.getElementById('reply_message');
    const customerName = '<?php echo e($message->name); ?>';
    const subject = '<?php echo e($message->subject); ?>';
    const adminName = '<?php echo e(auth()->user()->name); ?>';
    const appName = '<?php echo e(config("app.name")); ?>';
    const phone = '<?php echo e($message->phone ?? "[Your Phone]"); ?>';
    
    const templates = {
        thank_you: `Hi ${customerName},

Thank you for reaching out to us. We appreciate your interest in our services.

We have received your message regarding "${subject}" and our team will review it carefully. We will get back to you with a detailed response within 24-48 hours.

If you have any urgent questions in the meantime, please don't hesitate to contact us directly.

Best regards,
${adminName}
${appName}`,

        more_info: `Hi ${customerName},

Thank you for contacting us about "${subject}".

To better assist you, we would need some additional information:

1. [Add specific question here]
2. [Add specific question here]
3. [Add specific question here]

Once we have these details, we'll be able to provide you with a comprehensive solution tailored to your needs.

Looking forward to hearing from you.

Best regards,
${adminName}
${appName}`,

        meeting: `Hi ${customerName},

Thank you for your inquiry about "${subject}".

We would love to schedule a meeting to discuss your requirements in detail. This will help us understand your needs better and provide you with the best possible solution.

Please let us know your availability for a meeting, and we'll arrange a convenient time for both of us. You can also reach us at ${phone}.

We look forward to speaking with you soon.

Best regards,
${adminName}
${appName}`
    };
    
    if (templates[templateType]) {
        textarea.value = templates[templateType];
        textarea.focus();
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\usp_official\resources\views\admin\contact-messages\show.blade.php ENDPATH**/ ?>