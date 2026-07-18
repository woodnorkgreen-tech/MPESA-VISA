<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="app-url" content="{{ rtrim(config('app.url'), '/') }}" />
    <meta name="theme-color" content="#1434CB" />
    <meta name="description" content="Join the FIFA World Cup 2026™ watch party with Visa predictions and trivia." />
    <title>@yield('title', 'FIFA World Cup 2026™ watch party')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="@yield('body-class', 'bg-gray-950 text-white min-h-screen')">
    @yield('content')
    @stack('scripts')
</body>
</html>
