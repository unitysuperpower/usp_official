

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <a href="<?php echo e(route('blogs.index')); ?>" class="group inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 hover:text-indigo-600 font-medium rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all shadow-sm hover:shadow-md mb-8">
            <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span>Back to Blog</span>
        </a>

        <!-- Featured Image -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($blog->featured_image): ?>
            <div class="relative h-[500px] rounded-3xl overflow-hidden mb-8 shadow-2xl ring-1 ring-black/5 group">
                <img src="<?php echo e(asset('storage/' . $blog->featured_image)); ?>" alt="<?php echo e($blog->title); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($blog->is_featured): ?>
                    <span class="absolute top-6 right-6 inline-flex items-center gap-2 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-sm font-bold px-4 py-2 rounded-full shadow-2xl border-2 border-white/30">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        Featured
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Blog Header -->
        <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 mb-8 border border-gray-100">
            <!-- Category -->
            <a href="<?php echo e(route('blogs.category', $blog->category->slug)); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white mb-6 shadow-lg hover:shadow-xl hover:scale-105 transition-all" style="background-color: <?php echo e($blog->category->color); ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <?php echo e($blog->category->name); ?>

            </a>

            <!-- Title -->
            <h1 class="text-4xl md:text-6xl font-extrabold bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 bg-clip-text text-transparent mb-8 leading-tight"><?php echo e($blog->title); ?></h1>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b-2 border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg ring-4 ring-purple-100">
                        <?php echo e(strtoupper(substr($blog->user->name, 0, 2))); ?>

                    </div>
                    <div>
                        <p class="font-bold text-gray-900 text-lg"><?php echo e($blog->user->name); ?></p>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><?php echo e($blog->published_at->format('F d, Y')); ?></span>
                            <span class="text-gray-400">•</span>
                            <span><?php echo e($blog->published_at->diffForHumans()); ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-gray-600">
                    <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-xl">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span class="font-semibold"><?php echo e(number_format($blog->views_count)); ?></span>
                    </div>
                    <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-xl">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span class="font-semibold"><?php echo e(number_format($blog->comments_count)); ?></span>
                    </div>
                    <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-xl">
                        <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold"><?php echo e(ceil(str_word_count($blog->content) / 200)); ?> min</span>
                    </div>
                </div>
            </div>

            <!-- Like & Share Buttons -->
            <div class="flex flex-wrap items-center gap-3 mt-6">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <form action="<?php echo e(route('blogs.like', $blog)); ?>" method="POST" class="inline-block" id="like-form">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="group flex items-center gap-2 px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all hover:scale-105 <?php echo e($blog->isLikedBy(auth()->id()) ? 'bg-gradient-to-r from-red-500 to-pink-500 text-white' : 'bg-white text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 border-2 border-gray-200 hover:border-red-300'); ?>">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="<?php echo e($blog->isLikedBy(auth()->id()) ? 'currentColor' : 'none'); ?>" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <span id="like-count" class="font-bold"><?php echo e(number_format($blog->likes_count)); ?></span>
                            <span><?php echo e($blog->isLikedBy(auth()->id()) ? 'Liked' : 'Like'); ?></span>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="group flex items-center gap-2 px-6 py-3 bg-white hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 text-gray-700 rounded-xl font-semibold border-2 border-gray-200 hover:border-red-300 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span class="font-bold"><?php echo e(number_format($blog->likes_count)); ?></span>
                        <span>Like</span>
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <button onclick="sharePost()" class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all hover:scale-105">
                    <svg class="w-5 h-5 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    <span>Share</span>
                </button>
            </div>
        </div>

        <!-- Blog Content -->
        <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 mb-8 border border-gray-100">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($blog->excerpt): ?>
                <div class="relative text-xl text-gray-700 italic mb-10 pb-10 border-b-2 border-gray-100 pl-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-indigo-600 to-purple-600 rounded-full"></div>
                    <svg class="w-10 h-10 text-indigo-200 mb-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>
                    <?php echo e($blog->excerpt); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="prose prose-lg prose-indigo max-w-none prose-headings:font-bold prose-h2:text-3xl prose-h2:mt-8 prose-h2:mb-4 prose-h3:text-2xl prose-h3:mt-6 prose-h3:mb-3 prose-p:text-gray-700 prose-p:leading-relaxed prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl prose-img:shadow-lg">
                <?php echo nl2br(e($blog->content)); ?>

            </div>

            <!-- Tags -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($blog->tags && count($blog->tags) > 0): ?>
                <div class="mt-10 pt-8 border-t-2 border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <h4 class="text-lg font-bold text-gray-900">Tags</h4>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $blog->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 hover:from-indigo-100 hover:to-purple-100 text-indigo-700 font-semibold text-sm rounded-xl border-2 border-indigo-100 hover:border-indigo-300 transition-all cursor-pointer hover:scale-105">
                                #<?php echo e($tag); ?>

                            </span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <!-- Comments Section -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                Comments (<?php echo e($blog->comments->where('parent_id', null)->count()); ?>)
            </h2>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <!-- Comment Form -->
                <form action="<?php echo e(route('blogs.comment', $blog)); ?>" method="POST" class="mb-8">
                    <?php echo csrf_field(); ?>
                    <textarea name="comment" rows="4" required placeholder="Share your thoughts..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"></textarea>
                    <button type="submit" class="mt-3 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                        Post Comment
                    </button>
                </form>
            <?php else: ?>
                <div class="mb-8 p-6 bg-gray-50 rounded-lg text-center">
                    <p class="text-gray-600 mb-4">Please login to leave a comment</p>
                    <a href="<?php echo e(route('login')); ?>" class="inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Login
                    </a>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Comments List -->
            <div class="space-y-6">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $blog->comments->where('parent_id', null); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="border-l-4 border-indigo-500 pl-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                                <?php echo e(strtoupper(substr($comment->user->name, 0, 2))); ?>

                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-gray-900"><?php echo e($comment->user->name); ?></h4>
                                    <span class="text-sm text-gray-500"><?php echo e($comment->created_at->diffForHumans()); ?></span>
                                </div>
                                <p class="text-gray-700"><?php echo e($comment->comment); ?></p>

                                <!-- Replies -->
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->replies->count() > 0): ?>
                                    <div class="mt-4 ml-8 space-y-4">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $comment->replies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reply): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="flex items-start space-x-3">
                                                <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                                                    <?php echo e(strtoupper(substr($reply->user->name, 0, 2))); ?>

                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <h5 class="font-semibold text-gray-900 text-sm"><?php echo e($reply->user->name); ?></h5>
                                                        <span class="text-xs text-gray-500"><?php echo e($reply->created_at->diffForHumans()); ?></span>
                                                    </div>
                                                    <p class="text-gray-700 text-sm"><?php echo e($reply->comment); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <!-- Reply Form -->
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                                    <button onclick="toggleReplyForm(<?php echo e($comment->id); ?>)" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                        Reply
                                    </button>
                                    <form action="<?php echo e(route('blogs.comment', $blog)); ?>" method="POST" id="reply-form-<?php echo e($comment->id); ?>" class="hidden mt-3">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="parent_id" value="<?php echo e($comment->id); ?>">
                                        <textarea name="comment" rows="2" required placeholder="Write a reply..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                                        <button type="submit" class="mt-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                                            Post Reply
                                        </button>
                                    </form>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-gray-500 text-center py-8">No comments yet. Be the first to comment!</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <!-- Related Posts -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($relatedBlogs->count() > 0): ?>
            <div class="mt-12">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-1.5 h-10 bg-gradient-to-b from-indigo-600 to-purple-600 rounded-full"></div>
                    <h2 class="text-3xl font-bold text-gray-900">You May Also Like</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $relatedBlogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $related): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('blogs.show', $related->slug)); ?>" class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-100">
                            <div class="relative h-48 bg-gradient-to-br from-indigo-500 to-purple-600 overflow-hidden">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($related->featured_image): ?>
                                    <img src="<?php echo e(asset('storage/' . $related->featured_image)); ?>" alt="<?php echo e($related->title); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 text-lg mb-3 line-clamp-2 group-hover:text-indigo-600 transition-colors"><?php echo e($related->title); ?></h3>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <?php echo e($related->published_at->format('M d, Y')); ?>

                                </div>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </article>
</div>

<script>
function toggleReplyForm(commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    form.classList.toggle('hidden');
}

function sharePost() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo e($blog->title); ?>',
            text: '<?php echo e($blog->excerpt); ?>',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
    }
}

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
const likeForm = document.getElementById('like-form');
if (likeForm) {
    likeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch(this.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('like-count').textContent = data.likes_count;
            const button = this.querySelector('button');
            const svg = button.querySelector('svg');
            const text = button.querySelector('span:last-child');
            
            if (data.liked) {
                button.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                button.classList.add('bg-red-500', 'text-white');
                svg.setAttribute('fill', 'currentColor');
                text.textContent = 'Liked';
            } else {
                button.classList.remove('bg-red-500', 'text-white');
                button.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                svg.setAttribute('fill', 'none');
                text.textContent = 'Like';
            }
        });
    });
}
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\usp_official\resources\views\blogs\show.blade.php ENDPATH**/ ?>