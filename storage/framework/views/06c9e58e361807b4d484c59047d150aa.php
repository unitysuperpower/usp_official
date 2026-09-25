<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <?php echo \Artesaos\SEOTools\Facades\SEOTools::generate(); ?>

    <meta name="theme-color" content="#112b2b">
    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/react/app.jsx']); ?>
</head>
<body>
    <div id="app"></div>
    <noscript>Please enable JavaScript to use USP Tech Solution.</noscript>
    <script>
        window.__USP__ = <?php echo e(Illuminate\Support\Js::from([
            'page' => $page,
            'props' => $props,
            'user' => auth()->user()?->only(['id', 'name', 'email', 'is_admin']),
            'csrf' => csrf_token(),
            'errors' => $errors->toArray(),
            'old' => collect(session()->getOldInput())->except(['password', 'password_confirmation', 'current_password', 'code', 'recovery_code'])->all(),
            'flash' => collect(['success', 'error', 'status', 'reply_success'])->mapWithKeys(fn ($key) => [$key => session($key)])->filter()->all(),
        ])); ?>;
    </script>
</body>
</html>
<?php /**PATH /home/usp/Laravel_Projects/usp_official/resources/views/react.blade.php ENDPATH**/ ?>