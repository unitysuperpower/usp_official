<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('seo.head')
    <meta name="theme-color" content="#112b2b">
    @viteReactRefresh
    @vite(['resources/js/react/app.jsx'])
</head>
<body>
    <div id="app">@if($seo['public']) @include('seo.content') @else <noscript>Please enable JavaScript to use account features.</noscript> @endif</div>
    <script>
        window.__USP__ = {{ Illuminate\Support\Js::from([
            'page' => $page,
            'props' => $props,
            'breadcrumbs' => $seo['public'] && count($seo['breadcrumbs']) > 1 ? $seo['breadcrumbs'] : [],
            'user' => auth()->user()?->only(['id', 'name', 'email', 'is_admin']),
            'csrf' => csrf_token(),
            'errors' => $errors->toArray(),
            'old' => collect(session()->getOldInput())->except(['password', 'password_confirmation', 'current_password', 'code', 'recovery_code'])->all(),
            'flash' => collect(['success', 'error', 'status', 'reply_success'])->mapWithKeys(fn ($key) => [$key => session($key)])->filter()->all(),
        ]) }};
    </script>
</body>
</html>
