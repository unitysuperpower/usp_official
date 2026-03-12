

<?php $__env->startSection('page-title'); ?>
    Chat with <?php echo e($conversation->user->name); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <a href="<?php echo e(route('admin.chat.index')); ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to All Chats
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Chat Area -->
        <div class="lg:col-span-3">
            <!-- Chat Header -->
            <div class="bg-white rounded-t-3xl shadow-xl p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                            <?php echo e(strtoupper(substr($conversation->user->name, 0, 1))); ?>

                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?php echo e($conversation->user->name); ?></h2>
                            <p class="text-sm text-gray-600"><?php echo e($conversation->user->email); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                                'archived' => 'bg-yellow-100 text-yellow-800'
                            ];
                        ?>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo e($statusColors[$conversation->status]); ?>">
                            <?php echo e(ucfirst($conversation->status)); ?>

                        </span>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="bg-white shadow-xl" style="height: 500px;">
                <div id="chat-messages" class="h-full overflow-y-auto p-6 space-y-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $conversation->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->is_admin): ?>
                            <!-- Admin Message (You) -->
                            <div class="flex items-start gap-3 justify-end">
                                <div class="flex-1 max-w-lg flex flex-col items-end">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-2xl rounded-tr-none px-4 py-3">
                                        <p><?php echo e($message->message); ?></p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 mr-2">
                                        <?php echo e($message->user->name ?? 'Admin'); ?> • <?php echo e($message->created_at->format('h:i A')); ?>

                                    </p>
                                </div>
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                    <?php echo e(substr($message->user->name ?? 'A', 0, 1)); ?>

                                </div>
                            </div>
                        <?php else: ?>
                            <!-- User Message -->
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                    <?php echo e(substr($conversation->user->name, 0, 1)); ?>

                                </div>
                                <div class="flex-1 max-w-lg">
                                    <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3">
                                        <p class="text-gray-800"><?php echo e($message->message); ?></p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 ml-2">
                                        <?php echo e($message->created_at->format('h:i A')); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message->is_read): ?>
                                            <svg class="w-3 h-3 inline text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                </svg>
                                <p class="text-gray-600 font-medium">No messages yet</p>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Message Input -->
            <div class="bg-white rounded-b-3xl shadow-xl p-4 border-t border-gray-200">
                <form id="admin-chat-form" class="flex gap-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="conversation-id" value="<?php echo e($conversation->id); ?>">
                    <input type="text" 
                           id="admin-message-input" 
                           placeholder="Type your response..." 
                           class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           required
                           maxlength="1000">
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg transition inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Send
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Chat Info -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Chat Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Started</dt>
                        <dd class="text-sm text-gray-900 mt-1"><?php echo e($conversation->created_at->format('M d, Y h:i A')); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Activity</dt>
                        <dd class="text-sm text-gray-900 mt-1">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($conversation->last_message_at): ?>
                                <?php echo e($conversation->last_message_at->diffForHumans()); ?>

                            <?php else: ?>
                                Never
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Messages</dt>
                        <dd class="text-sm text-gray-900 mt-1"><?php echo e($conversation->messages->count()); ?> total</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                        <dd class="text-sm text-gray-900 mt-1">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($conversation->assignedTo): ?>
                                <?php echo e($conversation->assignedTo->name); ?>

                            <?php else: ?>
                                <span class="text-gray-400">Unassigned</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-3">
                    <!-- Change Status -->
                    <form action="<?php echo e(route('admin.chat.status', $conversation)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="active" <?php echo e($conversation->status === 'active' ? 'selected' : ''); ?>>Active</option>
                            <option value="closed" <?php echo e($conversation->status === 'closed' ? 'selected' : ''); ?>>Closed</option>
                            <option value="archived" <?php echo e($conversation->status === 'archived' ? 'selected' : ''); ?>>Archived</option>
                        </select>
                    </form>

                    <!-- Contact User -->
                    <a href="mailto:<?php echo e($conversation->user->email); ?>" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white text-sm font-medium rounded-lg transition inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email User
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('admin-chat-form');
    const messageInput = document.getElementById('admin-message-input');
    const chatMessages = document.getElementById('chat-messages');
    const conversationId = document.getElementById('conversation-id').value;

    // Auto-scroll to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    scrollToBottom();

    // Wait for Echo to be ready and listen for new messages
    setTimeout(() => {
        if (window.Echo) {
            console.log('Echo initialized, subscribing to channel:', `chat.${conversationId}`);
            
            window.Echo.private(`chat.${conversationId}`)
                .listen('.message.new', (event) => {
                    console.log('New message received:', event);
                    if (!event.is_admin) {
                        appendUserMessage(event);
                        // Play notification sound
                        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBFWfyZ3rZnjD');
                        audio.play().catch(() => {});
                    }
                });
        } else {
            console.error('Laravel Echo not initialized');
        }
    }, 1000);

    // Send message
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) {
            console.log('Empty message, not sending');
            return;
        }

        const submitBtn = chatForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        console.log('Admin sending message:', message);

        try {
            const requestBody = {
                conversation_id: conversationId,
                message: message
            };
            console.log('Request body:', requestBody);
            
            const response = await fetch('<?php echo e(route("admin.chat.send")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify(requestBody)
            });

            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                console.log('Admin message sent successfully');
                messageInput.value = '';
                appendAdminMessage(data.message);
            } else {
                console.error('Send failed:', data);
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message. Please check console for details.');
        } finally {
            submitBtn.disabled = false;
            messageInput.focus();
        }
    });

    // Append user message to chat (real-time)
    function appendUserMessage(event) {
        const messageDiv = document.createElement('div');
        const time = new Date(event.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                    <?php echo e(substr($conversation->user->name, 0, 1)); ?>

                </div>
                <div class="flex-1 max-w-lg">
                    <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3">
                        <p class="text-gray-800">${event.message}</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-2">${time}</p>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Append admin message to chat
    function appendAdminMessage(message) {
        const messageDiv = document.createElement('div');
        const time = new Date(message.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const userName = message.user?.name || 'Admin';
        const userInitial = userName.charAt(0);
        
        messageDiv.innerHTML = `
            <div class="flex items-start gap-3 justify-end">
                <div class="flex-1 max-w-lg flex flex-col items-end">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-2xl rounded-tr-none px-4 py-3">
                        <p>${message.message}</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 mr-2">${userName} • ${time}</p>
                </div>
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                    ${userInitial}
                </div>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\usp_official\resources\views\admin\chat\show.blade.php ENDPATH**/ ?>