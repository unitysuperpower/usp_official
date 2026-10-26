<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('seo.head')
    @php($theme = App\Support\Theme::current())
    <meta name="theme-color" content="{{ $theme['colors']['light']['ink'] }}">
    <link rel="icon" href="{{ App\Support\Theme::favicon() }}">
    @if($fonts = App\Support\Theme::fontsUrl())
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="{{ $fonts }}">
    @endif
    <script>
        // Apply the light/dark mode before first paint so the page does not flash.
        (function () {
            var mode = @json($theme['mode']), saved = null;
            try { saved = @json((bool) $theme['allow_toggle']) ? localStorage.getItem('usp-theme') : null; } catch (e) {}
            var pick = saved || mode;
            if (pick === 'system') pick = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.dataset.theme = pick;
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/js/react/app.jsx'])
    <style id="site-theme">{!! App\Support\Theme::css() !!}</style>
</head>
<body>
    <div id="app">@if($seo['public']) @include('seo.content') @else <noscript>Please enable JavaScript to use account features.</noscript> @endif</div>
    <script>
        window.__USP__ = {{ Illuminate\Support\Js::from([
            'page' => $page,
            'props' => $props,
            'breadcrumbs' => $seo['public'] && count($seo['breadcrumbs']) > 1 ? $seo['breadcrumbs'] : [],
            'user' => auth()->user() ? auth()->user()->only(['id', 'name', 'email', 'is_admin']) + ['permissions' => auth()->user()->permissions(), 'home' => auth()->user()->is_admin ? App\Support\StaffHome::url(auth()->user()) : '/dashboard', 'roles' => auth()->user()->roles->pluck('name')] : null,
            'theme' => App\Support\Theme::client(),
            'csrf' => csrf_token(),
            'errors' => $errors->toArray(),
            'old' => collect(session()->getOldInput())->except(['password', 'password_confirmation', 'current_password', 'code', 'recovery_code', 'jazzcash_password', 'jazzcash_integrity_salt', 'license', 'license_key', 'token'])->all(),
            'flash' => collect(['success', 'error', 'status', 'reply_success'])->mapWithKeys(fn ($key) => [$key => session($key)])->filter()->all(),
        ]) }};
    </script>
</body>
</html>
