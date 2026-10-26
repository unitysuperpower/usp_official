<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <?php echo $__env->make('seo.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php ($theme = App\Support\Theme::current()); ?>
    <meta name="theme-color" content="<?php echo e($theme['colors']['light']['ink']); ?>">
    <link rel="icon" href="<?php echo e(App\Support\Theme::favicon()); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fonts = App\Support\Theme::fontsUrl()): ?>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="<?php echo e($fonts); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <script>
        // Apply the light/dark mode before first paint so the page does not flash.
        (function () {
            var mode = <?php echo json_encode($theme['mode'], 15, 512) ?>, saved = null;
            try { saved = <?php echo json_encode((bool) $theme['allow_toggle'], 15, 512) ?> ? localStorage.getItem('usp-theme') : null; } catch (e) {}
            var pick = saved || mode;
            if (pick === 'system') pick = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.dataset.theme = pick;
        })();
    </script>
    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/react/app.jsx']); ?>
    <style id="site-theme"><?php echo App\Support\Theme::css(); ?></style>
</head>
<body>
    <div id="app"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($seo['public']): ?> <?php echo $__env->make('seo.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php else: ?> <noscript>Please enable JavaScript to use account features.</noscript> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>
    <script>
        window.__USP__ = <?php echo e(Illuminate\Support\Js::from([
            'page' => $page,
            'props' => $props,
            'breadcrumbs' => $seo['public'] && count($seo['breadcrumbs']) > 1 ? $seo['breadcrumbs'] : [],
            'user' => auth()->user() ? auth()->user()->only(['id', 'name', 'email', 'is_admin']) + ['permissions' => auth()->user()->permissions(), 'home' => auth()->user()->is_admin ? App\Support\StaffHome::url(auth()->user()) : '/dashboard', 'roles' => auth()->user()->roles->pluck('name')] : null,
            'theme' => App\Support\Theme::client(),
            'csrf' => csrf_token(),
            'errors' => $errors->toArray(),
            'old' => collect(session()->getOldInput())->except(['password', 'password_confirmation', 'current_password', 'code', 'recovery_code', 'jazzcash_password', 'jazzcash_integrity_salt', 'license', 'license_key', 'token'])->all(),
            'flash' => collect(['success', 'error', 'status', 'reply_success'])->mapWithKeys(fn ($key) => [$key => session($key)])->filter()->all(),
        ])); ?>;
    </script>
</body>
</html>
<?php /**PATH /home/usp/Laravel_Projects/usp_official/resources/views/react.blade.php ENDPATH**/ ?>